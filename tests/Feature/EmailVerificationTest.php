<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->withPersonalTeam()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_can_not_verified_with_invalid_hash(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_get_request_renders_confirmation_button_and_does_not_consume_token_for_safe_links(): void
    {
        $token = 'test-token-1234567890';
        $user = User::factory()->unverified()->create([
            'verification_token' => $token,
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // 1. First GET request (e.g. Safe Links / Spam filter pre-fetch)
        $response1 = $this->get(route('auth.verify', ['token' => $token]));
        $response1->assertStatus(200);
        $response1->assertSee('Activate Pilot Account');

        // Token MUST remain unconsumed and user still unverified
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertEquals($token, $user->fresh()->verification_token);

        // 2. Second GET request (Human opens link in browser)
        $response2 = $this->get(route('auth.verify', ['token' => $token]));
        $response2->assertStatus(200);
        $response2->assertSee('Activate Pilot Account');
        $this->assertNull($user->fresh()->email_verified_at);

        // 3. Human clicks the button (POST request)
        $postResponse = $this->post(route('auth.verify.confirm'), [
            'token' => $token,
        ]);

        $postResponse->assertRedirect(route('onboarding.select-airline'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->verification_token);
        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_token_shows_expired_state(): void
    {
        $token = 'expired-token-123';
        $user = User::factory()->unverified()->create([
            'verification_token' => $token,
            'verification_token_expires_at' => now()->subHour(),
        ]);

        $response = $this->get(route('auth.verify', ['token' => $token]));
        $response->assertStatus(200);
        $response->assertSee('Link Expired');
    }
}
