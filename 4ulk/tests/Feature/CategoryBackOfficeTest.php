<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use App\Support\CategoryInsights;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Per-category back offices: /admin/categories/{type} and /dashboard/categories/{type}. */
class CategoryBackOfficeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([CategorySeeder::class, PackageSeeder::class]);
    }

    private function user(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['phone' => '07'.random_int(10000000, 99999999), 'district' => 'Colombo'], $attrs));
    }

    private function admin(): User
    {
        $u = $this->user();
        $u->forceFill(['role' => 'admin'])->save();

        return $u;
    }

    private function ad(User $u, string $cat, array $attrs = []): Listing
    {
        return Listing::create(array_merge([
            'user_id' => $u->id, 'category_id' => Category::where('slug', $cat)->value('id'),
            'title' => 'Test ad '.uniqid(), 'slug' => 'test-ad', 'description' => 'A perfectly normal description of an item.',
            'price' => 1000, 'district' => 'Colombo', 'phone' => '0770000000', 'status' => 'active',
            'published_at' => now(), 'expires_at' => now()->addDays(30),
        ], $attrs));
    }

    public function test_admin_pages_render_for_every_category(): void
    {
        $admin = $this->admin();
        $this->ad($this->user(), 'vehicles', ['specs' => ['Brand' => 'Toyota', 'Fuel type' => 'Hybrid']]);

        $this->actingAs($admin)->get('/admin/categories')->assertOk()->assertSee('Vehicles')->assertSee('Property');

        foreach (Category::all() as $c) {
            $this->actingAs($admin)->get(route('admin.categories.show', $c))->assertOk()->assertSee($c->name);
            $this->actingAs($admin)->get(route('admin.categories.ads', [$c, 'status' => 'all']))->assertOk();
            $this->actingAs($admin)->get(route('admin.categories.edit', $c))->assertOk();
        }
    }

    public function test_admin_category_pages_are_admin_only(): void
    {
        $this->get('/admin/categories/vehicles')->assertRedirect('/login');
        $this->actingAs($this->user())->get('/admin/categories/vehicles')->assertForbidden();
    }

    public function test_admin_ads_queue_is_scoped_to_the_category_and_filters_by_spec(): void
    {
        $seller = $this->user();
        $hybrid = $this->ad($seller, 'vehicles', ['title' => 'Toyota Aqua hybrid car', 'status' => 'pending', 'specs' => ['Fuel type' => 'Hybrid']]);
        $petrol = $this->ad($seller, 'vehicles', ['title' => 'Suzuki Alto petrol car', 'status' => 'pending', 'specs' => ['Fuel type' => 'Petrol']]);
        $house = $this->ad($seller, 'property', ['title' => 'House for sale in Kandy', 'status' => 'pending']);

        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/categories/vehicles/ads')
            ->assertSee($hybrid->title)->assertSee($petrol->title)->assertDontSee($house->title);

        $this->actingAs($admin)->get('/admin/categories/vehicles/ads?spec[Fuel%20type]=Hybrid')
            ->assertSee($hybrid->title)->assertDontSee($petrol->title);

        // Unknown spec names are ignored rather than breaking the query
        $this->actingAs($admin)->get('/admin/categories/vehicles/ads?spec[Nope]=x')->assertOk()->assertSee($petrol->title);
    }

    public function test_admin_overview_counts_and_breakdown(): void
    {
        $seller = $this->user();
        $vehicles = Category::where('slug', 'vehicles')->first();
        foreach ([1000000, 2000000, 3000000] as $price) {
            $this->ad($seller, 'vehicles', ['price' => $price, 'specs' => ['Brand' => 'toyota', 'Fuel type' => 'Hybrid']]);
        }
        $this->ad($seller, 'vehicles', ['status' => 'pending', 'is_flagged' => true, 'risk_score' => 60]);

        $prices = CategoryInsights::prices($vehicles);
        $this->assertSame(2000000, $prices['median']);
        $this->assertSame(3, $prices['count']);

        $specs = CategoryInsights::specBreakdown($vehicles);
        $this->assertSame(['Hybrid' => 3], $specs['Fuel type']);
        $this->assertSame(['Toyota' => 3], $specs['Brand']); // free text is grouped case-insensitively

        $this->actingAs($this->admin())->get('/admin/categories/vehicles')
            ->assertOk()->assertSee('Rs 2,000,000')->assertSee('Toyota');
    }

    public function test_fee_revenue_only_counts_this_categorys_ads(): void
    {
        $seller = $this->user();
        $car = $this->ad($seller, 'vehicles');
        $house = $this->ad($seller, 'property');
        foreach ([[$car, 500], [$house, 750]] as [$ad, $amount]) {
            Order::create(['user_id' => $seller->id, 'type' => 'posting_fee', 'description' => 'Fee', 'amount' => $amount,
                'status' => 'paid', 'paid_at' => now(), 'meta' => ['listing_id' => $ad->id]]);
        }

        $revenue = CategoryInsights::feeRevenue(Category::where('slug', 'vehicles')->first());
        $this->assertSame(500, $revenue['total']);
        $this->assertSame(1, $revenue['month_count']);
    }

    public function test_admin_can_edit_category_settings_and_spec_fields(): void
    {
        $admin = $this->admin();
        $jobs = Category::where('slug', 'jobs')->first();

        $this->actingAs($admin)->put(route('admin.categories.update', $jobs), [
            'name' => 'Jobs & Careers', 'icon' => 'briefcase', 'hint' => 'Work across Sri Lanka',
            'posting_fee' => 300, 'free_ads_per_month' => 2, 'sort' => 6, 'is_active' => '1',
            'fields' => [
                ['name' => '  Job type ', 'type' => 'select', 'options' => "Full-time\nPart-time, Contract\n\nFull-time"],
                ['name' => 'Salary', 'type' => 'number'],
            ],
        ])->assertRedirect(route('admin.categories.edit', $jobs))->assertSessionHasNoErrors();

        $jobs->refresh();
        $this->assertSame('Jobs & Careers', $jobs->name);
        $this->assertSame(300, (int) $jobs->posting_fee);
        $this->assertFalse($jobs->has_condition);
        $this->assertSame([
            ['name' => 'Job type', 'type' => 'select', 'options' => ['Full-time', 'Part-time', 'Contract']],
            ['name' => 'Salary', 'type' => 'number'],
        ], $jobs->fields);
        $this->assertSame([], $jobs->specFields()[1]['options']);
    }

    public function test_category_settings_validation(): void
    {
        $admin = $this->admin();
        $jobs = Category::where('slug', 'jobs')->first();
        $base = ['name' => 'Jobs', 'icon' => 'briefcase', 'posting_fee' => 0, 'free_ads_per_month' => 0, 'sort' => 1];

        $this->actingAs($admin)->put(route('admin.categories.update', $jobs), $base + ['fields' => [
            ['name' => 'Salary', 'type' => 'text'], ['name' => 'salary', 'type' => 'text'],
        ]])->assertSessionHasErrors('fields.1.name');

        $this->actingAs($admin)->put(route('admin.categories.update', $jobs), $base + ['fields' => [
            ['name' => 'Job type', 'type' => 'select', 'options' => ' , '],
        ]])->assertSessionHasErrors('fields');

        $this->actingAs($admin)->put(route('admin.categories.update', $jobs), ['icon' => '<script>'] + $base)
            ->assertSessionHasErrors('icon');

        $this->assertSame('Jobs', $jobs->fresh()->name);
        $this->assertNotEmpty($jobs->fresh()->fields); // untouched
    }

    public function test_seller_category_pages(): void
    {
        $this->get('/dashboard/categories/vehicles')->assertRedirect('/login');

        $seller = $this->user();
        $other = $this->user();
        $mine = $this->ad($seller, 'vehicles', ['title' => 'My Honda Vezel', 'specs' => ['Brand' => 'Honda', 'Model' => 'Vezel']]);
        $sold = $this->ad($seller, 'vehicles', ['title' => 'My old Alto', 'status' => 'sold']);
        $theirs = $this->ad($other, 'vehicles', ['title' => 'Someone elses Prius']);
        $tv = $this->ad($seller, 'electronics', ['title' => 'My Sony TV']);

        $this->actingAs($seller)->get('/dashboard/categories')->assertOk()->assertSee('Vehicles')->assertSee('By category');

        $this->actingAs($seller)->get('/dashboard/categories/vehicles')->assertOk()
            ->assertSee($mine->title)->assertSee('Honda')->assertSee('Vezel')
            ->assertDontSee($theirs->title)->assertDontSee($tv->title)->assertDontSee($sold->title)
            ->assertSee('Posting fee this month');

        $this->actingAs($seller)->get('/dashboard/categories/vehicles?tab=sold')->assertSee($sold->title)->assertDontSee($mine->title);
        $this->actingAs($seller)->get('/dashboard/categories/vehicles?tab=bogus')->assertOk()->assertSee($mine->title);

        // Every active category renders, even with no ads
        foreach (Category::all() as $c) {
            $this->actingAs($seller)->get(route('dashboard.categories.show', $c))->assertOk();
        }
    }

    public function test_hidden_category_page_only_for_sellers_with_ads_there(): void
    {
        $animals = Category::where('slug', 'animals')->first();
        $animals->update(['is_active' => false]);
        $withAd = $this->user();
        $this->ad($withAd, 'animals', ['title' => 'Golden retriever puppy']);

        $this->actingAs($this->user())->get('/dashboard/categories/animals')->assertNotFound();
        $this->actingAs($withAd)->get('/dashboard/categories/animals')->assertOk()->assertSee('Golden retriever puppy');
    }

    public function test_fee_status_matches_what_posting_charges(): void
    {
        $seller = $this->user();
        $vehicles = Category::where('slug', 'vehicles')->first(); // Rs 500, 1 free per month

        $this->assertSame(0, CategoryInsights::feeStatus($vehicles, $seller)['next_costs']);
        $this->ad($seller, 'vehicles');
        $status = CategoryInsights::feeStatus($vehicles, $seller);
        $this->assertSame(500, $status['next_costs']);
        $this->assertSame(0, $status['free_left']);

        $this->assertNull(CategoryInsights::feeStatus(Category::where('slug', 'electronics')->first(), $seller));
    }

    public function test_post_form_preselects_category_from_query(): void
    {
        $this->actingAs($this->user())->get('/post?category=property')->assertOk()
            ->assertSee("cat: '".Category::where('slug', 'property')->value('id')."'", false);
    }
}
