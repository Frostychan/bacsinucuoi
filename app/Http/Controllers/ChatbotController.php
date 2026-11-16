<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use RuntimeException;

class ChatbotController extends Controller
{
    // Hiển thị trang chat
    public function index()
    {
        return view('chatbot');
    }

    // Nhận câu hỏi, gọi Gemini, trả JSON
    public function send(Request $request, GeminiService $gemini)
    {
        $data = $request->validate([
            'message' => 'required|string|max:1000',
        ], [
            'message.required' => 'Bạn chưa nhập câu hỏi.',
            'message.max' => 'Câu hỏi tối đa 1000 ký tự.',
        ]);
        $history = session('chat_history', []);
        $availableDoctors = User::query()
            ->where('role', 'doctor')
            ->whereHas('doctorProfile', fn ($query) => $query->where('is_accepting', true))
            ->with([
                'doctorProfile:id,user_id,specialty_id,consultation_fee,slot_duration,bio',
                'doctorProfile.specialty:id,name',
            ])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $doctor): array => [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'specialty' => $doctor->doctorProfile->specialty?->name ?? 'Bác sĩ đa khoa',
                'bio' => $doctor->doctorProfile->bio,
                'consultation_fee' => number_format((float) $doctor->doctorProfile->consultation_fee, 0, ',', '.').' đ',
                'slot_duration' => $doctor->doctorProfile->slot_duration,
                'profile_url' => route('catalog.doctors', absolute: false),
                'booking_url' => route(
                    'patient.appointments.create',
                    ['doctor_id' => $doctor->id],
                    absolute: false,
                ),
            ])
            ->all();

        try {
            $reply = $gemini->chat($history, $data['message'], $availableDoctors);
        } catch (RuntimeException $e) {
            return response()->json(['reply' => $e->getMessage()], 503);
        }
        // Chỉ lưu lịch sửkhi gọi thành công; giữ 10 lượt gần nhất cho nhẹ
        $history[] = ['role' => 'user', 'text' => $data['message']];
        $history[] = ['role' => 'model', 'text' => $reply];
        session(['chat_history' => array_slice($history, -10)]);

        return response()->json(['reply' => $reply]);
    }

    // Xóa lịch sử, bắt đầu hội thoại mới
    public function reset()
    {
        session()->forget('chat_history');

        return response()->json(['ok' => true]);
    }
}
