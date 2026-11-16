<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\Specialty;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_page_opens_the_chat_panel_with_working_endpoints(): void
    {
        $this->get(route('chatbot'))
            ->assertSee('Trợ lý AI MedCare')
            ->assertSee('data-chatbot-widget', false)
            ->assertSee('data-start-open="true"', false)
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('right: 20px', false)
            ->assertSee('href="'.route('catalog.doctors').'"', false)
            ->assertSee('xem danh sách bác sĩ')
            ->assertSee('/chatbot/send', false)
            ->assertSee('/chatbot/reset', false);
    }

    public function test_guest_layout_displays_the_chat_button_and_csrf_token(): void
    {
        $this->get(route('login'))
            ->assertSee('data-chatbot-widget', false)
            ->assertSee('meta name="csrf-token"', false);
    }

    public function test_patient_can_view_public_doctor_profile_information(): void
    {
        $patient = User::factory()->create(['role' => 'customer']);
        $doctor = User::factory()->create(['name' => 'Bác sĩ Nguyễn An', 'role' => 'doctor']);
        $specialty = Specialty::create(['name' => 'Nội tổng quát']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'bio' => 'Bác sĩ khám nội khoa và tư vấn sức khỏe tổng quát.',
            'consultation_fee' => 300000,
            'is_accepting' => true,
        ]);

        $this->actingAs($patient)
            ->get(route('catalog.doctors'))
            ->assertSee('Bác sĩ Nguyễn An')
            ->assertSee('Nội tổng quát')
            ->assertSee('Bác sĩ khám nội khoa và tư vấn sức khỏe tổng quát.')
            ->assertSee('300.000 đ');
    }

    public function test_valid_message_returns_the_ai_reply_and_saves_chat_history(): void
    {
        $message = 'Tôi muốn đặt lịch khám.';
        $reply = 'Bạn vui lòng chọn bác sĩ và khung giờ phù hợp.';
        $doctor = User::factory()->create(['name' => 'Bác sĩ Nguyễn An', 'role' => 'doctor']);
        $specialty = Specialty::create(['name' => 'Nội tổng quát']);
        DoctorProfile::create([
            'user_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'bio' => 'Bác sĩ khám nội khoa và tư vấn sức khỏe tổng quát.',
            'consultation_fee' => 300000,
            'is_accepting' => true,
        ]);
        $notAcceptingDoctor = User::factory()->create(['name' => 'Bác sĩ không nhận lịch', 'role' => 'doctor']);
        DoctorProfile::create([
            'user_id' => $notAcceptingDoctor->id,
            'is_accepting' => false,
        ]);
        $availableDoctors = [[
            'id' => $doctor->id,
            'name' => 'Bác sĩ Nguyễn An',
            'specialty' => 'Nội tổng quát',
            'bio' => 'Bác sĩ khám nội khoa và tư vấn sức khỏe tổng quát.',
            'consultation_fee' => '300.000 đ',
            'slot_duration' => 30,
            'profile_url' => '/doctors',
            'booking_url' => route('patient.appointments.create', ['doctor_id' => $doctor->id], absolute: false),
        ]];

        $this->mock(GeminiService::class)
            ->shouldReceive('chat')
            ->once()
            ->with([], $message, $availableDoctors)
            ->andReturn($reply);

        $this->postJson(route('chatbot.send'), ['message' => $message])
            ->assertExactJson(['reply' => $reply])
            ->assertSessionHas('chat_history', [
                ['role' => 'user', 'text' => $message],
                ['role' => 'model', 'text' => $reply],
            ]);
    }

    public function test_message_over_the_limit_is_rejected(): void
    {
        $this->postJson(route('chatbot.send'), ['message' => str_repeat('a', 1001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    }

    public function test_reset_starts_a_new_chat_without_saved_history(): void
    {
        $this->withSession([
            'chat_history' => [
                ['role' => 'user', 'text' => 'Tôi muốn đặt lịch khám.'],
                ['role' => 'model', 'text' => 'Bạn muốn khám chuyên khoa nào?'],
            ],
        ])->postJson(route('chatbot.reset'))
            ->assertExactJson(['ok' => true])
            ->assertSessionMissing('chat_history');
    }

    public function test_gemini_receives_booking_guidance_from_the_json_file(): void
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/test-model:generateContent';
        config([
            'services.gemini.model' => 'test-model',
            'services.gemini.key' => 'test-api-key',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            $url => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Mời bạn mở biểu mẫu đặt lịch.']]]],
                ],
            ]),
        ]);

        $reply = app(GeminiService::class)->chat([], 'Tôi muốn đặt lịch khám.');

        $this->assertSame('Mời bạn mở biểu mẫu đặt lịch.', $reply);
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === $url
            && str_contains(
                $request->data()['system_instruction']['parts'][0]['text'],
                '"booking_flow"',
            )
            && str_contains(
                $request->data()['system_instruction']['parts'][0]['text'],
                'Chatbot gợi ý bác sĩ nhưng không tạo lịch hẹn trực tiếp',
            )
            && str_contains(
                $request->data()['system_instruction']['parts'][0]['text'],
                '"recommendation_process"',
            )
            && str_contains(
                $request->data()['system_instruction']['parts'][0]['text'],
                '"response_style"',
            )
            && str_contains(
                $request->data()['system_instruction']['parts'][0]['text'],
                '"dynamic_doctor_data"',
            )
            && str_contains(
                $request->data()['system_instruction']['parts'][0]['text'],
                'tối đa 2-4 câu ngắn',
            )
            && str_contains(
                $request->data()['system_instruction']['parts'][0]['text'],
                'tối đa hai lựa chọn phù hợp nhất',
            )
            && str_contains(
                $request->data()['system_instruction']['parts'][0]['text'],
                'tuyệt đối không xin lỗi',
            )
            && str_contains(
                $request->data()['system_instruction']['parts'][0]['text'],
                '"login_required": true',
            ));
    }

    public function test_gemini_receives_current_doctor_list_and_prefilled_booking_links(): void
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/test-model:generateContent';
        config([
            'services.gemini.model' => 'test-model',
            'services.gemini.key' => 'test-api-key',
        ]);
        $availableDoctors = [[
            'id' => 12,
            'name' => 'Bác sĩ Nguyễn An',
            'specialty' => 'Nội tổng quát',
            'bio' => 'Bác sĩ khám nội khoa, chăm sóc sức khỏe tổng quát.',
            'consultation_fee' => '300.000 đ',
            'slot_duration' => 45,
            'profile_url' => '/doctors',
            'booking_url' => '/appointments/create?doctor_id=12',
        ]];

        Http::preventStrayRequests();
        Http::fake([
            $url => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Bạn có thể đặt lịch với bác sĩ phù hợp.']]]],
                ],
            ]),
        ]);

        app(GeminiService::class)->chat([], 'Tôi bị ho và sốt.', $availableDoctors);

        Http::assertSent(fn (ClientRequest $request): bool => str_contains(
            $request->data()['system_instruction']['parts'][0]['text'],
            '"booking_url":"/appointments/create?doctor_id=12"',
        ) && str_contains(
            $request->data()['system_instruction']['parts'][0]['text'],
            '"bio":"Bác sĩ khám nội khoa, chăm sóc sức khỏe tổng quát."',
        ) && str_contains(
            $request->data()['system_instruction']['parts'][0]['text'],
            '"profile_url":"/doctors"',
        ) && str_contains(
            $request->data()['system_instruction']['parts'][0]['text'],
            '"slot_duration":45',
        ));
    }

    public function test_gemini_reply_does_not_link_a_doctor_outside_the_current_roster(): void
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/test-model:generateContent';
        config([
            'services.gemini.model' => 'test-model',
            'services.gemini.key' => 'test-api-key',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            $url => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Đặt lịch: /appointments/create?doctor_id=999']]]],
                ],
            ]),
        ]);

        $reply = app(GeminiService::class)->chat([], 'Tôi muốn đặt lịch khám.', [[
            'id' => 12,
            'name' => 'Bác sĩ Nguyễn An',
            'specialty' => 'Nội tổng quát',
            'bio' => 'Bác sĩ khám nội khoa.',
            'consultation_fee' => '300.000 đ',
            'slot_duration' => 30,
            'profile_url' => '/doctors',
            'booking_url' => '/appointments/create?doctor_id=12',
        ]]);

        $this->assertSame('Đặt lịch: /appointments/create', $reply);
    }

    public function test_gemini_reply_removes_markdown_formatting_and_preserves_booking_targets(): void
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/test-model:generateContent';
        config([
            'services.gemini.model' => 'test-model',
            'services.gemini.key' => 'test-api-key',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            $url => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [[
                        'text' => "**Chọn bác sĩ**\nMở biểu mẫu: `/appointments/create`",
                    ]]]],
                ],
            ]),
        ]);

        $reply = app(GeminiService::class)->chat([], 'Tôi muốn đặt lịch khám.');

        $this->assertSame("Chọn bác sĩ\nMở biểu mẫu: /appointments/create", $reply);
    }

    public function test_gemini_reply_removes_apologies_and_uses_a_direct_answer(): void
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/test-model:generateContent';
        config([
            'services.gemini.model' => 'test-model',
            'services.gemini.key' => 'test-api-key',
        ]);

        Http::preventStrayRequests();
        Http::fake([
            $url => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [[
                        'text' => 'Xin lỗi, bạn có thể khám Nội tổng quát. Rất tiếc nếu gây bất tiện.',
                    ]]]],
                ],
            ]),
        ]);

        $reply = app(GeminiService::class)->chat([], 'Tôi bị đau bụng.');

        $this->assertSame('bạn có thể khám Nội tổng quát. nếu gây bất tiện.', $reply);
    }

    public function test_chatbot_renders_internal_routes_with_friendly_link_labels(): void
    {
        $this->get(route('chatbot'))
            ->assertSee("return 'Đặt lịch với bác sĩ';", false)
            ->assertSee("return 'Mở biểu mẫu đặt lịch';", false)
            ->assertSee("return 'Xem danh sách bác sĩ';", false)
            ->assertSee("return 'Xem danh sách chuyên khoa';", false)
            ->assertDontSee('link.textContent = match[0]', false)
            ->assertSee("addMessage(data.reply || 'Bạn muốn khám vấn đề gì? Mình sẽ gợi ý bác sĩ đang nhận lịch phù hợp.', 'assistant')", false);
    }
}
