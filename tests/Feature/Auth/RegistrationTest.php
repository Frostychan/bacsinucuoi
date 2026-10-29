<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('dashboard'))
            ->assertRedirect(route('patient.dashboard'));
        $this->get(route('patient.dashboard'))->assertOk();
    }

    public function test_legacy_user_role_can_follow_the_register_link_without_a_forbidden_response(): void
    {
        $legacyUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($legacyUser)
            ->followingRedirects()
            ->get('/register');

        $response->assertOk();
        $response->assertSee('Đặt lịch khám');
    }

    public function test_registration_is_rejected_when_the_name_is_already_in_use(): void
    {
        User::factory()->create(['name' => 'aaa']);

        $response = $this->from('/register')->post('/register', [
            'name' => 'aaa',
            'email' => 'another@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'Họ và tên này đã được sử dụng. Vui lòng chọn tên khác.',
        ]);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }
}
