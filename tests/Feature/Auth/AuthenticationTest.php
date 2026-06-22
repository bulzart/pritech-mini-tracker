<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in');
    }

    public function test_demo_owner_can_log_in(): void
    {
        $owner = User::factory()->create([
            'name' => 'Demo Owner',
            'email' => 'owner@example.com',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('projects.index'));
        $this->assertAuthenticatedAs($owner);
    }

    public function test_demo_member_can_log_in(): void
    {
        $member = User::factory()->create([
            'name' => 'Demo Member',
            'email' => 'member@example.com',
        ]);

        $this->post(route('login.store'), [
            'email' => 'member@example.com',
            'password' => 'password',
        ])->assertRedirect(route('projects.index'));

        $this->assertAuthenticatedAs($member);
    }

    public function test_logout_works(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_invalid_login_fails_and_stays_a_guest(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'owner@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_error_does_not_reveal_whether_the_account_exists(): void
    {
        // An unknown email and a known email with a wrong password must produce
        // the same generic message (enumeration prevention).
        User::factory()->create(['email' => 'known@example.com']);

        $unknown = $this->post(route('login.store'), [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ]);
        $wrongPassword = $this->post(route('login.store'), [
            'email' => 'known@example.com',
            'password' => 'wrong-password',
        ]);

        $message = 'These credentials do not match our records.';
        $unknown->assertSessionHasErrors(['email' => $message]);
        $wrongPassword->assertSessionHasErrors(['email' => $message]);
    }

    public function test_authenticated_user_visiting_login_is_redirected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('projects.index'));
    }

    public function test_login_form_is_prefilled_in_demo_mode(): void
    {
        config(['app.demo_mode' => true]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('value="owner@example.com"', false)
            ->assertSee('Demo credentials are prefilled in demo mode.');
    }

    public function test_login_form_is_not_prefilled_when_demo_mode_is_off(): void
    {
        config(['app.demo_mode' => false]);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('value="owner@example.com"', false)
            ->assertDontSee('Demo credentials are prefilled in demo mode.');
    }
}
