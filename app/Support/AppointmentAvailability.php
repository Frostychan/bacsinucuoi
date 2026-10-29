<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Carbon;

class AppointmentAvailability
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function slots(User $doctor, Carbon $date, ?Appointment $except = null): array
    {
        $doctor->loadMissing('doctorProfile');
        $duration = $doctor->doctorProfile?->slot_duration ?: 30;
        $dayStart = $date->copy()->setTime(0, 0);
        $dayEnd = $dayStart->copy()->addDay();

        $appointments = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->where('starts_at', '<', $dayEnd)
            ->where(function ($query) use ($dayStart): void {
                $query->where('ends_at', '>', $dayStart)
                    ->orWhere(function ($query) use ($dayStart): void {
                        $query->whereNull('ends_at')
                            ->where('starts_at', '>=', $dayStart);
                    });
            })
            ->get(['id', 'starts_at', 'ends_at']);

        $openingTime = $date->copy()->setTime(8, 0);
        $closingTime = $date->copy()->setTime(17, 0);
        $slots = [];

        while ($openingTime->copy()->addMinutes($duration)->lte($closingTime)) {
            $slotEnd = $openingTime->copy()->addMinutes($duration);
            $hasConflict = $appointments->contains(function (Appointment $appointment) use (
                $doctor,
                $duration,
                $openingTime,
                $slotEnd,
            ): bool {
                $appointmentStart = $appointment->starts_at;
                $appointmentEnd = $appointment->ends_at
                    ?? $appointmentStart->copy()->addMinutes($doctor->doctorProfile?->slot_duration ?: $duration);

                return $appointmentStart->lt($slotEnd) && $appointmentEnd->gt($openingTime);
            });

            $isCurrentAppointmentSlot = $except
                && $except->doctor_id === $doctor->id
                && $except->starts_at->format('Y-m-d\TH:i') === $openingTime->format('Y-m-d\TH:i');

            if (($openingTime->isFuture() || $isCurrentAppointmentSlot) && ! $hasConflict) {
                $slots[] = [
                    'value' => $openingTime->format('Y-m-d\TH:i'),
                    'label' => $openingTime->format('H:i'),
                ];
            }

            $openingTime->addMinutes($duration);
        }

        return $slots;
    }
}
