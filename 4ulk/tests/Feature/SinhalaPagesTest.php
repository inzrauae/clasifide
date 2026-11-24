<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sinhala pages under /si. */
class SinhalaPagesTest extends TestCase
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

    private function ad(User $u, array $attrs = []): Listing
    {
        return Listing::create(array_merge([
            'user_id' => $u->id, 'category_id' => Category::where('slug', 'vehicles')->value('id'),
            'title' => 'Toyota Aqua 2015 for sale', 'slug' => 'toyota-aqua-2015', 'description' => 'A perfectly normal description of an item.',
            'price' => 6450000, 'district' => 'Colombo', 'phone' => '0770000000', 'status' => 'active',
            'published_at' => now(), 'expires_at' => now()->addDays(30),
        ], $attrs));
    }

    public function test_sinhala_home_keeps_links_in_sinhala(): void
    {
        $this->get('/si')->assertOk()
            ->assertSee('<html lang="si">', false)
            ->assertSee('href="'.url('/').'/si/ads"', false)
            ->assertDontSee('href="'.url('/').'/ads"', false);
    }

    public function test_english_pages_are_unchanged(): void
    {
        $this->get('/si'); // a Sinhala request first must not leak into the next one
        $this->get('/')->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('href="'.url('/').'/ads"', false);
    }

    public function test_every_public_page_renders_in_sinhala(): void
    {
        $ad = $this->ad($this->user());
        foreach (['/si', '/si/ads', '/si/ads?cat=vehicles&loc=Kandy&q=car', '/si/ad/'.$ad->id.'/'.$ad->slug, '/si/pricing', '/si/advertise',
            '/si/safety', '/si/about', '/si/terms', '/si/privacy', '/si/login', '/si/register', '/si/forgot-password',
            '/si/seller/'.$ad->user_id] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_member_pages_render_in_sinhala(): void
    {
        $u = $this->user();
        $ad = $this->ad($u);
        foreach (['/si/dashboard', '/si/dashboard/ads', '/si/dashboard/categories', '/si/dashboard/categories/vehicles', '/si/dashboard/saved',
            '/si/dashboard/leads', '/si/dashboard/billing', '/si/dashboard/profile', '/si/dashboard/store', '/si/messages', '/si/post',
            '/si/ad/'.$ad->id.'/edit', '/si/ad/'.$ad->id.'/promote'] as $url) {
            $this->actingAs($u)->get($url)->assertOk();
        }
    }

    public function test_sinhala_login_redirects_stay_in_sinhala(): void
    {
        $this->get('/si/dashboard')->assertRedirect(url('/si/login'));

        $u = $this->user(['password' => 'secret-pass-1']);
        $this->post('/si/login', ['login' => $u->email, 'password' => 'secret-pass-1'])
            ->assertRedirect(url('/si/dashboard'));
    }

    public function test_validation_errors_go_back_to_the_sinhala_page(): void
    {
        $this->get('/si/login');
        $this->post('/si/login', ['login' => '', 'password' => ''])->assertRedirect(url('/si/login'));
    }

    public function test_admin_links_are_never_prefixed(): void
    {
        $admin = $this->user();
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin)->get('/si')->assertSee('href="'.url('/admin').'"', false);
    }

    public function test_language_switch_and_hreflang_point_at_the_same_page(): void
    {
        $this->get('/si/ads?q=car')
            ->assertSee('hreflang="en" href="'.url('/ads?q=car').'"', false)
            ->assertSee('hreflang="si" href="'.url('/si/ads?q=car').'"', false);

        $this->get('/ads?q=car')->assertSee('hreflang="si" href="'.url('/si/ads?q=car').'"', false);
    }

    public function test_pages_are_actually_in_sinhala(): void
    {
        $ad = $this->ad($this->user());

        $this->get('/si')->assertSee('Category අනුව බලන්න')->assertSee('Ad එකක් දාන්න')->assertSee('වාහන');
        $this->get('/si/ads?loc=Kandy')->assertSee('මහනුවර වල ඔක්කොම ads');
        $this->get($this->siUrl($ad->url()))->assertSee('Rs 6,450,000')->assertSee('කොළඹ')->assertSee('Safety tips');
        $this->get('/si/safety')->assertSee('බඩු ගන්නකොට');      // hand-written Sinhala page
        $this->get('/safety')->assertSee('When buying')->assertDontSee('බඩු ගන්නකොට');
        $this->get('/')->assertSee('Browse by category')->assertDontSee('Category අනුව බලන්න');
    }

    public function test_validation_and_flash_messages_are_in_sinhala(): void
    {
        $this->get('/si/register');
        $this->post('/si/register', ['name' => ''])->assertSessionHasErrors(['name' => 'නම දාන්න ඕන.']);

        $u = $this->user(['password' => 'secret-pass-1']);
        $this->post('/si/login', ['login' => $u->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['login' => 'ඒ details වලට ගැළපෙන account එකක් නෑ.']);

        $this->actingAs($u)->put('/si/dashboard/profile', ['name' => $u->name, 'email' => $u->email, 'phone' => $u->phone])
            ->assertSessionHas('status', 'Profile එක save කළා.');
    }

    public function test_category_names_are_translated_only_on_sinhala_pages(): void
    {
        $this->get('/si/ads?cat=vehicles')->assertSee('ලංකාව වල වාහන');
        $this->get('/ads?cat=vehicles')->assertSee('Vehicles in Sri Lanka');
        $this->assertSame('Vehicles', Category::where('slug', 'vehicles')->first()->name); // stored data stays English
    }

    private function siUrl(string $url): string
    {
        return str_replace(url('/'), url('/si'), $url);
    }

    public function test_paths_that_only_start_with_si_are_not_treated_as_sinhala(): void
    {
        $u = $this->user();
        $this->get('/seller/'.$u->id)->assertOk()->assertSee('<html lang="en">', false);
    }
}
