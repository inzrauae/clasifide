<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Services\SafetyService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Original sample ads (5 per category) posted from the admin account, so a new
 * site does not look empty. Contact details are the admin's own.
 *
 *   php artisan marketplace:sample-ads            add them (skips ones already there)
 *   php artisan marketplace:sample-ads --remove   delete them again
 */
class SampleAds extends Command
{
    protected $signature = 'marketplace:sample-ads {--remove : Delete the sample ads} {--admin= : Admin email (default: first admin)}';

    protected $description = 'Add (or remove) 5 sample ads per category, posted as the admin';

    /** Free-licence Unsplash photos */
    private const IMG = [
        'aqua' => '1549317661-bd32c8ce0db2', 'car2' => '1503376780353-7e6692767b70', 'scooter' => '1558981806-ec527fa84c39',
        'bicycle' => '1485965120184-e220f721d03e', 'iphone' => '1592750475338-74b7b21085ab', 'phone2' => '1511707171634-5f897ff02aa9',
        'tv' => '1593359677879-a4bb92f829d1', 'monitor' => '1527443224154-c4a3942d3acf', 'sofa' => '1555041469-a586c61ea9bc',
        'chair' => '1580480055273-228ff5388ef8', 'apartment' => '1502672260266-1c1ef2d93688', 'house' => '1600585154340-be6161a56a0c',
        'interior' => '1586023492125-27b2c045efd7', 'office' => '1497366216548-37526070297c', 'ac' => '1621905251918-48416bd8575a',
        'saree' => '1610030469983-98e550d6193c', 'clothes' => '1445205170230-053b83016050', 'garden' => '1416879595882-3373a0480b5b',
        'bakery' => '1509440159596-0249088772ff', 'spa' => '1544161515-4ab6ce6db874',
    ];

    public function handle(): int
    {
        $admin = $this->option('admin')
            ? User::where('email', $this->option('admin'))->where('role', 'admin')->first()
            : User::where('role', 'admin')->orderBy('id')->first();
        if (! $admin) {
            $this->error('No admin account found. Create one first.');

            return self::FAILURE;
        }

        $ads = self::ads();
        $titles = array_column($ads, 1);

        if ($this->option('remove')) {
            $n = 0;
            Listing::withTrashed()->where('user_id', $admin->id)->whereIn('title', $titles)->get()
                ->each(function (Listing $l) use (&$n) {
                    $l->images()->delete();
                    $l->forceDelete();
                    $n++;
                });
            $this->info("Removed {$n} sample ads.");

            return self::SUCCESS;
        }

        $cats = Category::pluck('id', 'slug');
        $existing = Listing::where('user_id', $admin->id)->whereIn('title', $titles)->pluck('title')->all();
        $phone = $admin->phone ?: '0110000000';
        $added = 0;

        foreach ($ads as $i => [$slug, $title, $district, $city, $price, $unit, $cond, $photos, $specs, $desc]) {
            if (in_array($title, $existing, true) || ! isset($cats[$slug])) {
                continue;
            }
            $posted = now()->subMinutes(30 + $i * 97);

            $listing = Listing::create([
                'user_id' => $admin->id,
                'category_id' => $cats[$slug],
                'title' => $title,
                'slug' => Str::slug($title),
                'description' => $desc,
                'price' => $price,
                'price_unit' => $unit,
                'negotiable' => $price !== null && $i % 3 === 0,
                'condition' => $cond,
                'district' => $district,
                'city' => $city,
                'specs' => $specs,
                'contact_name' => $admin->name,
                'phone' => $phone,
                'whatsapp' => $admin->whatsapp ?: $phone,
                'status' => 'active',
                'fingerprint' => SafetyService::fingerprint($title, $cats[$slug]),
                'featured_until' => $i % 7 === 0 ? now()->addDays(7) : null,
                'top_until' => $i % 11 === 0 ? now()->addDays(5) : null,
                'published_at' => $posted,
                'expires_at' => $posted->copy()->addDays(config('marketplace.ad_lifetime_days', 60)),
                'created_at' => $posted,
            ]);
            foreach ($photos as $s => $key) {
                $listing->images()->create([
                    'path' => 'https://images.unsplash.com/photo-'.self::IMG[$key].'?auto=format&fit=crop&w=1000&q=72',
                    'sort' => $s,
                ]);
            }
            $added++;
        }

        $this->info("Added {$added} sample ads as {$admin->email} (".count($existing).' were already there).');
        $this->line('Remove them later with: php artisan marketplace:sample-ads --remove');

        return self::SUCCESS;
    }

