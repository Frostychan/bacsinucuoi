<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        return match ($request->user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'doctor' => redirect()->route('doctor.dashboard'),
            default => redirect()->route('patient.dashboard'),
        };
    }

    public function patient(Request $request): View
    {
        $appointments = $request->user()->patientAppointments()
            ->with(['doctor.doctorProfile.specialty'])
            ->latest('starts_at')->limit(5)->get();
        $availableDoctors = User::query()->where('role', 'doctor')
            ->whereHas('doctorProfile', fn ($query) => $query->where('is_accepting', true))
            ->with('doctorProfile.specialty')
            ->orderBy('name')
            ->limit(7)
            ->get();
        $hasMoreDoctors = $availableDoctors->count() > 6;
        $doctors = $availableDoctors->take(6);

        return view('patients.dashboard', compact('appointments', 'doctors', 'hasMoreDoctors'));
    }

    public function adminPatientOverview(): View
    {
        $availableDoctors = User::query()->where('role', 'doctor')
            ->whereHas('doctorProfile', fn ($query) => $query->where('is_accepting', true))
            ->with('doctorProfile.specialty')
            ->orderBy('name')
            ->limit(7)
            ->get();
        $hasMoreDoctors = $availableDoctors->count() > 6;
        $doctors = $availableDoctors->take(6);

        return view('patients.dashboard', [
            'appointments' => collect(),
            'doctors' => $doctors,
            'hasMoreDoctors' => $hasMoreDoctors,
            'adminPreview' => true,
        ]);
    }

    public function doctor(Request $request): View
    {
        $appointments = $request->user()->doctorAppointments()
            ->with('patient')->whereDate('starts_at', '>=', now()->toDateString())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('doctor.dashboard', compact('appointments'));
    }

    public function admin(): View
    {
        $stats = [
            'appointments' => Appointment::count(),
            'today' => Appointment::whereDate('starts_at', today())->count(),
            'pending' => Appointment::where('status', 'pending')->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
