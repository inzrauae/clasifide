<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Featured ads by length (3 / 7 / 30 days) and Small / Medium / Premium monthly banners. */
class PricingOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([CategorySeeder::class, PackageSeeder::class]);
    }

    private function adFor(User $u): Listing
    {
        return Listing::create([
            'user_id' => $u->id, 'category_id' => Category::where('slug', 'electronics')->value('id'),
            'title' => 'Promo test ad', 'slug' => 'promo-test-ad', 'description' => 'A perfectly normal description of an item.',
            'price' => 1000, 'district' => 'Colombo', 'phone' => '0770000000', 'status' => 'active',
            'published_at' => now(), 'expires_at' => now()->addDays(60),
        ]);
    }

    public function test_featured_ad_prices_follow_the_plan(): void
    {
        $u = User::factory()->create();
        $ad = $this->adFor($u);

        foreach ([3 => 250, 7 => 450, 30 => 990] as $days => $price) {
            $this->actingAs($u)->post(route('promote.store', $ad), ['type' => 'featured', 'days' => $days, 'pay_with' => 'pay'])->assertRedirect();
            $order = Order::latest('id')->first();
            $this->assertSame($price, $order->amount, "$days days");
            $this->assertSame($days, $order->meta['days']);
        }
    }

    public function test_paid_order_gives_the_length_that_was_bought(): void
    {
        $u = User::factory()->create();
        $ad = $this->adFor($u);

        $this->actingAs($u)->post(route('promote.store', $ad), ['type' => 'featured', 'days' => 30, 'pay_with' => 'pay']);
        OrderService::markPaid(Order::latest('id')->first(), 'bank');

        $until = $ad->fresh()->featured_until;
        $this->assertTrue($until->between(now()->addDays(29), now()->addDays(31)));
    }

    public function test_credits_charged_by_length(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->post(route('pricing.subscribe', Package::where('slug', 'business')->first()));
        OrderService::markPaid(Order::where('type', 'subscription')->first(), 'bank');
        $ad = $this->adFor($u);

        $this->actingAs($u)->post(route('promote.store', $ad), ['type' => 'featured', 'days' => 3, 'pay_with' => 'credit']);
        $this->assertSame(14, $u->fresh()->activeSubscription->promotions_left);
        $this->assertTrue($ad->fresh()->featured_until->between(now()->addDays(2), now()->addDays(4)));
    }

    public function test_length_that_is_not_offered_is_refused(): void
    {
        $u = User::factory()->create();
        $ad = $this->adFor($u);

        $this->actingAs($u)->post(route('promote.store', $ad), ['type' => 'featured', 'days' => 1, 'pay_with' => 'pay'])->assertSessionHas('error');
        $this->assertSame(0, Order::count());

        // No length given: the default (7 days) as before
        $this->actingAs($u)->post(route('promote.store', $ad), ['type' => 'featured', 'pay_with' => 'pay']);
        $this->assertSame(450, Order::first()->amount);
    }

    public function test_banners_are_small_medium_premium_per_month(): void
    {
        $this->assertSame(
            ['home_top' => ['Premium', 12000], 'home_middle' => ['Medium', 6000], 'search_top' => ['Medium', 6000], 'listing_sidebar' => ['Small', 3000]],
            collect(config('marketplace.banner_placements'))->map(fn ($p) => [$p['tier'], $p['price']])->all()
        );

        $u = User::factory()->create();
        $this->actingAs($u)->post(route('advertise.store'), [
            'advertiser' => 'Lanka Motors', 'contact_email' => 'ads@example.com', 'contact_phone' => '0771234567',
            'placement' => 'home_top', 'months' => 3, 'starts_at' => now()->addDay()->toDateString(),
        ])->assertRedirect();

        $banner = Banner::firstOrFail();
        $this->assertSame(36000, $banner->price);
        $this->assertSame(3, $banner->months);
        $this->assertSame(now()->addDay()->addMonthsNoOverflow(3)->subDay()->toDateString(), $banner->ends_at->toDateString());
        $this->assertSame(36000, Order::where('type', 'banner')->first()->amount);

        $this->get('/advertise')->assertOk()->assertSee('12,000')->assertSee('3,000');
        $this->get('/pricing')->assertOk()->assertSee('Rs 250')->assertSee('Rs 990');
    }
}
