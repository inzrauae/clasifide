<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $t = fn (string $name, ?array $options = null) => $options ? ['name' => $name, 'type' => 'select', 'options' => $options] : ['name' => $name, 'type' => 'text'];

        $cats = [
            ['vehicles', 'Vehicles', null, 'car', 'Cars, bikes, three-wheelers', true, 500, 1, [
                $t('Brand'), $t('Model'), $t('Model year'), $t('Mileage'),
                $t('Transmission', ['Automatic', 'Manual', 'Tiptronic', 'Other']),
                $t('Fuel type', ['Petrol', 'Diesel', 'Hybrid', 'Electric', 'CNG']),
                $t('Engine capacity'),
                $t('Body type', ['Saloon', 'Hatchback', 'SUV / 4x4', 'Van', 'Pickup', 'Motorbike', 'Three-wheeler', 'Lorry', 'Other']),
            ]],
            ['property', 'Property', null, 'house', 'Land, houses, apartments', false, 750, 1, [
                $t('Type', ['Land for sale', 'House for sale', 'Apartment for sale', 'House for rent', 'Apartment for rent', 'Room / annex', 'Commercial property']),
                $t('Land size'), $t('Bedrooms'), $t('Bathrooms'), $t('House size'), $t('Deeds'),
            ]],
            ['electronics', 'Electronics', null, 'laptop', 'TVs, laptops, cameras', true, 0, 0, [
                $t('Brand'), $t('Model'), $t('Warranty'),
            ]],
            ['mobile-phones', 'Mobile Phones & Tablets', 'Mobile Phones', 'smartphone', 'Phones, tablets, accessories', true, 0, 0, [
                $t('Brand'), $t('Model'), $t('Storage'), $t('RAM'), $t('Battery health'),
            ]],
            ['home-living', 'Home & Living', null, 'sofa', 'Furniture, appliances, garden', true, 0, 0, [
                $t('Item'), $t('Material'),
            ]],
            ['jobs', 'Jobs', null, 'briefcase', 'Vacancies across Sri Lanka', false, 0, 0, [
                $t('Job type', ['Full-time', 'Part-time', 'Contract', 'Internship', 'Work from home']),
                $t('Experience'), $t('Education'), $t('Salary'), $t('Application deadline'),
            ]],
            ['services', 'Services', null, 'wrench', 'Repairs, trades, events, catering', false, 0, 0, [
                $t('Service'), $t('Coverage'), $t('Availability'),
            ]],
            ['business-industrial', 'Business & Industrial', 'Business', 'store', 'Machinery, office, shop equipment', true, 0, 0, [
                $t('Item'), $t('Capacity'),
            ]],
            ['fashion-lifestyle', 'Fashion & Lifestyle', 'Fashion', 'shirt', 'Clothing, jewellery, beauty', true, 0, 0, [
                $t('Item'), $t('Size'), $t('Material'),
            ]],
            ['hobby-sport-kids', 'Hobby, Sport & Kids', 'Hobby & Sport', 'dumbbell', 'Bicycles, gym, toys', true, 0, 0, [$t('Item')]],
            ['animals', 'Animals', null, 'paw-print', 'Pets, pet food, farm animals', false, 0, 0, [$t('Animal'), $t('Breed'), $t('Age')]],
            ['education', 'Education', null, 'graduation-cap', 'Classes, tuition, books', false, 0, 0, [$t('Subject'), $t('Level'), $t('Medium', ['Sinhala', 'Tamil', 'English'])]],
            ['agriculture', 'Agriculture', null, 'tractor', 'Plants, seeds, crops', false, 0, 0, [$t('Item'), $t('Minimum order')]],
        ];

        foreach ($cats as $i => [$slug, $name, $short, $icon, $hint, $cond, $fee, $free, $fields]) {
            Category::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'short_name' => $short,
                'icon' => $icon,
                'hint' => $hint,
                'sort' => $i + 1,
                'has_condition' => $cond,
                'posting_fee' => $fee,
                'free_ads_per_month' => $free,
                'fields' => $fields,
            ]);
        }
    }
}
