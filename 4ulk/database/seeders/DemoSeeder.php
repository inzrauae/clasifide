<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\ListingStat;
use App\Models\Package;
use App\Models\Review;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SafetyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Sample content carried over from the original 4U.lk preview site. */
class DemoSeeder extends Seeder
{
    private const IMG = [
        'aqua' => '1549317661-bd32c8ce0db2', 'car2' => '1503376780353-7e6692767b70', 'scooter' => '1558981806-ec527fa84c39',
        'bicycle' => '1485965120184-e220f721d03e', 'iphone' => '1592750475338-74b7b21085ab', 'phone2' => '1511707171634-5f897ff02aa9',
        'tv' => '1593359677879-a4bb92f829d1', 'monitor' => '1527443224154-c4a3942d3acf', 'sofa' => '1555041469-a586c61ea9bc',
        'chair' => '1580480055273-228ff5388ef8', 'apartment' => '1502672260266-1c1ef2d93688', 'house' => '1600585154340-be6161a56a0c',
        'interior' => '1586023492125-27b2c045efd7', 'office' => '1497366216548-37526070297c', 'ac' => '1621905251918-48416bd8575a',
        'saree' => '1610030469983-98e550d6193c', 'clothes' => '1445205170230-053b83016050', 'garden' => '1416879595882-3373a0480b5b',
        'bakery' => '1509440159596-0249088772ff', 'spa' => '1544161515-4ab6ce6db874',
    ];

