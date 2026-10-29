<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('contact.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'message.required' => 'Vui lòng nhập nội dung liên hệ.',
        ]);

        ContactMessage::create($data);

        return redirect()->route('contact.create')->with('success', 'Cảm ơn bạn đã gửi liên hệ. Chúng tôi sẽ phản hồi sớm.');
    }

    public function adminIndex(): View
    {
        $messages = ContactMessage::query()
            ->latest('id')
            ->paginate(20);

        return view('admin.contact-messages.index', compact('messages'));
    }
}
