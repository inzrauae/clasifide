<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Saves uploads straight into public/uploads so no `storage:link` symlink is
 * needed (many cPanel hosts block symlinks). Large photos are scaled down with GD.
 */
class ImageService
{
    /**
     * @return array{path:string, hash:string}
     */
    public static function store(UploadedFile $file, string $folder, int $maxSide = 1280): array
    {
        $hash = md5_file($file->getRealPath());
        $dir = 'uploads/'.$folder.'/'.now()->format('Y/m');
        $abs = public_path($dir);
        if (! is_dir($abs)) {
            mkdir($abs, 0755, true);
        }

        $name = Str::random(24);
        $saved = self::resize($file->getRealPath(), $abs.'/'.$name.'.jpg', $maxSide);

        if ($saved) {
            return ['path' => $dir.'/'.$name.'.jpg', 'hash' => $hash];
        }

        // GD missing or unsupported format: keep the original file
        $ext = strtolower($file->guessExtension() ?: 'jpg');
        $file->move($abs, $name.'.'.$ext);

        return ['path' => $dir.'/'.$name.'.'.$ext, 'hash' => $hash];
    }

    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/') && is_file(public_path($path))) {
            @unlink(public_path($path));
        }
    }

    private static function resize(string $src, string $dest, int $maxSide): bool
    {
        if (! function_exists('imagecreatefromstring')) {
            return false;
        }
        $data = @file_get_contents($src);
        $img = $data ? @imagecreatefromstring($data) : false;
        if (! $img) {
            return false;
        }

        // Respect phone camera rotation
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($src);
            $angle = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 0] ?? 0;
            if ($angle) {
                $img = imagerotate($img, $angle, 0);
            }
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1, $maxSide / max($w, $h));
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);

        $out = imagecreatetruecolor($nw, $nh);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $ok = imagejpeg($out, $dest, 82);
        imagedestroy($img);
        imagedestroy($out);

        return $ok;
    }
}
