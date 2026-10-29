<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\User;
use App\Support\AppointmentAvailability;
use App\Support\MedicationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function patientIndex(Request $request): View
    {
        $appointments = $request->user()->patientAppointments()
            ->with(['doctor.doctorProfile.specialty', 'medicalRecord'])
            ->latest('starts_at')->paginate(12);

        return view('patients.appointments.index', compact('appointments'));
    }

    public function create(): View
    {
        $doctors = User::query()->where('role', 'doctor')
            ->whereHas('doctorProfile', fn ($query) => $query->where('is_accepting', true))
            ->with('doctorProfile.specialty')->orderBy('name')->get();

        return view('patients.appointments.create', compact('doctors'));
    }

    public function availability(Request $request, User $doctor, AppointmentAvailability $availability): JsonResponse
    {
        abort_unless($doctor->isRole('doctor') && $doctor->doctorProfile, 404);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'except_appointment' => ['nullable', 'integer'],
        ]);

        $except = null;
        if ($request->user()->isRole('admin') && isset($data['except_appointment'])) {
            $except = Appointment::findOrFail($data['except_appointment']);
        }

        $date = Carbon::createFromFormat('Y-m-d', $data['date']);
        if ($date->lt(today()) && ! (
            $except
            && $except->doctor_id === $doctor->id
            && $except->starts_at->toDateString() === $date->toDateString()
        )) {
            return response()->json([
                'message' => 'Ngày khám phải từ hôm nay trở đi.',
                'errors' => ['date' => ['Ngày khám phải từ hôm nay trở đi.']],
            ], 422);
        }

        if (! $doctor->doctorProfile->is_accepting
            && ! ($except && $except->doctor_id === $doctor->id)) {
            return response()->json(['slots' => []]);
        }

        return response()->json([
            'slots' => $availability->slots($doctor, $date, $except),
        ]);
    }

    public function store(Request $request, AppointmentAvailability $availability): RedirectResponse
    {
        $data = $request->validate([
            'doctor_id' => ['required', Rule::exists('users', 'id')->where('role', 'doctor')],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'patient_age' => ['required', 'integer', 'min:0', 'max:120'],
            'reason' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'appointment_date.required' => 'Vui lòng chọn ngày khám.',
            'appointment_date.after_or_equal' => 'Ngày khám phải từ hôm nay trở đi.',
            'starts_at.required' => 'Vui lòng chọn khung giờ còn trống.',
            'starts_at.date_format' => 'Ngày giờ khám không hợp lệ.',
            'patient_age.required' => 'Vui lòng nhập tuổi bệnh nhân.',
            'patient_age.integer' => 'Tuổi bệnh nhân phải là số nguyên.',
            'patient_age.min' => 'Tuổi bệnh nhân không được nhỏ hơn 0.',
            'patient_age.max' => 'Tuổi bệnh nhân không được lớn hơn 120.',
        ]);

        $doctor = User::whereKey($data['doctor_id'])->where('role', 'doctor')
            ->whereHas('doctorProfile', fn ($query) => $query->where('is_accepting', true))
            ->firstOrFail();
        $startsAt = Carbon::createFromFormat('Y-m-d\TH:i', $data['starts_at']);
        if ($startsAt->toDateString() !== $data['appointment_date']) {
            return back()->withErrors(['starts_at' => 'Khung giờ phải thuộc ngày khám đã chọn.'])
                ->withInput();
        }

        $appointment = DB::transaction(function () use ($availability, $data, $doctor, $request, $startsAt): ?Appointment {
            $profile = DoctorProfile::query()
                ->where('user_id', $doctor->id)
                ->lockForUpdate()
                ->firstOrFail();
            $doctor->setRelation('doctorProfile', $profile);

            $slots = $availability->slots($doctor, Carbon::createFromFormat('Y-m-d', $data['appointment_date']));
            $slotValues = array_column($slots, 'value');
            if (! in_array($startsAt->format('Y-m-d\TH:i'), $slotValues, true)) {
                return null;
            }

            $duration = $profile->slot_duration ?: 30;

            return Appointment::create([
                'patient_id' => $request->user()->id,
                'patient_age' => $data['patient_age'],
                'doctor_id' => $doctor->id,
                'specialty_id' => $profile->specialty_id,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes($duration),
                'consultation_fee' => $profile->consultation_fee ?? 0,
                'status' => 'pending',
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        if (! $appointment) {
            return back()->withErrors(['starts_at' => 'Khung giờ này vừa được đặt hoặc không còn khả dụng. Vui lòng chọn giờ khác.'])
                ->withInput();
        }

        return redirect()->route('patient.appointments.payment', $appointment)
            ->with('success', 'Đã gửi yêu cầu đặt lịch. Bạn có thể thanh toán phí khám tại đây.');
    }

    public function patientShow(Request $request, Appointment $appointment): View
    {
        $this->authorize('view', $appointment);
        $appointment->load(['doctor.doctorProfile.specialty', 'medicalRecord.medications', 'medicineOrder.items']);
        $prescribedMedicines = MedicationCatalog::prescribedNames($appointment->medicalRecord?->prescription);
        foreach ($appointment->medicalRecord?->medications ?? [] as $medication) {
            $prescribedMedicines[] = $medication->medicine_name;
        }
        $prescribedMedicines = array_values(array_unique($prescribedMedicines));

        return view('patients.appointments.show', compact('appointment', 'prescribedMedicines'));
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('cancel', $appointment);
        $data = $request->validate(['cancellation_reason' => ['nullable', 'string', 'max:1000']]);
        $appointment->update(['status' => 'cancelled', 'cancellation_reason' => $data['cancellation_reason'] ?? null]);

        return back()->with('success', 'Lịch hẹn đã được hủy.');
    }

    public function doctorIndex(Request $request): View
    {
        $appointments = $request->user()->doctorAppointments()
            ->with(['patient', 'specialty', 'medicalRecord'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('doctor.appointments.index', compact('appointments'));
    }

    public function doctorShow(Request $request, Appointment $appointment): View
    {
        $this->authorize('view', $appointment);
        $appointment->load(['patient', 'doctor.doctorProfile', 'specialty', 'medicalRecord']);

        return view('doctor.appointments.show', compact('appointment'));
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);
        $data = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(['confirmed', 'completed', 'cancelled'])],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
            'doctor_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $data['status'] ??= $appointment->status;
        $appointment->update($data);

        return back()->with('success', 'Trạng thái lịch hẹn đã được cập nhật.');
    }

    public function recordCreate(Request $request, Appointment $appointment): View
    {
        $this->authorize('update', $appointment);
        abort_unless($appointment->status === 'completed', 422, 'Chỉ có thể lập hồ sơ sau khi hoàn tất lịch hẹn.');
        $appointment->load('patient', 'medicalRecord.medications');
        $prescribedMedicationQuantities = $appointment->medicalRecord?->medications
            ->pluck('quantity', 'medicine_name')
            ->all() ?? [];

        foreach (MedicationCatalog::prescribedNames($appointment->medicalRecord?->prescription) as $medicineName) {
            $prescribedMedicationQuantities[$medicineName] ??= 1;
        }
        $prescriptionDetails = MedicationCatalog::details($appointment->medicalRecord?->prescription);

        return view('doctor.medical-records.create', compact(
            'appointment',
            'prescribedMedicationQuantities',
            'prescriptionDetails',
        ));
    }

    public function recordStore(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);
        abort_unless($appointment->status === 'completed', 422, 'Chỉ có thể lập hồ sơ sau khi hoàn tất lịch hẹn.');
        $data = $request->validate([
            'diagnosis' => ['required', 'string', 'max:5000'],
            'prescription' => ['nullable', 'string', 'max:5000'],
            'medications' => ['nullable', 'array', 'max:20'],
            'medications.*.name' => ['required', 'string', Rule::in(MedicationCatalog::all()), 'distinct'],
            'medications.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $medicinesByName = Medicine::query()
            ->whereIn('name', array_column($data['medications'] ?? [], 'name'))
            ->pluck('id', 'name');

        DB::transaction(function () use ($appointment, $data, $medicinesByName): void {
            $medicalRecord = MedicalRecord::updateOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'diagnosis' => $data['diagnosis'],
                    'prescription' => $data['prescription'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'patient_id' => $appointment->patient_id,
                    'doctor_id' => $appointment->doctor_id,
                ],
            );
            $medicalRecord->medications()->delete();
            $medicalRecord->medications()->createMany(array_map(
                fn (array $medication): array => [
                    'medicine_id' => $medicinesByName->get($medication['name']),
                    'medicine_name' => $medication['name'],
                    'quantity' => $medication['quantity'],
                ],
                $data['medications'] ?? [],
            ));
            $appointment->update(['doctor_notes' => $data['notes'] ?? null]);
        });

        return redirect()->route('doctor.appointments.show', $appointment)->with('success', 'Hồ sơ y tế đã được lưu.');
    }
}
