<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Category-specific search filters built from each category's spec fields
 * (Vehicles: Brand, Fuel type, Model year… Property: Type, Bedrooms…).
 *
 * Each field becomes one of:
 *  - choice: a dropdown (the field's fixed options, or the values sellers actually used)
 *  - range:  min / max, for numeric values such as "2015", "78,000 km" or "1,500 cc"
 *  - search: a "contains" text box, when there are too many different values to list
 *
 * URL format: ?spec[Brand]=Toyota&spec[Model year][min]=2012&spec[Model year][max]=2018
 */
class SpecFilters
{
    private const MAX_CHOICES = 40;

    private const MIN_RANGE_VALUES = 6;

    /** The filters to show for a category, with how many live ads have each value. */
    public static function for(Category $category): array
    {
        $fields = $category->specFields();
        if (! $fields) {
            return [];
        }

        $key = 'spec-filters.'.$category->id.'.'.md5(json_encode($fields)).'.'.app()->getLocale();

        return Cache::remember($key, 600, function () use ($category, $fields) {
            $specs = Listing::live()->where('category_id', $category->id)->whereNotNull('specs')
                ->latest('id')->limit(3000)->pluck('specs');

            $filters = [];
            foreach ($fields as $f) {
                // Count values case-insensitively, but show the most common spelling
                $counts = [];
                $spelling = [];
                foreach ($specs as $s) {
                    $v = trim((string) ($s[$f['name']] ?? ''));
                    if ($v === '') {
                        continue;
                    }
                    $k = mb_strtolower($v);
                    $counts[$k] = ($counts[$k] ?? 0) + 1;
                    $spelling[$k][$v] = ($spelling[$k][$v] ?? 0) + 1;
                }

                if ($f['type'] === 'select') {
                    $options = collect($f['options'])->mapWithKeys(fn ($o) => [$o => $counts[mb_strtolower($o)] ?? 0])->all();
                    $filters[] = ['name' => $f['name'], 'kind' => 'choice', 'options' => $options];

                    continue;
                }
                if (! $counts) {
                    continue;
                }

                $values = array_keys($counts);
                $numeric = array_filter($values, fn ($v) => self::number($v) !== null);

                $unit = self::unit(array_map(fn ($k) => array_key_first($spelling[$k]), $values));
                $allNumeric = count($numeric) === count($values);
                // Almost every ad says something different (Model, Salary…): a dropdown would be a wall of one-offs
                $oneOffs = count($values) > 5 && count($values) / array_sum($counts) > 0.7;

                if ($f['type'] === 'number' || ($allNumeric && ($unit !== null || count($values) >= self::MIN_RANGE_VALUES))) {
                    $nums = array_map(fn ($v) => self::number($v), $numeric);
                    $filters[] = ['name' => $f['name'], 'kind' => 'range', 'min' => $nums ? min($nums) : null, 'max' => $nums ? max($nums) : null, 'unit' => $unit];
                } elseif (count($values) <= self::MAX_CHOICES && ! $oneOffs) {
                    arsort($counts);
                    $options = [];
                    foreach ($counts as $k => $n) {
                        arsort($spelling[$k]);
                        $options[array_key_first($spelling[$k])] = $n;
                    }
                    $filters[] = ['name' => $f['name'], 'kind' => 'choice', 'options' => $options];
                } else {
                    $filters[] = ['name' => $f['name'], 'kind' => 'search'];
                }
            }

            return $filters;
        });
    }

    /** The spec filters actually set in the request, limited to this category's filters. */
    public static function active(array $filters, mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $active = [];
        foreach ($filters as $f) {
            $v = $input[$f['name']] ?? null;
            if ($f['kind'] === 'range') {
                $min = is_array($v) ? self::number((string) ($v['min'] ?? '')) : null;
                $max = is_array($v) ? self::number((string) ($v['max'] ?? '')) : null;
                if ($min !== null || $max !== null) {
                    $active[$f['name']] = ['kind' => 'range', 'min' => $min, 'max' => $max, 'unit' => $f['unit']];
                }
            } elseif (is_string($v) && trim($v) !== '') {
                $active[$f['name']] = ['kind' => $f['kind'], 'value' => mb_substr(trim($v), 0, 100)];
            }
        }

        return $active;
    }

    /** Narrow a listing query to the active spec filters. */
    public static function apply(Builder $query, array $active): Builder
    {
        foreach ($active as $name => $f) {
            $text = self::textExpr($name);
            match ($f['kind']) {
                'choice' => $query->whereRaw("LOWER($text) = ?", [mb_strtolower($f['value'])]),
                'search' => $query->whereRaw("LOWER($text) LIKE ?", ['%'.addcslashes(mb_strtolower($f['value']), '%_\\').'%']),
                'range' => $query
                    ->when($f['min'] !== null, fn ($q) => $q->whereRaw(self::numberExpr($text).' >= ?', [$f['min']]))
                    ->when($f['max'] !== null, fn ($q) => $q->whereRaw(self::numberExpr($text).' <= ?', [$f['max']])),
            };
        }

        return $query;
    }

    /** Short label for a filter chip, e.g. "Model year: 2012 – 2018". */
    public static function label(string $name, array $f): string
    {
        if ($f['kind'] !== 'range') {
            return __($name).': '.$f['value'];
        }
        $fmt = fn ($n) => number_format($n, floor($n) == $n ? 0 : 1, '.', $n >= 10000 ? ',' : '').($f['unit'] ? ' '.$f['unit'] : '');

        return __($name).': '.match (true) {
            $f['min'] !== null && $f['max'] !== null => $fmt($f['min']).' – '.$fmt($f['max']),
            $f['min'] !== null => $fmt($f['min']).'+',
            default => __('up to :n', ['n' => $fmt($f['max'])]),
        };
    }

    /** "78,000 km" → 78000, "2015" → 2015, "Toyota" → null */
    public static function number(string $value): ?float
    {
        $v = str_replace(',', '', trim($value));

        return preg_match('/^(\d+(?:\.\d+)?)\s*[\p{L}%]{0,8}$/u', $v, $m) ? (float) $m[1] : null;
    }

    /** The shared unit of a field's values ("km", "cc", "%"), if they all use the same one. */
    private static function unit(array $values): ?string
    {
        $units = array_unique(array_map(fn ($v) => trim(preg_replace('/^[\d,.\s]+/', '', $v)), $values));

        return count($units) === 1 && reset($units) !== '' ? reset($units) : null;
    }

    /** The spec value as plain text, on MySQL and SQLite. */
    private static function textExpr(string $name): string
    {
        $path = DB::connection()->getPdo()->quote('$."'.str_replace(['\\', '"'], ['\\\\', '\\"'], $name).'"');

        return DB::connection()->getDriverName() === 'sqlite'
            ? "json_extract(specs, $path)"
            : "JSON_UNQUOTE(JSON_EXTRACT(specs, $path))";
    }

    /** The spec value as a number: commas dropped, trailing units ignored. */
    private static function numberExpr(string $text): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(REPLACE($text, ',', '') AS REAL)"
            : "CAST(REPLACE($text, ',', '') AS DECIMAL(16,2))";
    }
}
