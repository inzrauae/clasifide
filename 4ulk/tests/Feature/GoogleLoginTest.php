<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'test-id', 'services.google.client_secret' => 'test-secret']);
    }

    private function fakeGoogle(string $id, string $email, bool $verified = true, string $name = 'Kamal Perera'): void
    {
        $user = (new GoogleUser)->setRaw(['email_verified' => $verified])->map(['id' => $id, 'name' => $name, 'email' => $email]);
        $driver = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $driver->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }

    public function test_new_google_user_gets_an_account(): void
    {
        $this->fakeGoogle('g-1', 'Kamal@Gmail.com');

        $this->get('/auth/google/callback?code=x')->assertRedirect('/dashboard');

        $user = User::where('email', 'kamal@gmail.com')->firstOrFail();
        $this->assertSame('g-1', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_account_with_same_verified_email_is_linked(): void
    {
        $existing = User::factory()->create(['email' => 'nimal@gmail.com']);
        $this->fakeGoogle('g-2', 'nimal@gmail.com');

        $this->get('/auth/google/callback?code=x')->assertRedirect('/dashboard');

        $this->assertSame('g-2', $existing->fresh()->google_id);
        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::count());
    }

    public function test_unverified_google_email_does_not_take_over_an_account(): void
    {
        User::factory()->create(['email' => 'sunil@gmail.com']);
        $this->fakeGoogle('g-3', 'sunil@gmail.com', verified: false);

        $this->get('/auth/google/callback?code=x')->assertRedirect('/login')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_banned_user_cannot_sign_in_with_google(): void
    {
        User::factory()->create(['email' => 'bad@gmail.com', 'google_id' => 'g-4'])->forceFill(['banned_at' => now()])->save();
        $this->fakeGoogle('g-4', 'bad@gmail.com');

        $this->get('/auth/google/callback?code=x')->assertRedirect('/login')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_sinhala_visitors_come_back_to_sinhala_pages(): void
    {
        $this->fakeGoogle('g-5', 'si@gmail.com');

        $this->withSession(['google_login_lang' => 'si'])->get('/auth/google/callback?code=x')->assertRedirect('/si/dashboard');
    }

    public function test_cancel_on_google_screen_goes_back_to_login(): void
    {
        $this->get('/auth/google/callback?error=access_denied')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_button_is_hidden_when_google_is_not_configured(): void
    {
        $this->get('/login')->assertSee('Continue with Google');

        config(['services.google.client_id' => null]);
        $this->get('/login')->assertDontSee('Continue with Google');
        $this->get('/auth/google')->assertNotFound();
    }
}
