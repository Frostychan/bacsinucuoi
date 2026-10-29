<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_contact_form(): void
    {
        $this->get(route('contact.create'))
            ->assertSee('Liên hệ với chúng tôi')
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="message"', false)
            ->assertSee('Gửi liên hệ');
    }

    public function test_valid_contact_submission_is_saved_and_returns_confirmation(): void
    {
        $formData = [
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.com',
            'message' => 'Toi muon hoi ve lich kham.',
        ];

        $this->post(route('contact.store'), $formData)
            ->assertRedirect(route('contact.create'))
            ->assertSessionHas('success', 'Cảm ơn bạn đã gửi liên hệ. Chúng tôi sẽ phản hồi sớm.');

        $this->assertDatabaseHas('contact_messages', $formData);
    }

    public function test_invalid_contact_submission_is_rejected_without_saving(): void
    {
        $this->from(route('contact.create'))
            ->post(route('contact.store'), [
                'name' => '',
                'email' => 'invalid-email',
                'message' => '',
            ])
            ->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_admin_can_review_contact_messages_and_untrusted_text_is_escaped(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ContactMessage::create([
            'name' => '<script>alert(1)</script>',
            'email' => 'visitor@example.com',
            'message' => '<b>Need assistance</b>',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contact-messages.index'))
            ->assertSee('visitor@example.com')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;b&gt;Need assistance&lt;/b&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_non_admin_cannot_review_contact_messages(): void
    {
        $patient = User::factory()->create(['role' => 'customer']);

        $this->actingAs($patient)
            ->get(route('admin.contact-messages.index'))
            ->assertForbidden();
    }
}
