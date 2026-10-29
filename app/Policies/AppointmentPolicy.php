<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->isRole('admin')
            || ($user->isRole('customer') && $appointment->patient_id === $user->id)
            || ($user->isRole('doctor') && $appointment->doctor_id === $user->id);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->isRole('admin')
            || ($user->isRole('doctor') && $appointment->doctor_id === $user->id);
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return ($user->isRole('admin') || ($user->isRole('customer') && $appointment->patient_id === $user->id))
            && in_array($appointment->status, ['pending', 'confirmed'], true);
    }

    public function orderMedicine(User $user, Appointment $appointment): bool
    {
        return $user->isRole('customer')
            && $appointment->patient_id === $user->id
            && $appointment->status === 'completed';
    }

    public function pay(User $user, Appointment $appointment): bool
    {
        return $user->isRole('customer')
            && $appointment->patient_id === $user->id;
    }
}
