<?php

use App\Http\Controllers\Admin\MedicationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MedicineOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Nhóm Route dành riêng cho Admin (Bắt buộc đăng nhập + role là admin)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/patient-overview', [DashboardController::class, 'adminPatientOverview'])->name('patient-overview');
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
    Route::get('/appointments', [AdminController::class, 'appointments'])->name('appointments.index');
    Route::get('/appointments/{appointment}/edit', [AdminController::class, 'editAppointment'])->name('appointments.edit');
    Route::patch('/appointments/{appointment}', [AdminController::class, 'updateAppointment'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [AdminController::class, 'deleteAppointment'])->name('appointments.destroy');
    Route::patch('/appointments/{appointment}/send', [AdminController::class, 'sendAppointmentToDoctor'])->name('appointments.send');
    Route::get('/doctors', [AdminController::class, 'doctors'])->name('doctors.index');
    Route::get('/doctors/create', [AdminController::class, 'createDoctor'])->name('doctors.create');
    Route::post('/doctors', [AdminController::class, 'storeDoctor'])->name('doctors.store');
    Route::patch('/doctors/{user}', [AdminController::class, 'updateDoctor'])->name('doctors.update');
    Route::get('/medicines', [MedicationController::class, 'index'])->name('medicines.index');
    Route::patch('/medicines/{medicine}', [MedicationController::class, 'update'])->name('medicines.update');
    Route::get('/contact-messages', [ContactController::class, 'adminIndex'])->name('contact-messages.index');
    Route::resource('/products', ProductController::class);
});

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/specialties', [CatalogController::class, 'specialties'])->name('catalog.specialties');
Route::get('/doctors', [CatalogController::class, 'doctors'])->name('catalog.doctors');
Route::get('/services', [CatalogController::class, 'services'])->name('catalog.services');
Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:10,1')->name('contact.store');

Route::get('/dashboard', [DashboardController::class, 'redirect'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/patient/dashboard', [DashboardController::class, 'patient'])
        ->middleware('role:customer')
        ->name('patient.dashboard');
    Route::get('/doctor/dashboard', [DashboardController::class, 'doctor'])
        ->middleware('role:doctor')
        ->name('doctor.dashboard');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('patient.appointments.store');
    Route::get('/booking/doctors/{doctor}/availability', [AppointmentController::class, 'availability'])
        ->middleware('role:customer,admin')
        ->name('booking.doctors.availability');
    Route::get('/appointments/mine', [AppointmentController::class, 'patientIndex'])->name('patient.appointments.index');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('patient.appointments.create');
    Route::get('/appointments/{appointment}/payment', [MedicineOrderController::class, 'payment'])->name('patient.appointments.payment');
    Route::post('/appointments/{appointment}/payment/sepay-sandbox', [MedicineOrderController::class, 'simulateSepay'])
        ->name('patient.appointments.payment.sepay-sandbox');
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'patientShow'])->name('patient.appointments.show');
    Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('patient.appointments.cancel');
    Route::get('/appointments/{appointment}/medicine-checkout', [MedicineOrderController::class, 'checkout'])->name('patient.medicine-orders.checkout');
    Route::post('/appointments/{appointment}/medicine-orders', [MedicineOrderController::class, 'store'])->name('patient.medicine-orders.store');
    Route::get('/appointments/{appointment}/medicine-payment', [MedicineOrderController::class, 'medicinePayment'])->name('patient.medicine-orders.payment');
    Route::post('/appointments/{appointment}/medicine-payment/simulate', [MedicineOrderController::class, 'simulatePayment'])->name('patient.medicine-orders.payment.simulate');
    Route::post('/appointments/{appointment}/medicine-payment/sepay-sandbox', [MedicineOrderController::class, 'simulateSepay'])->name('patient.medicine-orders.payment.sepay-sandbox');
    Route::get('/doctor/appointments', [AppointmentController::class, 'doctorIndex'])->middleware('role:doctor')->name('doctor.appointments.index');
    Route::get('/doctor/appointments/{appointment}', [AppointmentController::class, 'doctorShow'])->middleware('role:doctor')->name('doctor.appointments.show');
    Route::patch('/doctor/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->middleware('role:doctor')->name('doctor.appointments.status');
    Route::get('/doctor/appointments/{appointment}/medical-records/create', [AppointmentController::class, 'recordCreate'])
        ->middleware('role:doctor')->name('doctor.medical-records.create');
    Route::post('/doctor/appointments/{appointment}/medical-records', [AppointmentController::class, 'recordStore'])
        ->middleware('role:doctor')->name('doctor.medical-records.store');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
