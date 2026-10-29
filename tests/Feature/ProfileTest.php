<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('Thông tin khách hàng');
        $response->assertSee('Người liên hệ khẩn cấp');
    }

    public function test_customer_header_has_requested_navigation_and_links_their_name_to_profile(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Khách hàng MedCare']);

        $response = $this->actingAs($customer)->get(route('patient.dashboard'));

        $navigationStart = strpos($response->getContent(), '<nav class="topnav"');
        $this->assertNotFalse($navigationStart);

        $navigation = substr($response->getContent(), $navigationStart, strpos($response->getContent(), '</nav>', $navigationStart) - $navigationStart);
        preg_match_all('/<a href="([^"]+)" class="nav-link/', $navigation, $links);

        $this->assertSame([
            route('dashboard'),
            route('catalog.specialties'),
            route('catalog.doctors'),
            route('patient.appointments.create'),
            route('patient.appointments.index'),
            route('catalog.services'),
        ], $links[1]);
        $response->assertSee('customer-profile-link', false);
        $response->assertSee('href="'.route('profile.edit').'"', false);
        $response->assertSee('Khách hàng MedCare</a>', false);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_name_cannot_be_changed_to_another_users_name(): void
    {
        $user = User::factory()->create(['name' => 'Current Name']);
        User::factory()->create(['name' => 'Taken Name']);

        $response = $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => 'Taken Name',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors([
            'name' => 'Họ và tên này đã được sử dụng. Vui lòng chọn tên khác.',
        ]);
        $this->assertSame('Current Name', $user->refresh()->name);
    }

    public function test_customer_can_update_contact_and_emergency_contact_information(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '0901234567',
            'date_of_birth' => '1990-05-20',
            'gender' => 'female',
            'address' => '12 Nguyen Trai, Ho Chi Minh City',
            'emergency_contact_name' => 'Nguyen Van An',
            'emergency_contact_phone' => '0912345678',
            'emergency_contact_relationship' => 'Brother',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '0901234567',
            'gender' => 'female',
            'address' => '12 Nguyen Trai, Ho Chi Minh City',
            'emergency_contact_name' => 'Nguyen Van An',
            'emergency_contact_phone' => '0912345678',
            'emergency_contact_relationship' => 'Brother',
        ]);
        $this->assertSame('1990-05-20', $user->refresh()->date_of_birth->toDateString());
    }

    public function test_customer_cannot_save_a_future_date_of_birth_or_invalid_gender(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'date_of_birth' => today()->addDay()->toDateString(),
            'gender' => 'unknown',
        ]);

        $response->assertSessionHasErrors(['date_of_birth', 'gender']);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'date_of_birth' => null,
            'gender' => null,
        ]);
    }

    public function test_non_customer_cannot_update_customer_contact_fields(): void
    {
        $doctor = User::factory()->create([
            'role' => 'doctor',
            'phone' => '0900000000',
        ]);

        $response = $this->actingAs($doctor)->patch('/profile', [
            'name' => $doctor->name,
            'email' => $doctor->email,
            'phone' => '0911111111',
            'emergency_contact_name' => 'Unexpected Contact',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');
        $this->assertSame('0900000000', $doctor->refresh()->phone);
        $this->assertNull($doctor->emergency_contact_name);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
