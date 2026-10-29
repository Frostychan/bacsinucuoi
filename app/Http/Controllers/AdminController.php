<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Specialty;
use App\Models\User;
use App\Support\AppointmentAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $pendingAppointments = Appointment::query()
            ->with(['patient:id,name', 'doctor:id,name'])
            ->where('status', 'pending')
            ->orderBy('starts_at')
            ->limit(5)
            ->get();
        $stats = [
            'appointments' => Appointment::count(),
            'today' => Appointment::whereDate('starts_at', today())->count(),
            'pending' => Appointment::where('status', 'pending')->count(),
        ];

        return view('admin.dashboard', compact('pendingAppointments', 'stats'));
    }

    public function users(Request $request): View
    {
        $roleLabels = [
            'customer' => 'Bệnh nhân',
            'doctor' => 'Bác sĩ',
            'admin' => 'Admin',
        ];
        $data = $request->validate([
            'role' => ['nullable', Rule::in(array_keys($roleLabels))],
        ]);
        $selectedRole = $data['role'] ?? null;
        $roleCounts = User::query()
            ->select('role')
            ->selectRaw('COUNT(*) as total')
            ->whereIn('role', array_keys($roleLabels))
            ->groupBy('role')
            ->pluck('total', 'role');
        $users = $selectedRole
            ? User::query()
                ->where('role', $selectedRole)
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString()
            : null;

        return view('admin.users.index', compact('roleCounts', 'roleLabels', 'selectedRole', 'users'));
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['role' => 'Bạn không thể tự thay đổi vai trò của mình.']);
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'doctor', 'customer'])],
        ]);

        if ($user->isRole('admin') && $data['role'] !== 'admin'
            && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'Hệ thống phải luôn có ít nhất một quản trị viên.']);
        }

        $user->update($data);

        return back()->with('success', 'Vai trò người dùng đã được cập nhật.');
    }

    public function appointments(): View
    {
        $appointments = Appointment::query()
            ->with(['patient', 'doctor', 'specialty'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.appointments.index', compact('appointments'));
    }

    public function editAppointment(Appointment $appointment): View
    {
        $appointment->load(['patient', 'doctor']);
        $doctors = User::query()
            ->where('role', 'doctor')
            ->whereHas('doctorProfile', function ($query) use ($appointment): void {
                $query->where('is_accepting', true)
                    ->orWhere('user_id', $appointment->doctor_id);
            })
            ->with('doctorProfile.specialty')
            ->orderBy('name')
            ->get();

        return view('admin.appointments.edit', compact('appointment', 'doctors'));
    }

    public function updateAppointment(
        Request $request,
        Appointment $appointment,
        AppointmentAvailability $availability,
    ): RedirectResponse {
        $data = $request->validate([
            'doctor_id' => ['required', Rule::exists('users', 'id')->where('role', 'doctor')],
            'appointment_date' => ['required', 'date_format:Y-m-d'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'patient_age' => ['required', 'integer', 'min:0', 'max:120'],
            'reason' => ['required', 'string', 'max:500'],
            'status' => ['required', Rule::in(Appointment::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $startsAt = Carbon::createFromFormat('Y-m-d\TH:i', $data['starts_at']);
        if ($startsAt->toDateString() !== $data['appointment_date']) {
            return back()->withErrors(['starts_at' => 'Khung giờ phải thuộc ngày khám đã chọn.'])
                ->withInput();
        }

        $doctor = User::query()
            ->where('role', 'doctor')
            ->with('doctorProfile')
            ->findOrFail($data['doctor_id']);
        $sameSlot = $appointment->doctor_id === $doctor->id
            && $appointment->starts_at->format('Y-m-d\TH:i') === $startsAt->format('Y-m-d\TH:i');

        $saved = DB::transaction(function () use (
            $availability,
            $appointment,
            $data,
            $doctor,
            $sameSlot,
            $startsAt,
        ): bool {
            $profile = DoctorProfile::query()
                ->where('user_id', $doctor->id)
                ->lockForUpdate()
                ->first();

            if (! $profile || (! $sameSlot && ! $profile->is_accepting)) {
                return false;
            }

            $doctor->setRelation('doctorProfile', $profile);
            if (! $sameSlot) {
                $slots = $availability->slots(
                    $doctor,
                    Carbon::createFromFormat('Y-m-d', $data['appointment_date']),
                    $appointment,
                );

                if (! in_array($startsAt->format('Y-m-d\TH:i'), array_column($slots, 'value'), true)) {
                    return false;
                }
            }

            $duration = $profile->slot_duration ?: 30;
            $appointment->update([
                'doctor_id' => $doctor->id,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes($duration),
                'patient_age' => $data['patient_age'],
                'specialty_id' => $profile->specialty_id,
                'consultation_fee' => $profile->consultation_fee ?? 0,
                'reason' => $data['reason'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'cancellation_reason' => $data['cancellation_reason'] ?? null,
            ]);

            return true;
        });

        if (! $saved) {
            return back()->withErrors([
                'starts_at' => 'Bác sĩ không nhận lịch hoặc khung giờ không còn trống.',
            ])->withInput();
        }

        return redirect()->route('admin.appointments.index')->with('success', 'Lịch hẹn đã được cập nhật.');
    }

    public function deleteAppointment(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();

        return redirect()->route('admin.appointments.index')->with('success', 'Lịch hẹn đã được xóa.');
    }

    public function doctors(): View
    {
        $doctors = User::query()
            ->where('role', 'doctor')
            ->with('doctorProfile.specialty')
            ->orderBy('name')
            ->paginate(15);
        $specialties = Specialty::query()->orderBy('name')->get();

        return view('admin.doctors.index', compact('doctors', 'specialties'));
    }

    public function createDoctor(): View
    {
        $specialties = Specialty::query()->orderBy('name')->get();

        return view('admin.doctors.create', compact('specialties'));
    }

    public function storeDoctor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique(User::class, 'name')],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'license_number' => ['nullable', 'string', 'max:255', Rule::unique('doctor_profiles', 'license_number')],
            'bio' => ['nullable', 'string', 'max:2000'],
            'consultation_fee' => ['required', 'numeric', 'min:0'],
            'slot_duration' => ['required', 'integer', 'min:15', 'max:180'],
            'is_accepting' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'name.unique' => 'Họ và tên này đã được sử dụng. Vui lòng chọn tên khác.',
        ]);

        $image = $data['image'] ?? null;
        unset($data['image']);
        $imagePath = $image?->store('doctors', 'public');

        DB::transaction(function () use ($data, $imagePath): void {
            $doctor = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'role' => 'doctor',
            ]);

            $doctor->doctorProfile()->create([
                'specialty_id' => $data['specialty_id'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'bio' => $data['bio'] ?? null,
                'consultation_fee' => $data['consultation_fee'],
                'slot_duration' => $data['slot_duration'],
                'is_accepting' => $data['is_accepting'] ?? false,
                'image_path' => $imagePath,
            ]);
        });

        return redirect()->route('admin.doctors.index')->with('success', 'Đã tạo tài khoản và hồ sơ bác sĩ.');
    }

    public function updateDoctor(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isRole('doctor'), 404);

        $data = $request->validate([
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'consultation_fee' => ['required', 'numeric', 'min:0'],
            'slot_duration' => ['required', 'integer', 'min:15', 'max:180'],
            'is_accepting' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $image = $data['image'] ?? null;
        unset($data['image']);
        $data['is_accepting'] = $request->boolean('is_accepting');
        $profile = DoctorProfile::firstOrNew(['user_id' => $user->id]);

        $newImagePath = $image?->store('doctors', 'public');

        $oldImagePath = $profile->image_path;
        $profile->fill($data);
        if ($newImagePath !== null) {
            $profile->image_path = $newImagePath;
        }
        $profile->save();

        if ($newImagePath !== null && $oldImagePath !== null) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return back()->with('success', 'Thông tin bác sĩ đã được cập nhật.');
    }

    public function sendAppointmentToDoctor(Appointment $appointment): RedirectResponse
    {
        if ($appointment->status !== 'pending') {
            return back()->withErrors(['appointment' => 'Lịch hẹn này đã được xử lý.']);
        }

        $appointment->update(['status' => 'confirmed']);

        return back()->with('success', 'Đã gửi lịch hẹn cho bác sĩ.');
    }
}
