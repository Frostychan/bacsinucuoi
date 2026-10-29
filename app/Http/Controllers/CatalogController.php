<?php

namespace App\Http\Controllers;

use App\Models\DoctorProfile;
use App\Models\Product;
use App\Models\Specialty;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function specialties(): View
    {
        $specialties = Specialty::query()
            ->where('is_active', true)
            ->withCount('doctors')
            ->orderBy('name')
            ->get();

        return view('catalog.specialties', compact('specialties'));
    }

    public function doctors(): View
    {
        $doctors = DoctorProfile::query()
            ->where('is_accepting', true)
            ->with(['user', 'specialty'])
            ->get()
            ->sortBy('user.name')
            ->values();

        return view('catalog.doctors', compact('doctors'));
    }

    public function services(): View
    {
        $services = Product::query()
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('catalog.services', compact('services'));
    }
}