    public function run(): void
    {
        if (Listing::exists()) {
            return;
        }

        $cat = Category::pluck('id', 'slug');
        $map = ['vehicles' => 'vehicles', 'mobiles' => 'mobile-phones', 'property' => 'property', 'electronics' => 'electronics',
            'home' => 'home-living', 'fashion' => 'fashion-lifestyle', 'jobs' => 'jobs', 'services' => 'services',
            'business' => 'business-industrial', 'hobby' => 'hobby-sport-kids', 'animals' => 'animals',
            'education' => 'education', 'agriculture' => 'agriculture'];

        // [key, title, cat, district, city, price, unit, from, negotiable, condition, minutesAgo, top, member, photos, seller, phone, specs, description]
        $ads = [
            ['Toyota Aqua G 2015', 'vehicles', 'Colombo', 'Nugegoda', 6450000, null, false, true, 'Used', 35, true, false, ['aqua', 'car2'], 'Nuwan Perera', '0770000101',
                ['Brand' => 'Toyota', 'Model' => 'Aqua G', 'Model year' => '2015', 'Mileage' => '78,000 km', 'Transmission' => 'Automatic', 'Fuel type' => 'Hybrid', 'Engine capacity' => '1,500 cc', 'Body type' => 'Hatchback'],
                "Single owner, full agent service record.\nNew hybrid battery fitted in 2024.\nPush start, reverse camera, alloy wheels, dual airbags.\nLeasing can be arranged. Inspection welcome in Nugegoda."],
            ['Apple iPhone 14 Pro 128GB (Used)', 'mobiles', 'Kandy', 'Peradeniya', 238000, null, false, false, 'Used', 70, true, false, ['iphone', 'phone2'], 'Ishara Bandara', '0710000202',
                ['Brand' => 'Apple', 'Model' => 'iPhone 14 Pro', 'Storage' => '128 GB', 'Battery health' => '92%'],
                "Original device with box and cable, never repaired.\nFace ID and all cameras working perfectly.\nAlways used with a case and screen guard.\nMeet-up in Kandy town or Peradeniya."],
            ['Furnished 2BR Apartment for Rent – Colombo 5', 'property', 'Colombo', 'Colombo 1-15', 145000, '/month', false, false, null, 180, false, true, ['apartment', 'interior'], 'Lanka Homes Realty', '0110000303',
                ['Type' => 'Apartment for rent', 'Bedrooms' => '2', 'Bathrooms' => '2', 'House size' => '1,050 sqft'],
                "1,050 sqft apartment in Havelock Town with A/C, hot water, parking and 24-hour security.\nSix months advance.\nWalking distance to shops and schools."],
            ['Samsung 55" Crystal 4K Smart TV – Brand New', 'electronics', 'Gampaha', 'Kadawatha', 189500, null, false, false, 'Brand New', 260, false, true, ['tv'], 'Digital Zone Kadawatha', '0330000404',
                ['Brand' => 'Samsung', 'Model' => '55" Crystal 4K', 'Warranty' => '2 years (local)'],
                "Sealed box with official local warranty.\nIsland-wide delivery, free within Gampaha district.\nCard payments and instalment plans accepted in-store."],
            ['Teak Sofa Set 3+1+1 with Cushions', 'home', 'Galle', 'Karapitiya', 135000, null, false, true, 'Used', 600, false, false, ['sofa', 'interior'], 'Dilani Wickramasinghe', '0760000505',
                ['Item' => 'Sofa set', 'Material' => 'Solid teak'],
                "Solid teak frame, polished last year, with washable beige cushion covers.\nMoving abroad so must sell.\nBuyer to arrange transport from Karapitiya."],
            ['Honda Dio 2019', 'vehicles', 'Kurunegala', 'Kurunegala', 465000, null, false, true, 'Used', 900, false, false, ['scooter'], 'Chamara Rathnayake', '0700000606',
                ['Brand' => 'Honda', 'Model' => 'Dio', 'Model year' => '2019', 'Mileage' => '21,000 km', 'Engine capacity' => '110 cc', 'Body type' => 'Motorbike'],
                "Well-maintained scooter, licence and insurance valid.\nNew tyres and battery.\nGenuine buyers only, price slightly negotiable after inspection."],
            ['Accounts Executive – Colombo 3', 'jobs', 'Colombo', 'Colombo 1-15', 95000, '/month', false, false, null, 1500, false, true, ['office'], 'Serendib Trading (Pvt) Ltd', '0110000707',
                ['Job type' => 'Full-time', 'Experience' => '2+ years', 'Education' => 'Part-qualified CA / CIMA', 'Salary' => 'Rs. 85,000 – 110,000'],
                "Handle payables, VAT returns and month-end closing.\nEPF/ETF, medical cover and annual bonus.\nSend your CV through 4U.lk chat."],
            ['A/C Repair, Service & Gas Refill – Same Day', 'services', 'Gampaha', 'Negombo', 3500, null, true, false, null, 2200, false, true, ['ac'], 'CoolAir Technicians', '0770000808',
                ['Service' => 'A/C repair & servicing', 'Coverage' => 'Negombo, Ja-Ela, Katunayake, Seeduwa', 'Availability' => '7 days'],
                "Split and inverter A/C servicing, gas top-up, leak repair and installation.\nWeekend visits available."],
            ['Handloom Cotton Sarees – New Arrivals', 'fashion', 'Kandy', 'Kandy', 8900, null, false, false, 'Brand New', 2900, false, true, ['saree', 'clothes'], 'Kandy Weaves', '0810000909',
                ['Item' => 'Saree', 'Material' => 'Handloom cotton'],
                "Hand-woven cotton sarees, blouse piece included.\nCash on delivery island-wide, or visit our shop near the Kandy Clock Tower."],
            ['Land for Sale – 12 Perches, Kottawa', 'property', 'Colombo', 'Kottawa', 1450000, '/perch', false, true, null, 45, true, true, ['garden'], 'Prime Lands Advisory', '0110001010',
                ['Type' => 'Land for sale', 'Land size' => '12 perches', 'Deeds' => 'Clear deeds'],
                "Residential land 1.2 km from Kottawa town and the expressway entrance.\n20 ft carpeted road access. Water and electricity available.\nBank loans can be arranged."],
            ['Samsung Galaxy A54 5G 8GB/256GB', 'mobiles', 'Colombo', 'Maharagama', 92000, null, false, true, 'Used', 140, false, false, ['phone2'], 'Kasun Jayawardena', '0750001111',
                ['Brand' => 'Samsung', 'Model' => 'Galaxy A54 5G', 'Storage' => '256 GB', 'RAM' => '8 GB'],
                "Excellent condition, used for 10 months.\nCharger and box included. Swap not available."],
            ['Modern 3-Bedroom House for Sale – Kadawatha', 'property', 'Gampaha', 'Kadawatha', 32500000, null, false, true, null, 3600, false, true, ['house', 'interior'], 'Lanka Homes Realty', '0110000303',
                ['Type' => 'House for sale', 'Bedrooms' => '3', 'Bathrooms' => '2', 'House size' => '1,850 sqft', 'Land size' => '10 perches'],
                "Two-storey house, 500 m to Kandy Road.\nSolar panels, garage for two vehicles.\nClear deeds, bank loan facilities available."],
            ['Dell 27" Monitor + Office Chair Bundle', 'electronics', 'Colombo', 'Rajagiriya', 68000, null, false, true, 'Used', 4300, false, false, ['monitor', 'chair'], 'Hiran de Silva', '0720001313',
                ['Brand' => 'Dell', 'Model' => '27" monitor + chair'],
                "Work-from-home setup, both in very good condition.\nWill not sell separately."],
            ['Mountain Bicycle 27.5" – 21 Speed', 'hobby', 'Kandy', 'Katugastota', 54000, null, false, true, 'Used', 5200, false, false, ['bicycle'], 'Sahan Gunasekara', '0780001414',
                ['Item' => 'Bicycle'], "Aluminium frame, 27.5\" wheels, 21 speed, disc brakes, recently serviced."],
            ['Wedding & Event Cakes – Order Now', 'services', 'Kandy', 'Kandy', 6500, null, true, false, null, 6000, false, true, ['bakery'], 'Hill Country Bakehouse', '0810001515',
                ['Service' => 'Cakes & catering', 'Coverage' => 'Kandy district'], "Butter cakes, wedding structures and short-eat platters.\nOrder three days in advance."],
            ['Ayurveda Massage Therapist – Galle Fort', 'jobs', 'Galle', 'Galle', 70000, '/month', false, false, null, 6500, false, true, ['spa'], 'Serene Ayurveda Spa', '0910001616',
                ['Job type' => 'Full-time', 'Experience' => '1+ year'], "Certified therapists preferred. Training provided.\nMeals and accommodation included."],
            ['Golden Retriever Puppies', 'animals', 'Gampaha', 'Kiribathgoda', 45000, null, false, false, null, 7000, false, false, [], 'Rashmi Peiris', '0770001717',
                ['Animal' => 'Dog', 'Breed' => 'Golden Retriever', 'Age' => '8 weeks'], "Healthy, playful puppies raised at home.\nFirst vaccine done. Both parents can be seen."],
            ['A/L Combined Maths – Individual & Group Classes', 'education', 'Matara', 'Matara', 2500, '/month', false, false, null, 8000, false, false, [], 'Pradeep Kumara', '0710001818',
                ['Subject' => 'Combined Mathematics', 'Level' => 'A/L', 'Medium' => 'Sinhala'], "Theory and revision classes for 2026 and 2027 batches.\nPhysical and online classes."],
            ['Hybrid Coconut Seedlings', 'agriculture', 'Kurunegala', 'Kuliyapitiya', 450, '/each', false, false, null, 9000, false, true, [], 'Wayamba Nursery', '0370001919',
                ['Item' => 'Coconut seedling', 'Minimum order' => '20'], "Healthy hybrid seedlings, delivery for orders above 100."],
            ['Bajaj RE Three-Wheeler 2017', 'vehicles', 'Kalutara', 'Panadura', 1385000, null, false, true, 'Used', 1100, false, false, [], 'Ajith Kumara', '0700002020',
                ['Brand' => 'Bajaj', 'Model' => 'RE 4 Stroke', 'Model year' => '2017', 'Mileage' => '64,000 km', 'Body type' => 'Three-wheeler'], "Original paint, good engine, ready for transfer."],
            ['Commercial Chest Freezer 400L', 'business', 'Colombo', 'Battaramulla', 118000, null, false, true, 'Used', 4800, false, false, [], 'Malith Stores', '0770002121',
                ['Item' => 'Chest freezer', 'Capacity' => '400 L'], "Closing the shop. Working perfectly, two years old."],
            ['Toyota Premio 2017 – Registered 2018', 'vehicles', 'Gampaha', 'Negombo', 15900000, null, false, true, 'Used', 300, true, true, ['car2'], 'Negombo Auto Traders', '0310002222',
                ['Brand' => 'Toyota', 'Model' => 'Premio', 'Model year' => '2017', 'Mileage' => '52,000 km', 'Transmission' => 'Automatic', 'Fuel type' => 'Petrol', 'Body type' => 'Saloon'],
                "Fully loaded, original paint and mileage guaranteed.\nLeasing facilities up to 60%."],
        ];

        $business = Package::where('slug', 'business')->first();
        $users = [];

        foreach ($ads as $i => [$title, $c, $district, $city, $price, $unit, $from, $neg, $cond, $mins, $top, $member, $photos, $seller, $phone, $specs, $desc]) {
            // One account per seller name
            if (! isset($users[$seller])) {
                $u = new User([
                    'name' => $seller,
                    'email' => Str::slug($seller).'@demo.4u.lk',
                    'password' => 'password',
                    'phone' => $phone,
                    'whatsapp' => $phone,
                    'district' => $district,
                    'city' => $city,
                    'account_type' => $member ? 'business' : 'individual',
                ]);
                $u->forceFill([
                    'created_at' => now()->subMonths(rand(3, 40)),
                    'verified_at' => $member ? now()->subMonths(2) : null,
                    'trust_score' => $member ? 80 : 55,
                ])->save();

                if ($member) {
                    Subscription::create([
                        'user_id' => $u->id, 'package_id' => $business->id,
                        'starts_at' => now()->subDays(5), 'ends_at' => now()->addDays(25),
                        'promotions_left' => 12, 'premium_ads_left' => 3,
                    ]);
                    Store::create([
                        'user_id' => $u->id, 'name' => $seller, 'slug' => Str::slug($seller),
                        'tagline' => "Trusted seller in {$city}", 'description' => "{$seller} has served customers in {$district} for years. Browse our latest stock below or get in touch.",
                        'phone' => $phone, 'whatsapp' => $phone, 'district' => $district, 'city' => $city, 'opening_hours' => 'Mon–Sat, 9am–6pm',
                    ]);
                }
                $users[$seller] = $u->fresh();
            }
            $u = $users[$seller];
            $posted = now()->subMinutes($mins);

            $listing = Listing::create([
                'user_id' => $u->id,
                'category_id' => $cat[$map[$c]],
                'store_id' => $u->stores()->value('id'),
                'title' => $title,
                'slug' => Str::slug($title),
                'description' => $desc,
                'price' => $price,
                'price_unit' => $unit,
                'price_from' => $from,
                'negotiable' => $neg,
                'condition' => $cond,
                'district' => $district,
                'city' => $city,
                'specs' => $specs,
                'contact_name' => $seller,
                'phone' => $phone,
                'whatsapp' => $phone,
                'status' => 'active',
                'fingerprint' => SafetyService::fingerprint($title, $cat[$map[$c]]),
                'top_until' => $top ? now()->addDays(5) : null,
                'featured_until' => $top || $i % 5 === 0 ? now()->addDays(4) : null,
                'urgent_until' => $i === 4 ? now()->addDays(3) : null,
                'views_count' => $v = rand(60, 900),
                'phone_reveals' => (int) ($v * 0.06),
                'whatsapp_clicks' => (int) ($v * 0.04),
                'published_at' => $posted,
                'expires_at' => $posted->copy()->addDays(60),
                'created_at' => $posted,
            ]);

            foreach ($photos as $s => $key) {
                $listing->images()->create([
                    'path' => 'https://images.unsplash.com/photo-'.self::IMG[$key].'?auto=format&fit=crop&w=1000&q=72',
                    'sort' => $s,
                ]);
            }

            // 30 days of stats so the seller dashboard has a chart
            for ($d = 29; $d >= 0; $d--) {
                $views = rand(0, 40);
                ListingStat::create([
                    'listing_id' => $listing->id, 'date' => now()->subDays($d)->toDateString(),
                    'views' => $views, 'phone_reveals' => (int) floor($views / 12), 'whatsapp_clicks' => (int) floor($views / 18), 'messages' => rand(0, 10) > 8 ? 1 : 0,
                ]);
            }
        }

        // A demo buyer with a chat + review so messaging and reputation have content
        $buyer = User::create([
            'name' => 'Demo Buyer', 'email' => 'buyer@demo.4u.lk', 'password' => 'password',
            'phone' => '0779999999', 'district' => 'Colombo', 'account_type' => 'individual',
        ]);
        $ad = Listing::where('title', 'like', 'Toyota Aqua%')->first();
        $conv = Conversation::create([
            'listing_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $ad->user_id,
            'seller_unread' => 1, 'last_message_at' => now()->subMinutes(20), 'lead_status' => 'new',
        ]);
        $conv->messages()->create(['sender_id' => $buyer->id, 'body' => 'Hi, is the Aqua still available? Can I come to see it on Saturday morning?']);

        $lh = $users['Lanka Homes Realty'];
        Review::create(['seller_id' => $lh->id, 'reviewer_id' => $buyer->id, 'rating' => 5, 'comment' => 'Very helpful agent, showed us three apartments in one day.']);
    }
}
