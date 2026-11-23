<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /** The five plans from the 4U.lk 2026 business model. */
    public function run(): void
    {
        $plans = [
            ['free', 'Free', 0, 0, 0, 5, 0, false, false, false, [
                'Up to 5 live ads', 'Basic listings', 'Chat, call & WhatsApp leads', 'Basic view counts',
            ]],
            ['starter', 'Starter', 990, 1, 5, 20, 1, false, false, false, [
                '1 online store page', '5 promotion credits / month', 'Up to 20 live ads', '1 free vehicle or property ad', 'MEMBER badge on your ads',
            ]],
            ['business', 'Business', 1990, 1, 15, 60, 3, true, false, false, [
                'Store with your logo & banner', '15 promotion credits / month', 'Up to 60 live ads', '3 free vehicle or property ads', 'Seller analytics dashboard',
            ]],
            ['premium', 'Premium', 3990, 1, 40, 200, 10, true, true, false, [
                'Advanced store page', '40 promotion credits / month', 'Up to 200 live ads', '10 free vehicle or property ads', 'Full analytics', 'Lead tools: buyer pipeline & notes',
            ]],
            ['enterprise', 'Enterprise', 0, 5, 150, 1000, 50, true, true, true, [
                'Multiple stores & branches', 'Custom promotion bundle', 'Bulk ad posting', 'Dedicated account manager', 'Business support',
            ]],
        ];

        foreach ($plans as $i => [$slug, $name, $price, $stores, $promos, $ads, $premium, $analytics, $leads, $custom, $features]) {
            Package::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'price' => $price,
                'duration_days' => 30,
                'stores' => $stores,
                'promotions' => $promos,
                'active_ads' => $ads,
                'premium_ads' => $premium,
                'analytics' => $analytics,
                'lead_tools' => $leads,
                'is_custom' => $custom,
                'features' => $features,
                'sort' => $i + 1,
            ]);
        }
    }
}