    /** [category, title, district, city, price, unit, condition, photos, specs, description] */
    private static function ads(): array
    {
        return [
            // ---------------------------------------------------------------- Vehicles
            ['vehicles', 'Suzuki Wagon R Stingray 2018', 'Colombo', 'Dehiwala', 6950000, null, 'Used', ['car2'],
                ['Brand' => 'Suzuki', 'Model' => 'Wagon R Stingray', 'Model year' => '2018', 'Mileage' => '45,000 km', 'Transmission' => 'Automatic', 'Fuel type' => 'Hybrid', 'Engine capacity' => '660 cc', 'Body type' => 'Hatchback'],
                "Second owner, full service history.\nPush start, reverse camera, climate control.\nLeasing facilities available."],
            ['vehicles', 'Toyota Vitz 2017 Safety Edition', 'Gampaha', 'Ja-Ela', 7250000, null, 'Used', ['aqua'],
                ['Brand' => 'Toyota', 'Model' => 'Vitz Safety Edition', 'Model year' => '2017', 'Mileage' => '58,000 km', 'Transmission' => 'Automatic', 'Fuel type' => 'Petrol', 'Engine capacity' => '1,000 cc', 'Body type' => 'Hatchback'],
                "Pre-crash safety, lane assist, auto lights.\nOriginal paint, no accidents.\nInspection welcome in Ja-Ela."],
            ['vehicles', 'Yamaha FZ-S V3 2021', 'Kurunegala', 'Kuliyapitiya', 685000, null, 'Used', ['scooter'],
                ['Brand' => 'Yamaha', 'Model' => 'FZ-S V3', 'Model year' => '2021', 'Mileage' => '18,500 km', 'Engine capacity' => '150 cc', 'Body type' => 'Motorbike'],
                "Single owner, serviced at the agent.\nNew tyres, licence and insurance valid until next year."],
            ['vehicles', 'Bajaj RE Three-Wheeler 2019', 'Kalutara', 'Horana', 1550000, null, 'Used', [],
                ['Brand' => 'Bajaj', 'Model' => 'RE 4 Stroke', 'Model year' => '2019', 'Mileage' => '52,000 km', 'Fuel type' => 'Petrol', 'Body type' => 'Three-wheeler'],
                "Good engine and gearbox, recently re-upholstered.\nReady for immediate transfer."],
            ['vehicles', 'Nissan Leaf 2016 – 30 kWh', 'Colombo', 'Battaramulla', 5900000, null, 'Used', ['car2'],
                ['Brand' => 'Nissan', 'Model' => 'Leaf', 'Model year' => '2016', 'Mileage' => '62,000 km', 'Transmission' => 'Automatic', 'Fuel type' => 'Electric', 'Body type' => 'Hatchback'],
                "Battery health 10 bars, home charger included.\nVery low running cost, ideal for city use."],

            // ---------------------------------------------------------------- Property
            ['property', 'Land for Sale – 15 Perches, Piliyandala', 'Colombo', 'Piliyandala', 1250000, '/perch', null, ['garden'],
                ['Type' => 'Land for sale', 'Land size' => '15 perches', 'Deeds' => 'Clear deeds'],
                "Flat residential land, 800 m from Piliyandala town.\n15 ft road access, water and electricity available.\nBank loans can be arranged."],
            ['property', '3-Bedroom House for Rent – Nugegoda', 'Colombo', 'Nugegoda', 120000, '/month', null, ['house'],
                ['Type' => 'House for rent', 'Bedrooms' => '3', 'Bathrooms' => '2', 'House size' => '1,600 sqft'],
                "Quiet lane close to schools and the High Level Road.\nParking for one car, hot water, pantry cupboards.\nSix months advance."],
            ['property', 'Luxury 3BR Apartment for Sale – Colombo 3', 'Colombo', 'Colombo 1-15', 68500000, null, null, ['apartment', 'interior'],
                ['Type' => 'Apartment for sale', 'Bedrooms' => '3', 'Bathrooms' => '3', 'House size' => '1,750 sqft'],
                "Sea-view apartment with pool, gym and 24-hour security.\nTwo parking slots. Fully furnished with branded fittings."],
            ['property', 'Furnished Annex for Rent – Peradeniya', 'Kandy', 'Peradeniya', 30000, '/month', null, ['interior'],
                ['Type' => 'Room / annex', 'Bedrooms' => '1', 'Bathrooms' => '1'],
                "Separate entrance, attached bathroom and small kitchen.\nIdeal for a university student or working professional."],
            ['property', 'Shop Space for Rent – Galle Road, Galle', 'Galle', 'Galle', 85000, '/month', null, ['office'],
                ['Type' => 'Commercial property', 'House size' => '650 sqft'],
                "Ground-floor shop facing the main road, high foot traffic.\nThree-phase power, roller doors, parking in front."],

            // ---------------------------------------------------------------- Electronics
            ['electronics', 'Lenovo IdeaPad Slim 3 – Core i5 12th Gen', 'Colombo', 'Maharagama', 189000, null, 'Brand New', ['monitor'],
                ['Brand' => 'Lenovo', 'Model' => 'IdeaPad Slim 3', 'Warranty' => '2 years'],
                "16 GB RAM, 512 GB SSD, 15.6\" Full HD display.\nSealed box with local warranty."],
            ['electronics', 'Canon EOS 200D II with 18-55mm Lens', 'Kandy', 'Kandy', 165000, null, 'Used', [],
                ['Brand' => 'Canon', 'Model' => 'EOS 200D II', 'Warranty' => 'No warranty'],
                "Shutter count about 8,000. Includes bag, two batteries and 64 GB card.\nNo scratches on the lens."],
            ['electronics', 'Sony PlayStation 5 Slim – Disc Edition', 'Colombo', 'Nugegoda', 178000, null, 'Brand New', [],
                ['Brand' => 'Sony', 'Model' => 'PS5 Slim Disc', 'Warranty' => '1 year'],
                "Brand new, sealed. One controller included.\nDelivery available in Colombo district."],
            ['electronics', 'LG 43" Full HD Smart TV', 'Gampaha', 'Gampaha', 98500, null, 'Used', ['tv'],
                ['Brand' => 'LG', 'Model' => '43" Full HD Smart', 'Warranty' => '6 months left'],
                "Netflix and YouTube built in. Wall bracket and remote included."],
            ['electronics', 'JBL Flip 6 Bluetooth Speaker', 'Galle', 'Galle', 34500, null, 'Brand New', [],
                ['Brand' => 'JBL', 'Model' => 'Flip 6', 'Warranty' => '1 year'],
                "Waterproof, 12-hour battery. Black colour, sealed box."],

            // ---------------------------------------------------------------- Mobile phones
            ['mobile-phones', 'Apple iPhone 13 128GB – Midnight', 'Colombo', 'Nugegoda', 168000, null, 'Used', ['iphone'],
                ['Brand' => 'Apple', 'Model' => 'iPhone 13', 'Storage' => '128 GB', 'Battery health' => '88%'],
                "Never repaired, Face ID working. Box and cable included."],
            ['mobile-phones', 'Samsung Galaxy S23 Ultra 256GB', 'Colombo', 'Colombo 1-15', 285000, null, 'Used', ['phone2'],
                ['Brand' => 'Samsung', 'Model' => 'Galaxy S23 Ultra', 'Storage' => '256 GB', 'RAM' => '12 GB'],
                "Excellent condition with S Pen, always used with a case.\nBox, charger and receipt available."],
            ['mobile-phones', 'Redmi Note 13 8GB/256GB – Brand New', 'Kurunegala', 'Kurunegala', 64900, null, 'Brand New', ['phone2'],
                ['Brand' => 'Xiaomi', 'Model' => 'Redmi Note 13', 'Storage' => '256 GB', 'RAM' => '8 GB'],
                "Sealed box with one year company warranty.\nCash on delivery island-wide."],
            ['mobile-phones', 'Apple iPad 9th Gen 64GB Wi-Fi', 'Kandy', 'Kandy', 92000, null, 'Used', [],
                ['Brand' => 'Apple', 'Model' => 'iPad 9th Gen', 'Storage' => '64 GB'],
                "Used for online classes, very clean. Cover and charger included."],
            ['mobile-phones', 'Huawei Nova Y70 4GB/128GB', 'Matara', 'Matara', 38500, null, 'Used', [],
                ['Brand' => 'Huawei', 'Model' => 'Nova Y70', 'Storage' => '128 GB', 'RAM' => '4 GB'],
                "6,000 mAh battery lasts two days. Minor scratches on the back."],

            // ---------------------------------------------------------------- Home & Living
            ['home-living', 'Teak Dining Table with 6 Chairs', 'Kandy', 'Kandy', 145000, null, 'Used', ['interior'],
                ['Item' => 'Dining set', 'Material' => 'Teak'],
                "Solid teak, recently polished. Table 6 ft x 3.5 ft.\nBuyer to arrange transport."],
            ['home-living', 'Samsung 7kg Front Load Washing Machine', 'Colombo', 'Kottawa', 82000, null, 'Used', [],
                ['Item' => 'Washing machine', 'Material' => 'Steel / plastic'],
                "Inverter motor, 15 wash programmes. Working perfectly, two years old."],
            ['home-living', 'Queen Size Bed with Spring Mattress', 'Gampaha', 'Kadawatha', 58000, null, 'Used', [],
                ['Item' => 'Bed with mattress', 'Material' => 'Mahogany'],
                "Mahogany frame with 8-inch spring mattress, very clean."],
            ['home-living', '3-Seater Fabric Sofa – Grey', 'Colombo', 'Rajagiriya', 65000, null, 'Brand New', ['sofa'],
                ['Item' => 'Sofa', 'Material' => 'Fabric'],
                "Made to order in our workshop. Free delivery within Colombo."],
            ['home-living', 'Inverter Air Conditioner 12,000 BTU', 'Galle', 'Karapitiya', 155000, null, 'Brand New', ['ac'],
                ['Item' => 'Air conditioner', 'Material' => 'Inverter'],
                "Energy-saving inverter model with 5-year compressor warranty.\nInstallation can be arranged."],

            // ---------------------------------------------------------------- Jobs
            ['jobs', 'Sales Executive – Kurunegala', 'Kurunegala', 'Kurunegala', 60000, '/month', null, ['office'],
                ['Job type' => 'Full-time', 'Experience' => '1+ year in sales', 'Education' => 'O/L and A/L', 'Salary' => 'Rs. 50,000 – 70,000 + commission'],
                "Visit retail shops in the Kurunegala area and grow our customer base.\nMotorbike and licence required. Fuel allowance paid."],
            ['jobs', 'Graphic Designer (Hybrid) – Colombo 7', 'Colombo', 'Colombo 1-15', 120000, '/month', null, ['office'],
                ['Job type' => 'Full-time', 'Experience' => '2+ years', 'Education' => 'Diploma or degree in design', 'Salary' => 'Rs. 100,000 – 140,000'],
                "Social media creatives, print and branding work.\nThree days in office, two from home. Send your portfolio via chat."],
            ['jobs', 'Delivery Rider – Gampaha', 'Gampaha', 'Gampaha', 55000, '/month', null, [],
                ['Job type' => 'Full-time', 'Experience' => 'Not required', 'Education' => 'O/L', 'Salary' => 'Rs. 45,000 + incentives'],
                "Own motorbike and valid licence required.\nDaily incentives and fuel allowance."],
            ['jobs', 'English Teacher – International School, Kandy', 'Kandy', 'Kandy', 90000, '/month', null, [],
                ['Job type' => 'Full-time', 'Experience' => '2+ years teaching', 'Education' => 'Degree in English', 'Salary' => 'Rs. 80,000 – 110,000'],
                "Teach English for grades 6 to 11 (Edexcel syllabus)."],
            ['jobs', 'Front Office Receptionist – Hotel in Galle', 'Galle', 'Galle', 50000, '/month', null, ['spa'],
                ['Job type' => 'Full-time', 'Experience' => '1+ year in hospitality', 'Education' => 'A/L, hotel school preferred', 'Salary' => 'Rs. 45,000 + service charge'],
                "Good spoken English required. Meals and uniform provided."],

            // ---------------------------------------------------------------- Services
            ['services', 'House Wiring & Electrical Repairs', 'Colombo', 'Maharagama', 2500, null, null, [],
                ['Service' => 'Electrician', 'Coverage' => 'Colombo district', 'Availability' => '7 days'],
                "New wiring, DB boards, fault finding and CCTV installation.\nNVQ-qualified electricians."],
            ['services', 'Wedding Photography & Videography', 'Kandy', 'Kandy', 85000, null, null, [],
                ['Service' => 'Photography', 'Coverage' => 'Island-wide', 'Availability' => 'Book in advance'],
                "Full-day coverage, pre-shoot, album and edited highlight video."],
            ['services', 'Lorry Hire for House Moving', 'Gampaha', 'Kadawatha', 8000, null, null, [],
                ['Service' => 'Transport', 'Coverage' => 'Island-wide', 'Availability' => '7 days'],
                "10.5 ft and 14.5 ft lorries with helpers for loading."],
            ['services', 'Termite & Pest Control Service', 'Colombo', 'Dehiwala', 6500, null, null, [],
                ['Service' => 'Pest control', 'Coverage' => 'Western Province', 'Availability' => 'Mon–Sat'],
                "Termite, cockroach and rodent treatment with a 1-year guarantee."],
            ['services', 'Laptop & Phone Repair – Same Day', 'Colombo', 'Wellawatte', 1500, null, null, [],
                ['Service' => 'Electronics repair', 'Coverage' => 'Colombo', 'Availability' => 'Mon–Sat'],
                "Screen and battery replacement, software issues, data recovery."],

            // ---------------------------------------------------------------- Business & Industrial
            ['business-industrial', 'Commercial Display Chiller – 2 Door', 'Colombo', 'Dehiwala', 165000, null, 'Used', [],
                ['Item' => 'Display chiller', 'Capacity' => '1,000 L'],
                "Glass doors with LED lighting, cools well. Shop closing down."],
            ['business-industrial', 'Juki Industrial Sewing Machine', 'Gampaha', 'Minuwangoda', 72000, null, 'Used', [],
                ['Item' => 'Sewing machine', 'Capacity' => 'Single needle'],
                "Servo motor, table included. Ideal for a small garment unit."],
            ['business-industrial', 'Office Desks & Chairs – Set of 4', 'Colombo', 'Rajagiriya', 120000, null, 'Used', ['office', 'chair'],
                ['Item' => 'Office furniture', 'Capacity' => '4 workstations'],
                "Moving office, all in good condition. Will not sell separately."],
            ['business-industrial', 'POS System with Scanner & Printer', 'Kandy', 'Kandy', 98000, null, 'Brand New', [],
                ['Item' => 'POS system', 'Capacity' => '1 counter'],
                "Touch screen, barcode scanner, receipt printer and billing software.\nInstallation and training included."],
            ['business-industrial', '3-Deck Gas Bakery Oven', 'Kurunegala', 'Kurunegala', 450000, null, 'Used', ['bakery'],
                ['Item' => 'Bakery oven', 'Capacity' => '9 trays'],
                "Stainless steel, temperature control on each deck. Working condition."],

            // ---------------------------------------------------------------- Fashion
            ['fashion-lifestyle', 'Kandyan Osariya Silk Saree', 'Kandy', 'Kandy', 15500, null, 'Brand New', ['saree'],
                ['Item' => 'Saree', 'Size' => 'Free size', 'Material' => 'Silk'],
                "Hand-finished silk saree with blouse piece. Several colours available."],
            ['fashion-lifestyle', 'Men\'s Formal Shirts – Pack of 3', 'Colombo', 'Pettah', 6900, null, 'Brand New', ['clothes'],
                ['Item' => 'Shirts', 'Size' => 'S to XXL', 'Material' => 'Cotton'],
                "Long sleeve, easy iron cotton. Cash on delivery island-wide."],
            ['fashion-lifestyle', 'Genuine Leather Ladies Handbag', 'Colombo', 'Nugegoda', 8500, null, 'Brand New', [],
                ['Item' => 'Handbag', 'Material' => 'Leather'],
                "Locally made, brown and black colours available."],
            ['fashion-lifestyle', 'Casio G-Shock GA-2100 Watch', 'Galle', 'Galle', 32000, null, 'Used', [],
                ['Item' => 'Watch', 'Material' => 'Resin'],
                "Original, bought from the authorised dealer. Box and warranty card included."],
            ['fashion-lifestyle', 'Kids Party Frocks (2–8 years)', 'Gampaha', 'Negombo', 3500, null, 'Brand New', ['clothes'],
                ['Item' => 'Frock', 'Size' => '2–8 years', 'Material' => 'Net and satin'],
                "Many designs available, delivery island-wide."],

            // ---------------------------------------------------------------- Hobby, Sport & Kids
            ['hobby-sport-kids', 'Motorised Treadmill – 2HP', 'Colombo', 'Kottawa', 115000, null, 'Used', [],
                ['Item' => 'Treadmill'],
                "Max 120 kg user weight, auto incline. Foldable."],
            ['hobby-sport-kids', 'Kids Bicycle 16" with Training Wheels', 'Gampaha', 'Wattala', 18500, null, 'Used', ['bicycle'],
                ['Item' => 'Bicycle'],
                "Suitable for ages 4 to 7. Good tyres and brakes."],
            ['hobby-sport-kids', 'Yamaha F310 Acoustic Guitar', 'Kandy', 'Kandy', 38000, null, 'Used', [],
                ['Item' => 'Guitar'],
                "Great beginner guitar with bag, capo and spare strings."],
            ['hobby-sport-kids', 'Senior Cricket Kit – Full Set', 'Matara', 'Matara', 42000, null, 'Used', [],
                ['Item' => 'Cricket kit'],
                "English willow bat, pads, gloves, helmet and kit bag."],
            ['hobby-sport-kids', 'Road Bike 700c – 18 Speed', 'Colombo', 'Mount Lavinia', 95000, null, 'Used', ['bicycle'],
                ['Item' => 'Bicycle'],
                "Aluminium frame, Shimano gears, recently serviced."],

            // ---------------------------------------------------------------- Animals
            ['animals', 'Labrador Puppies – 7 Weeks', 'Kandy', 'Katugastota', 40000, null, null, [],
                ['Animal' => 'Dog', 'Breed' => 'Labrador Retriever', 'Age' => '7 weeks'],
                "Home-raised, first vaccine and deworming done. Parents can be seen."],
            ['animals', 'Persian Kittens', 'Colombo', 'Dehiwala', 25000, null, null, [],
                ['Animal' => 'Cat', 'Breed' => 'Persian', 'Age' => '10 weeks'],
                "Litter trained and playful. Two males, one female."],
            ['animals', 'Love Birds – Pair', 'Kurunegala', 'Kurunegala', 6500, null, null, [],
                ['Animal' => 'Bird', 'Breed' => 'Love bird', 'Age' => '6 months'],
                "Healthy breeding pair. Cage can be given for an extra price."],
            ['animals', 'Village Chickens (Live)', 'Anuradhapura', 'Anuradhapura', 2800, '/each', null, [],
                ['Animal' => 'Chicken', 'Breed' => 'Village', 'Age' => '5 months'],
                "Free-range village chickens. Minimum order of 5."],
            ['animals', 'Friesian Cross Dairy Cow', 'Nuwara Eliya', 'Nuwara Eliya', 380000, null, null, [],
                ['Animal' => 'Cow', 'Breed' => 'Friesian cross', 'Age' => '4 years'],
                "Gives about 14 litres of milk a day. Calm and healthy."],

            // ---------------------------------------------------------------- Education
            ['education', 'O/L Maths Classes – English Medium', 'Colombo', 'Nugegoda', 3000, '/month', null, [],
                ['Subject' => 'Mathematics', 'Level' => 'O/L', 'Medium' => 'English'],
                "Small groups, weekly paper classes and past paper discussions."],
            ['education', 'IELTS Preparation Course – 8 Weeks', 'Kandy', 'Kandy', 35000, null, null, [],
                ['Subject' => 'IELTS', 'Level' => 'Adult', 'Medium' => 'English'],
                "All four modules with mock tests. Weekday and weekend batches."],
            ['education', 'Grade 5 Scholarship Classes', 'Gampaha', 'Gampaha', 2000, '/month', null, [],
                ['Subject' => 'Scholarship exam', 'Level' => 'Grade 5', 'Medium' => 'Sinhala'],
                "Experienced teacher, model papers every week."],
            ['education', 'Piano Lessons – Trinity Exams', 'Colombo', 'Rajagiriya', 6000, '/month', null, [],
                ['Subject' => 'Piano', 'Level' => 'All grades', 'Medium' => 'English'],
                "Individual lessons for children and adults, home visits possible."],
            ['education', 'A/L Biology Online Classes', 'Galle', 'Galle', 3500, '/month', null, [],
                ['Subject' => 'Biology', 'Level' => 'A/L', 'Medium' => 'Sinhala'],
                "Theory and revision on Zoom, recordings provided."],

            // ---------------------------------------------------------------- Agriculture
            ['agriculture', 'TJC Mango Plants', 'Kurunegala', 'Kuliyapitiya', 850, '/each', null, ['garden'],
                ['Item' => 'Mango plant', 'Minimum order' => '5'],
                "Grafted plants, fruit within 2 to 3 years. Delivery for large orders."],
            ['agriculture', 'Organic Compost – 25 kg Bag', 'Matale', 'Matale', 1200, '/each', null, [],
                ['Item' => 'Compost', 'Minimum order' => '10 bags'],
                "Made from cow dung and plant waste. Ideal for home gardens."],
            ['agriculture', 'Sri Wijaya Cinnamon Plants', 'Galle', 'Galle', 60, '/each', null, [],
                ['Item' => 'Cinnamon plant', 'Minimum order' => '100'],
                "Healthy plants from a registered nursery."],
            ['agriculture', 'Fresh Coconuts – Wholesale', 'Puttalam', 'Puttalam', 95, '/each', null, [],
                ['Item' => 'Coconut', 'Minimum order' => '500'],
                "Large size, direct from the estate. Transport can be arranged."],
            ['agriculture', 'Panniyur Pepper Vines', 'Kegalle', 'Kegalle', 120, '/each', null, [],
                ['Item' => 'Pepper vine', 'Minimum order' => '50'],
                "High-yield variety, rooted cuttings ready to plant."],
        ];
    }
}
