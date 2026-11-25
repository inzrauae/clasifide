<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([CategorySeeder::class, PackageSeeder::class]);
    }

    private function seller(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['phone' => '07'.random_int(10000000, 99999999), 'district' => 'Colombo'], $attrs));
    }

    private function admin(): User
    {
        $u = $this->seller();
        $u->forceFill(['role' => 'admin'])->save();

        return $u;
    }

    private function adData(string $cat = 'electronics', array $override = []): array
    {
        return array_merge([
            'category_id' => Category::where('slug', $cat)->value('id'),
            'title' => 'Sony Bravia 43 inch smart TV',
            'description' => 'Excellent condition, two years old, with remote and wall bracket.',
            'price' => 85000,
            'condition' => 'Used',
            'district' => 'Colombo',
            'city' => 'Nugegoda',
            'contact_name' => 'Test Seller',
            'phone' => '0771234567',
            'specs' => ['Brand' => 'Sony'],
        ], $override);
    }

    private function live(User $u, array $attrs = []): Listing
    {
        return Listing::create(array_merge([
            'user_id' => $u->id, 'category_id' => Category::where('slug', 'electronics')->value('id'),
            'title' => 'Live test ad '.uniqid(), 'slug' => 'live-test-ad', 'description' => 'A perfectly normal description of an item.',
            'price' => 1000, 'district' => 'Colombo', 'phone' => '0770000000', 'status' => 'active',
            'published_at' => now(), 'expires_at' => now()->addDays(30),
        ], $attrs));
    }

    public function test_public_pages_render(): void
    {
        $ad = $this->live($this->seller());
        foreach (['/', '/ads', '/ads?cat=vehicles&loc=Kandy&q=car', $ad->url(), '/pricing', '/advertise', '/safety', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_search_suggestions(): void
    {
        $u = $this->seller();
        $this->live($u, ['title' => 'Toyota Aqua 2015 hybrid']);
        $this->live($u, ['title' => 'Toyota Axio for sale', 'status' => 'sold']);
        $this->live($u, ['title' => 'Honda Vezel 2017']);

        $r = $this->getJson('/search/suggest?q=toyota')->assertOk();
        $this->assertSame(['Toyota Aqua 2015 hybrid'], array_column($r->json('ads'), 'title')); // sold ads are left out

        $this->getJson('/search/suggest?q=elect')->assertOk()->assertJsonPath('categories.0.name', 'Electronics');
        $this->getJson('/search/suggest?q=t')->assertOk()->assertExactJson(['categories' => [], 'ads' => []]);
        $this->getJson('/search/suggest?q=100%25')->assertOk()->assertJsonCount(0, 'ads'); // % is not a wildcard
    }

    public function test_register_and_login_with_phone(): void
    {
        $this->post('/register', [
            'name' => 'Kamal Perera', 'email' => 'kamal@example.com', 'phone' => '+94 77 123 4567',
            'district' => 'Kandy', 'account_type' => 'individual',
            'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', ['email' => 'kamal@example.com', 'phone' => '0771234567']);
        auth()->logout();

        $this->post('/login', ['login' => '077 123 4567', 'password' => 'secret123'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_free_category_ad_goes_live_with_photo(): void
    {
        $u = $this->seller();
        $this->actingAs($u)->post('/post', $this->adData('electronics', ['photos' => [UploadedFile::fake()->image('tv.jpg', 1600, 1200)]]))
            ->assertRedirect();

        $ad = Listing::first();
        $this->assertSame('active', $ad->status);
        $this->assertCount(1, $ad->images);
        $this->assertFileExists(public_path($ad->images->first()->path));
        @unlink(public_path($ad->images->first()->path));
    }

    public function test_duplicate_ad_is_blocked(): void
    {
        $u = $this->seller();
        $this->actingAs($u)->post('/post', $this->adData());
        $this->actingAs($u)->post('/post', $this->adData('electronics', ['title' => 'SONY bravia 43 inch  smart TV!']))->assertSessionHasErrors('title');
        $this->assertSame(1, Listing::count());
    }

    public function test_scam_phrases_hold_ad_for_review(): void
    {
        $u = $this->seller();
        $this->actingAs($u)->post('/post', $this->adData('electronics', ['description' => 'Brand new phone. Advance payment required before courier dispatch.']));
        $ad = Listing::first();
        $this->assertSame('pending', $ad->status);
        $this->assertTrue($ad->is_flagged);
        $this->assertStringContainsString('advance payment', $ad->flag_reason);
    }

    public function test_vehicle_ads_charge_premium_fee_after_free_quota(): void
    {
        $u = $this->seller();
        $vehicle = fn ($title, $phone) => $this->adData('vehicles', ['title' => $title, 'phone' => $phone]);

        // First vehicle ad of the month is free
        $this->actingAs($u)->post('/post', $vehicle('Toyota Vitz 2016 for sale', '0771111111'));
        $this->assertSame('active', Listing::latest('id')->first()->status);

        // Second one needs the fee
        $this->actingAs($u)->post('/post', $vehicle('Honda Fit GP5 2015 for sale', '0772222222'))->assertRedirect();
        $ad = Listing::latest('id')->first();
        $this->assertSame('pending_payment', $ad->status);
        $order = Order::where('type', 'posting_fee')->first();
        $this->assertSame(500, $order->amount);

        // Admin confirms the bank slip -> ad goes live
        $this->actingAs($this->admin())->post(route('admin.orders.approve', $order))->assertRedirect();
        $this->assertSame('active', $ad->fresh()->status);
    }

    public function test_package_purchase_gives_credits_and_promotion_uses_them(): void
    {
        $u = $this->seller();
        $business = Package::where('slug', 'business')->first();

        $this->actingAs($u)->post(route('pricing.subscribe', $business))->assertRedirect();
        $order = Order::where('type', 'subscription')->first();
        $this->assertSame(1990, $order->amount);

        $this->actingAs($this->admin())->post(route('admin.orders.approve', $order));
        $u->refresh();
        $this->assertSame('Business', $u->activeSubscription->package->name);
        $this->assertSame(15, $u->activeSubscription->promotions_left);
        $this->assertTrue($u->hasAnalytics());

        $ad = $this->live($u);
        $this->actingAs($u)->post(route('promote.store', $ad), ['type' => 'top', 'pay_with' => 'credit'])->assertRedirect();
        $this->assertTrue($ad->fresh()->isTop());
        $this->assertSame(13, $u->fresh()->activeSubscription->promotions_left);

        // Approving the same order twice must not double-deliver
        $this->actingAs($this->admin())->post(route('admin.orders.approve', $order));
        $this->assertSame(1, $u->subscriptions()->count());
    }

    public function test_payhere_notify_verifies_signature_and_pays(): void
    {
        config(['marketplace.payhere' => ['enabled' => true, 'sandbox' => true, 'merchant_id' => '1211149', 'merchant_secret' => 'secret']]);
        $u = $this->seller();
        $ad = $this->live($u);
        $order = Order::create(['user_id' => $u->id, 'type' => 'promotion', 'description' => 'Urgent', 'amount' => 350, 'meta' => ['listing_id' => $ad->id, 'promotion' => 'urgent']]);

        $payload = ['merchant_id' => '1211149', 'order_id' => $order->reference, 'payhere_amount' => '350.00', 'payhere_currency' => 'LKR', 'status_code' => '2'];
        $sig = strtoupper(md5('1211149'.$order->reference.'350.00LKR2'.strtoupper(md5('secret'))));

        $this->post('/payhere/notify', $payload + ['md5sig' => 'WRONG'])->assertStatus(400);
        $this->assertFalse($order->fresh()->isPaid());

        $this->post('/payhere/notify', $payload + ['md5sig' => $sig])->assertOk();
        $this->assertTrue($order->fresh()->isPaid());
        $this->assertTrue($ad->fresh()->isUrgent());
    }

    public function test_messaging_leads_and_spam_limits(): void
    {
        $seller = $this->seller();
        $buyer = $this->seller();
        $ad = $this->live($seller);

        $this->actingAs($buyer)->post(route('messages.start', $ad), ['body' => 'Is this still available?'])->assertRedirect();
        $conv = Conversation::first();
        $this->assertSame(1, $conv->seller_unread);
        $this->assertSame(1, $ad->fresh()->messages_count);

        $this->actingAs($seller)->get(route('messages.show', $conv))->assertOk();
        $this->assertSame(0, $conv->fresh()->seller_unread);
        $this->actingAs($seller)->post(route('messages.reply', $conv), ['body' => 'Yes, come tomorrow.']);
        $this->assertSame('contacted', $conv->fresh()->lead_status);

        // Strangers cannot read the chat
        $this->actingAs($this->seller())->get(route('messages.show', $conv))->assertForbidden();

        // Scam-looking messages are flagged for the reader
        $this->actingAs($buyer)->post(route('messages.reply', $conv), ['body' => 'Pay via bit.ly/xyz first']);
        $this->assertTrue($conv->messages()->reorder('id', 'desc')->first()->is_flagged);

        // Rate limit
        for ($i = 0; $i < 8; $i++) {
            $this->actingAs($buyer)->post(route('messages.reply', $conv), ['body' => "msg {$i}"]);
        }
        $this->assertLessThanOrEqual(config('marketplace.safety.messages_per_minute') + 1, $conv->messages()->where('sender_id', $buyer->id)->count());
    }

    public function test_contact_tracking_feeds_dashboard(): void
    {
        $seller = $this->seller();
        $ad = $this->live($seller);

        $this->get($ad->url())->assertOk();
        $this->postJson(route('listings.phone', $ad))->assertJson(['phone' => '0770000000']);
        $this->get(route('listings.whatsapp', $ad))->assertRedirectContains('wa.me/94770000000');

        $ad->refresh();
        $this->assertSame(1, $ad->views_count);
        $this->assertSame(1, $ad->phone_reveals);
        $this->assertSame(1, $ad->whatsapp_clicks);

        $this->actingAs($seller)->get('/dashboard')->assertOk()->assertSee('Buyer contacts');
        foreach (['/dashboard/ads', '/dashboard/saved', '/dashboard/leads', '/dashboard/billing', '/dashboard/profile', '/dashboard/store', '/messages', route('promote.show', $ad), route('listings.edit', $ad)] as $url) {
            $this->actingAs($seller)->get($url)->assertOk();
        }
    }

    public function test_three_fraud_reports_pull_the_ad(): void
    {
        $ad = $this->live($this->seller());
        foreach (['1.1.1.1', '2.2.2.2', '3.3.3.3'] as $ip) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('listings.report', $ad), ['reason' => 'fraud']);
        }
        $this->assertSame('pending', $ad->fresh()->status);
        $this->assertTrue($ad->fresh()->is_flagged);
    }

    public function test_reviews_require_a_conversation(): void
    {
        $seller = $this->seller();
        $buyer = $this->seller();
        $this->actingAs($buyer)->post(route('reviews.store', $seller), ['rating' => 5])->assertSessionHas('error');

        $ad = $this->live($seller);
        $this->actingAs($buyer)->post(route('messages.start', $ad), ['body' => 'Hello there']);
        $this->actingAs($buyer)->post(route('reviews.store', $seller), ['rating' => 4, 'comment' => 'Good'])->assertSessionHas('status');
        $this->assertSame(4.0, $seller->ratingAverage());
    }

    public function test_store_requires_package(): void
    {
        $u = $this->seller();
        $this->actingAs($u)->post('/dashboard/store', ['name' => 'My Shop'])->assertForbidden();
    }

    public function test_web_installer_is_locked_without_key(): void
    {
        config(['app.install_key' => null]);
        $this->get('/install/anything')->assertNotFound();

        config(['app.install_key' => 'secret-key-123']);
        $this->get('/install/wrong')->assertNotFound();

        $this->seller(); // existing users -> seeding is skipped
        $this->get('/install/secret-key-123')->assertOk()->assertSee('seeding skipped')->assertSee('DONE');
    }

    public function test_missing_tables_show_setup_help_instead_of_500(): void
    {
        config(['app.debug' => false]);
        \Illuminate\Support\Facades\Schema::drop('listings');

        $this->get('/')->assertStatus(503)->assertSee('the database is empty');
    }

    public function test_admin_area(): void
    {
        $this->actingAs($this->seller())->get('/admin')->assertForbidden();

        $admin = $this->admin();
        $this->live($this->seller(), ['status' => 'pending', 'is_flagged' => true, 'risk_score' => 50]);
        foreach (['/admin', '/admin/listings', '/admin/listings?status=flagged', '/admin/reports', '/admin/users', '/admin/orders', '/admin/banners', '/admin/packages', '/admin/categories'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $bad = $this->seller();
        $this->live($bad);
        $this->actingAs($admin)->post(route('admin.users.ban', $bad), ['reason' => 'Scammer']);
        $this->assertTrue($bad->fresh()->isBanned());
        $this->assertSame('rejected', $bad->listings()->first()->status);

        // Banned users are signed out on their next request
        $this->actingAs($bad->fresh())->get('/dashboard')->assertRedirect('/login');
    }
}
