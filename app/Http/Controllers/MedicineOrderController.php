<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Medicine;
use App\Models\MedicineOrder;
use App\Services\AppointmentPaymentProcessor;
use App\Services\SepayPaymentProcessor;
use App\Support\AppointmentPaymentPricing;
use App\Support\MedicationCatalog;
use App\Support\MedicineOrderPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicineOrderController extends Controller
{
    public function payment(Appointment $appointment): View
    {
        $this->authorize('pay', $appointment);
        $appointment->load(['doctor', 'medicineOrder.items.medicine']);
        $hasMedicineOrder = $appointment->medicineOrder !== null;

        if ($hasMedicineOrder) {
            $this->snapshotOrderPrices($appointment);
            $appointment->load(['doctor', 'medicineOrder.items.medicine']);
            $summary = $this->paymentSummary($appointment->medicineOrder);
            $appointment->medicineOrder->load(['payments' => fn ($query) => $query->latest('id')->limit(1)]);
            $latestPayment = $appointment->medicineOrder->payments->first();
        } else {
            $summary = [
                'paymentItems' => [],
                'totalAmountCents' => null,
                'unpricedMedicines' => [],
                'hasEstimates' => false,
            ];
            $latestPayment = null;
        }

        $appointmentAmount = AppointmentPaymentPricing::totalInWholeVnd($appointment);
        $appointmentReference = 'APPT-'.$appointment->id;
        $medicineReference = $hasMedicineOrder ? 'MEDCARE-'.$appointment->medicineOrder->id : null;
        $bankAccount = config('services.sepay.bank_account');
        $bankCode = config('services.sepay.bank_code');
        $paymentReady = is_string($bankAccount)
            && $bankAccount !== ''
            && is_string($bankCode)
            && $bankCode !== ''
            && is_string(config('services.sepay.webhook_secret'))
            && config('services.sepay.webhook_secret') !== '';
        $appointmentPaymentEnabled = $paymentReady
            && $appointmentAmount !== null
            && $appointment->payment_status !== 'paid'
            && $appointment->status !== 'cancelled';
        $medicinePaymentEnabled = $paymentReady
            && $hasMedicineOrder
            && ! $summary['hasEstimates']
            && $summary['unpricedMedicines'] === []
            && $summary['totalAmountCents'] !== null
            && $summary['totalAmountCents'] > 0
            && $summary['totalAmountCents'] % 100 === 0
            && ! in_array($appointment->medicineOrder->payment_status, ['paid', 'simulated_paid'], true);
        $appointmentQrUrl = $appointmentPaymentEnabled
            ? 'https://vietqr.app/img?'.http_build_query([
                'acc' => $bankAccount,
                'bank' => $bankCode,
                'amount' => $appointmentAmount,
                'des' => $appointmentReference,
                'template' => 'compact',
            ])
            : null;
        $medicineQrUrl = $medicinePaymentEnabled
            ? 'https://vietqr.app/img?'.http_build_query([
                'acc' => $bankAccount,
                'bank' => $bankCode,
                'amount' => intdiv($summary['totalAmountCents'], 100),
                'des' => $medicineReference,
                'template' => 'compact',
            ])
            : null;

        return view('patients.medicine-orders.payment', [
            'appointment' => $appointment,
            'paymentMethods' => $this->paymentMethods(),
            'simulated' => false,
            'selectedPaymentMethod' => null,
            'appointmentAmount' => $appointmentAmount,
            'appointmentReference' => $appointmentReference,
            'medicineReference' => $medicineReference,
            'bankAccount' => $bankAccount,
            'bankCode' => $bankCode,
            'paymentReady' => $paymentReady,
            'appointmentPaymentEnabled' => $appointmentPaymentEnabled,
            'medicinePaymentEnabled' => $medicinePaymentEnabled,
            'appointmentQrUrl' => $appointmentQrUrl,
            'medicineQrUrl' => $medicineQrUrl,
            'hasMedicineOrder' => $hasMedicineOrder,
            'sandboxEnabled' => app()->environment(['local', 'testing']),
            'latestPayment' => $latestPayment,
            ...$summary,
        ]);
    }

    public function medicinePayment(Appointment $appointment): View
    {
        $this->authorize('orderMedicine', $appointment);
        abort_if(! $appointment->medicineOrder()->exists(), 404);

        return $this->payment($appointment);
    }

    public function simulateSepay(
        Request $request,
        Appointment $appointment,
        SepayPaymentProcessor $processor,
        AppointmentPaymentProcessor $appointmentProcessor,
    ): RedirectResponse {
        abort_unless(app()->environment(['local', 'testing']), 404);
        $data = $request->validate([
            'scenario' => ['required', Rule::in(['paid', 'wrong_amount'])],
            'bill' => ['sometimes', Rule::in(['appointment', 'medicine'])],
        ]);
        $bill = $data['bill'] ?? 'medicine';
        $this->authorize($bill === 'appointment' ? 'pay' : 'orderMedicine', $appointment);

        if ($bill === 'appointment') {
            $expectedAmount = AppointmentPaymentPricing::totalInWholeVnd($appointment);
            $reference = 'APPT-'.$appointment->id;
            $alreadyPaid = $appointment->payment_status === 'paid';
        } else {
            $appointment->load('medicineOrder.items');
            abort_if($appointment->medicineOrder === null, 404);
            $expectedAmount = MedicineOrderPricing::totalInWholeVnd($appointment->medicineOrder);
            $reference = 'MEDCARE-'.$appointment->medicineOrder->id;
            $alreadyPaid = $appointment->medicineOrder->payment_status === 'paid';
        }

        if ($alreadyPaid) {
            return redirect()->route('patient.appointments.payment', $appointment)
                ->withErrors(['payment' => 'Khoản này đã được SePay xác nhận thanh toán thật.']);
        }

        $transferAmount = $expectedAmount === null
            ? 1
            : ($data['scenario'] === 'paid' ? $expectedAmount : $expectedAmount + 1);
        $transferData = [
            'id' => 'SIM-'.Str::uuid(),
            'accountNumber' => config('services.sepay.bank_account') ?: 'TEST-ACCOUNT',
            'transferType' => 'in',
            'transferAmount' => $transferAmount,
            'content' => $reference,
            'code' => $reference,
            'referenceCode' => null,
        ];
        $status = $bill === 'appointment'
            ? $appointmentProcessor->process($appointment->id, $transferData, isSimulated: true)
            : $processor->process($transferData, isSimulated: true);

        if ($status === 'simulated_paid') {
            return redirect()->route('patient.appointments.payment', $appointment)
                ->with('success', 'Mô phỏng SePay thành công. Đây không phải giao dịch thật, không có tiền được chuyển.');
        }

        return redirect()->route('patient.appointments.payment', $appointment)
            ->withErrors([
                'payment' => match ($status) {
                    'amount_mismatch' => 'Mô phỏng đã ghi nhận số tiền sai; đơn không được xác nhận đã thanh toán.',
                    'price_not_confirmed' => 'Không thể xác nhận thanh toán thử vì đơn còn giá tham khảo hoặc chưa có giá chính thức.',
                    'already_paid' => 'Đơn này đã được thanh toán thật.',
                    'appointment_cancelled' => 'Lịch đã hủy nên không thể nhận thanh toán.',
                    default => 'Không thể hoàn tất mô phỏng thanh toán SePay.',
                },
            ]);
    }

    public function simulatePayment(Request $request, Appointment $appointment): View|RedirectResponse
    {
        $this->authorize('orderMedicine', $appointment);
        $appointment->load(['doctor', 'medicineOrder.items.medicine']);
        abort_if($appointment->medicineOrder === null, 404);

        $data = $request->validate([
            'payment_method' => ['required', Rule::in(array_keys($this->paymentMethods()))],
        ], [
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán mô phỏng.',
            'payment_method.in' => 'Vui lòng chọn phương thức thanh toán mô phỏng hợp lệ.',
        ]);

        $pricesLocked = $this->snapshotOrderPrices($appointment);

        if (! $pricesLocked) {
            return redirect()->route('patient.medicine-orders.payment', $appointment)
                ->withErrors(['payment' => 'Chưa thể thanh toán thử nghiệm: quản trị viên cần nhập giá cho tất cả thuốc trong đơn.']);
        }

        $appointment->load(['doctor', 'medicineOrder.items.medicine']);

        return view('patients.medicine-orders.payment', [
            'appointment' => $appointment,
            'paymentMethods' => $this->paymentMethods(),
            'simulated' => true,
            'selectedPaymentMethod' => $data['payment_method'],
            'appointmentAmount' => AppointmentPaymentPricing::totalInWholeVnd($appointment),
            'appointmentReference' => 'APPT-'.$appointment->id,
            'medicineReference' => 'MEDCARE-'.$appointment->medicineOrder->id,
            'bankAccount' => config('services.sepay.bank_account'),
            'bankCode' => config('services.sepay.bank_code'),
            'paymentReady' => false,
            'appointmentPaymentEnabled' => false,
            'medicinePaymentEnabled' => false,
            'appointmentQrUrl' => null,
            'medicineQrUrl' => null,
            'hasMedicineOrder' => true,
            'sandboxEnabled' => app()->environment(['local', 'testing']),
            'latestPayment' => null,
            ...$this->paymentSummary($appointment->medicineOrder),
        ]);
    }

    public function checkout(Appointment $appointment): View
    {
        $this->authorize('orderMedicine', $appointment);
        $appointment->load(['medicalRecord.medications', 'medicineOrder.items']);
        $medicines = MedicationCatalog::prescribedNames($appointment->medicalRecord?->prescription);
        $medicineQuantities = $appointment->medicalRecord?->medications
            ->pluck('quantity', 'medicine_name')
            ->all() ?? [];

        foreach ($appointment->medicalRecord?->medications ?? [] as $medication) {
            $medicines[] = $medication->medicine_name;
        }
        $medicines = array_values(array_unique($medicines));
        abort_if($medicines === [] && ! $appointment->medicineOrder, 404);

        return view('patients.medicine-orders.checkout', compact('appointment', 'medicines', 'medicineQuantities'));
    }

    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('orderMedicine', $appointment);
        $appointment->load('medicalRecord.medications');

        $existingOrder = $appointment->medicineOrder()->exists();
        if ($existingOrder) {
            return redirect()->route('patient.medicine-orders.checkout', $appointment)
                ->with('success', 'Yêu cầu mua thuốc cho lịch khám này đã được gửi.');
        }

        $medicines = MedicationCatalog::prescribedNames($appointment->medicalRecord?->prescription);
        foreach ($appointment->medicalRecord?->medications ?? [] as $medication) {
            $medicines[] = $medication->medicine_name;
        }
        $medicines = array_values(array_unique($medicines));
        abort_if($medicines === [], 404);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:'.count($medicines)],
            'items.*.medicine_name' => ['required', 'string', Rule::in($medicines), 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.selected' => ['nullable', 'boolean'],
        ], [
            'items.*.medicine_name.in' => 'Chỉ có thể đặt thuốc được kê trong đơn của bác sĩ.',
            'items.*.medicine_name.distinct' => 'Mỗi loại thuốc chỉ được chọn một lần.',
            'items.*.quantity.min' => 'Số lượng mỗi loại thuốc phải ít nhất là 1.',
            'items.*.quantity.max' => 'Số lượng mỗi loại thuốc không được vượt quá 100.',
        ]);
        $selectedItems = array_values(array_filter(
            $data['items'],
            fn (array $item): bool => ($item['selected'] ?? false) === true || ($item['selected'] ?? null) === '1',
        ));

        if ($selectedItems === []) {
            return back()->withErrors(['items' => 'Vui lòng chọn ít nhất một loại thuốc.'])->withInput();
        }

        $orderCreated = DB::transaction(function () use ($appointment, $request, $selectedItems): bool {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAppointment->medicineOrder()->exists()) {
                return false;
            }

            $order = MedicineOrder::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $request->user()->id,
                'status' => 'requested',
            ]);

            $medicines = Medicine::query()
                ->whereIn('name', array_column($selectedItems, 'medicine_name'))
                ->lockForUpdate()
                ->get()
                ->keyBy('name');

            $order->items()->createMany(array_map(
                function (array $item) use ($medicines): array {
                    $medicine = $medicines->get($item['medicine_name']);

                    return [
                        'medicine_id' => $medicine?->id,
                        'medicine_name' => $item['medicine_name'],
                        'quantity' => $item['quantity'],
                        'unit' => $medicine?->unit,
                        'unit_price' => $medicine?->price,
                        'unit_price_is_estimate' => $medicine?->price_is_estimate ?? false,
                        'unit_price_source' => $medicine?->price_source,
                    ];
                },
                $selectedItems,
            ));

            return true;
        });

        if (! $orderCreated) {
            return redirect()->route('patient.medicine-orders.checkout', $appointment)
                ->with('success', 'Yêu cầu mua thuốc cho lịch khám này đã được gửi.');
        }

        return redirect()->route('patient.medicine-orders.checkout', $appointment)
            ->with('success', 'Yêu cầu mua thuốc đã được gửi. Nhân viên sẽ liên hệ để xác nhận.');
    }

    /**
     * @return array<string, string>
     */
    private function paymentMethods(): array
    {
        return [
            'vnpay' => 'VNPay (mô phỏng)',
            'momo' => 'MoMo (mô phỏng)',
            'bank_transfer' => 'Chuyển khoản ngân hàng (mô phỏng)',
        ];
    }

    private function snapshotOrderPrices(Appointment $appointment): bool
    {
        return DB::transaction(function () use ($appointment): bool {
            $order = MedicineOrder::query()
                ->where('appointment_id', $appointment->id)
                ->with('items.medicine')
                ->lockForUpdate()
                ->firstOrFail();
            $medicines = Medicine::query()
                ->whereKey($order->items->pluck('medicine_id')->filter()->unique())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $prices = [];

            foreach ($order->items as $item) {
                $medicine = $medicines->get($item->medicine_id);
                $unitPrice = $item->unit_price ?? $medicine?->price;

                if ($unitPrice === null) {
                    return false;
                }

                $prices[] = [
                    $item,
                    $unitPrice,
                    $medicine?->unit,
                    $medicine?->price_is_estimate ?? false,
                    $medicine?->price_source,
                ];
            }

            foreach ($prices as [$item, $unitPrice, $unit, $isEstimate, $source]) {
                if ($item->unit_price === null) {
                    $item->update([
                        'unit_price' => $unitPrice,
                        'unit' => $unit,
                        'unit_price_is_estimate' => $isEstimate,
                        'unit_price_source' => $source,
                    ]);
                }
            }

            return true;
        });
    }

    /**
     * @return array{paymentItems: array<int, array{name: string, quantity: int, unitPrice: ?string, lineTotalCents: ?int, isEstimate: bool, source: ?string}>, totalAmountCents: ?int, unpricedMedicines: array<int, string>, hasEstimates: bool}
     */
    private function paymentSummary(MedicineOrder $order): array
    {
        $totalAmountCents = 0;
        $unpricedMedicines = [];
        $paymentItems = [];
        $hasEstimates = false;

        foreach ($order->items as $item) {
            $unitPrice = $item->unit_price ?? $item->medicine?->price;
            $lineTotalCents = null;

            if ($unitPrice === null) {
                $unpricedMedicines[] = $item->medicine_name;
            } else {
                $lineTotalCents = $this->amountInCents($unitPrice) * $item->quantity;
                $totalAmountCents += $lineTotalCents;
            }

            $paymentItems[] = [
                'name' => $item->medicine_name,
                'quantity' => $item->quantity,
                'unitPrice' => $unitPrice,
                'lineTotalCents' => $lineTotalCents,
                'isEstimate' => $item->unit_price_is_estimate,
                'source' => $item->unit_price_source,
            ];
            $hasEstimates = $hasEstimates || $item->unit_price_is_estimate;
        }

        return [
            'paymentItems' => $paymentItems,
            'totalAmountCents' => $unpricedMedicines === [] ? $totalAmountCents : null,
            'unpricedMedicines' => $unpricedMedicines,
            'hasEstimates' => $hasEstimates,
        ];
    }

    private function amountInCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
