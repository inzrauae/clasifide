<?php

return [

    'name' => env('APP_NAME', '4U.lk'),
    'tagline' => 'Everything. Just for you.',
    'support_phone' => env('SUPPORT_PHONE', '011 000 0000'),
    'support_email' => env('SUPPORT_EMAIL', 'hello@4u.lk'),

    // How long a free ad stays live
    'ad_lifetime_days' => 60,

    // Free accounts: how many ads can be active at once
    'free_active_ads' => 5,

    // New ads go live straight away unless the safety checks flag them.
    // Set to false to hold every ad for manual review.
    'auto_approve' => env('ADS_AUTO_APPROVE', true),

    'max_photos' => 6,

    // ---------------------------------------------------------------
    // Featured ads / promotions (pay per ad, or spend package credits)
    // 'options' = the lengths a seller can pick: days => price (LKR) + package credits.
    // 'days' / 'price' / 'credits' = the default choice (also used when an admin gives one free).
    // ---------------------------------------------------------------
    'promotions' => [
        'top' => [
            'name' => 'Top Ad',
            'blurb' => 'Pinned above normal ads in its category and search results.',
            'days' => 7,
            'price' => 750,
            'credits' => 2,
            'options' => [
                3 => ['price' => 400, 'credits' => 1],
                7 => ['price' => 750, 'credits' => 2],
                30 => ['price' => 1990, 'credits' => 5],
            ],
        ],
        'featured' => [
            'name' => 'Featured on Home',
            'blurb' => 'Shown in the Featured section on the home page.',
            'days' => 7,
            'price' => 450,
            'credits' => 2,
            'options' => [
                3 => ['price' => 250, 'credits' => 1],
                7 => ['price' => 450, 'credits' => 2],
                30 => ['price' => 990, 'credits' => 4],
            ],
        ],
        'urgent' => [
            'name' => 'Urgent',
            'blurb' => 'A red URGENT label that stands out in the list.',
            'days' => 7,
            'price' => 350,
            'credits' => 1,
            'options' => [
                3 => ['price' => 200, 'credits' => 1],
                7 => ['price' => 350, 'credits' => 1],
                30 => ['price' => 890, 'credits' => 3],
            ],
        ],
        'bump' => [
            'name' => 'Bump Up',
            'blurb' => 'Moves your ad back to the top of the latest ads, once.',
            'days' => 0,
            'price' => 200,
            'credits' => 1,
        ],
    ],

    // ---------------------------------------------------------------
    // Banner advertising for other businesses: Small / Medium / Premium, price per month (LKR)
    // ---------------------------------------------------------------
    'banner_max_months' => 12,
    'banner_placements' => [
        'home_top' => ['tier' => 'Premium', 'name' => 'Home page – top leaderboard', 'size' => '1200 × 200 px', 'price' => 12000, 'blurb' => 'The first thing every visitor sees. Maximum brand reach.', 'tag' => 'Best reach'],
        'home_middle' => ['tier' => 'Medium', 'name' => 'Home page – between sections', 'size' => '1200 × 200 px', 'price' => 6000, 'blurb' => 'A big, wide banner as visitors scroll the home page.', 'tag' => null],
        'search_top' => ['tier' => 'Medium', 'name' => 'Search results – top', 'size' => '1200 × 160 px', 'price' => 6000, 'blurb' => 'Above the results, in front of people ready to buy.', 'tag' => 'High intent'],
        'listing_sidebar' => ['tier' => 'Small', 'name' => 'Ad page – sidebar', 'size' => '600 × 500 px', 'price' => 3000, 'blurb' => 'Next to every ad buyers open. Great value for local businesses.', 'tag' => 'Best value'],
    ],

    // ---------------------------------------------------------------
    // Trust & safety
    // ---------------------------------------------------------------
    'safety' => [
        // Words that often appear in scams. An ad that contains them is held for review.
        'scam_phrases' => [
            'advance payment', 'pay in advance', 'western union', 'moneygram', 'send otp', 'share otp', 'otp code',
            'bitcoin', 'crypto', 'usdt', 'gift card', 'lottery', 'customs clearance fee', 'courier fee first',
            'whatsapp only no calls', 'deposit first', 'investment double',
        ],
        // A price this far below the category median (as a fraction) is suspicious
        'low_price_ratio' => 0.2,
        // Max ads a brand-new account (< 24h) can post before review kicks in
        'new_account_ad_limit' => 3,
        // Risk score at which an ad is held for review
        'review_threshold' => 40,
        // Messaging spam limits
        'messages_per_minute' => 6,
        'new_conversations_per_day' => 25,
    ],

    // ---------------------------------------------------------------
    // Payments
    // ---------------------------------------------------------------
    'payhere' => [
        'enabled' => env('PAYHERE_ENABLED', false),
        'sandbox' => env('PAYHERE_SANDBOX', true),
        'merchant_id' => env('PAYHERE_MERCHANT_ID'),
        'merchant_secret' => env('PAYHERE_MERCHANT_SECRET'),
    ],

    'bank' => [
        'enabled' => true,
        'account_name' => env('BANK_ACCOUNT_NAME', '4U.lk (Pvt) Ltd'),
        'bank_name' => env('BANK_NAME', 'Commercial Bank'),
        'branch' => env('BANK_BRANCH', 'Colombo'),
        'account_no' => env('BANK_ACCOUNT_NO', '0000000000'),
    ],

    // ---------------------------------------------------------------
    // Locations (centre point of each district for "near me")
    // ---------------------------------------------------------------
    'districts' => [
        'Colombo' => [6.9271, 79.8612],
        'Gampaha' => [7.0873, 79.9990],
        'Kalutara' => [6.5854, 79.9607],
        'Kandy' => [7.2906, 80.6337],
        'Matale' => [7.4675, 80.6234],
        'Nuwara Eliya' => [6.9497, 80.7891],
        'Galle' => [6.0535, 80.2210],
        'Matara' => [5.9549, 80.5550],
        'Hambantota' => [6.1241, 81.1185],
        'Jaffna' => [9.6615, 80.0255],
        'Kilinochchi' => [9.3803, 80.3770],
        'Mannar' => [8.9810, 79.9044],
        'Vavuniya' => [8.7514, 80.4971],
        'Mullaitivu' => [9.2671, 80.8142],
        'Batticaloa' => [7.7310, 81.6747],
        'Ampara' => [7.2975, 81.6820],
        'Trincomalee' => [8.5874, 81.2152],
        'Kurunegala' => [7.4863, 80.3647],
        'Puttalam' => [8.0362, 79.8283],
        'Anuradhapura' => [8.3114, 80.4037],
        'Polonnaruwa' => [7.9403, 81.0188],
        'Badulla' => [6.9934, 81.0550],
        'Monaragala' => [6.8728, 81.3507],
        'Ratnapura' => [6.6828, 80.3992],
        'Kegalle' => [7.2513, 80.3464],
    ],

    'cities' => [
        'Colombo' => ['Colombo 1-15', 'Nugegoda', 'Dehiwala', 'Mount Lavinia', 'Maharagama', 'Kottawa', 'Battaramulla', 'Rajagiriya', 'Kaduwela', 'Homagama', 'Moratuwa', 'Piliyandala', 'Kolonnawa', 'Malabe', 'Avissawella'],
        'Gampaha' => ['Negombo', 'Gampaha', 'Kadawatha', 'Kiribathgoda', 'Wattala', 'Ja-Ela', 'Kelaniya', 'Minuwangoda', 'Nittambuwa', 'Ragama', 'Katunayake', 'Divulapitiya'],
        'Kalutara' => ['Panadura', 'Kalutara', 'Horana', 'Beruwala', 'Wadduwa', 'Aluthgama', 'Matugama', 'Bandaragama'],
        'Kandy' => ['Kandy', 'Peradeniya', 'Katugastota', 'Gampola', 'Kundasale', 'Digana', 'Pilimathalawa', 'Akurana', 'Nawalapitiya'],
        'Matale' => ['Matale', 'Dambulla', 'Galewela', 'Ukuwela'],
        'Nuwara Eliya' => ['Nuwara Eliya', 'Hatton', 'Talawakele', 'Nanu Oya'],
        'Galle' => ['Galle', 'Karapitiya', 'Hikkaduwa', 'Ambalangoda', 'Unawatuna', 'Baddegama', 'Elpitiya'],
        'Matara' => ['Matara', 'Weligama', 'Akuressa', 'Dikwella', 'Hakmana'],
        'Hambantota' => ['Hambantota', 'Tangalle', 'Tissamaharama', 'Ambalantota', 'Beliatta'],
        'Jaffna' => ['Jaffna', 'Chavakachcheri', 'Point Pedro', 'Nallur'],
        'Kurunegala' => ['Kurunegala', 'Kuliyapitiya', 'Pannala', 'Narammala', 'Wariyapola', 'Polgahawela'],
        'Puttalam' => ['Puttalam', 'Chilaw', 'Wennappuwa', 'Marawila', 'Nattandiya'],
        'Anuradhapura' => ['Anuradhapura', 'Kekirawa', 'Medawachchiya', 'Tambuttegama'],
        'Polonnaruwa' => ['Polonnaruwa', 'Kaduruwela', 'Hingurakgoda', 'Medirigiriya'],
        'Badulla' => ['Badulla', 'Bandarawela', 'Ella', 'Haputale', 'Mahiyanganaya', 'Welimada'],
        'Ratnapura' => ['Ratnapura', 'Embilipitiya', 'Balangoda', 'Pelmadulla', 'Eheliyagoda'],
        'Kegalle' => ['Kegalle', 'Mawanella', 'Warakapola', 'Rambukkana'],
        'Batticaloa' => ['Batticaloa', 'Kattankudy', 'Eravur'],
        'Ampara' => ['Ampara', 'Kalmunai', 'Sainthamaruthu', 'Akkaraipattu'],
        'Trincomalee' => ['Trincomalee', 'Kinniya', 'Kantale'],
        'Monaragala' => ['Monaragala', 'Wellawaya', 'Bibile', 'Buttala'],
        'Vavuniya' => ['Vavuniya'],
        'Mannar' => ['Mannar'],
        'Kilinochchi' => ['Kilinochchi'],
        'Mullaitivu' => ['Mullaitivu'],
    ],

    'report_reasons' => [
        'fraud' => 'Fraud or scam',
        'sold' => 'Item sold / unavailable',
        'wrong_category' => 'Wrong category',
        'duplicate' => 'Duplicate ad',
        'offensive' => 'Offensive content',
        'wrong_price' => 'Fake or wrong price',
        'other' => 'Something else',
    ],

    'lead_statuses' => [
        'new' => 'New',
        'contacted' => 'Contacted',
        'negotiating' => 'Negotiating',
        'won' => 'Sold / won',
        'lost' => 'Lost',
    ],
];
