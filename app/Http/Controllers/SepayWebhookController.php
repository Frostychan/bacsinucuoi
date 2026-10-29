<?php

namespace App\Http\Controllers;

use App\Models\MedicinePayment;
use App\Services\AppointmentPaymentProcessor;
use App\Services\SepayPaymentProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JsonException;

class SepayWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        SepayPaymentProcessor $processor,
        AppointmentPaymentProcessor $appointmentProcessor,
    ): JsonResponse {
        $secret = config('services.sepay.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            Log::warning('Rejected SePay webhook because the integration is not configured.');

            return response()->json(['success' => false], 503);
        }

        $rawBody = $request->getContent();
        $timestamp = $request->header('X-SePay-Timestamp');
        $signature = $request->header('X-SePay-Signature');
        $tolerance = (int) config('services.sepay.signature_tolerance_seconds', 300);

        if (
            ! is_string($timestamp)
            || ! ctype_digit($timestamp)
            || abs(now()->timestamp - (int) $timestamp) > $tolerance
            || ! is_string($signature)
        ) {
            return response()->json(['success' => false], 401);
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json(['success' => false], 401);
        }

        try {
            $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['success' => false], 400);
        }

        if (! is_array($payload)) {
            return response()->json(['success' => false], 400);
        }

        $validator = Validator::make($payload, [
            'id' => ['required', 'integer', 'min:1'],
            'accountNumber' => ['required', 'string', 'max:50'],
            'transferType' => ['required', 'string', 'in:in,out'],
            'transferAmount' => ['required', 'integer', 'min:1'],
            'content' => ['nullable', 'string', 'max:2000'],
            'code' => ['nullable', 'string', 'max:100'],
            'referenceCode' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false], 422);
        }

        $data = $validator->validated();
        $transactionId = (string) $data['id'];

        if (
            $data['transferType'] !== 'in'
            || $data['accountNumber'] !== config('services.sepay.bank_account')
        ) {
            return response()->json(['success' => true]);
        }

        if (MedicinePayment::query()->where('provider_transaction_id', $transactionId)->exists()) {
            return response()->json(['success' => true]);
        }

        $referenceText = implode(' ', array_filter([
            $data['content'] ?? null,
            $data['code'] ?? null,
            $data['referenceCode'] ?? null,
        ], fn (?string $value): bool => $value !== null && $value !== ''));

        if (preg_match('/(?<![A-Z0-9])APPT-(\d+)(?!\d)/i', $referenceText, $matches) === 1) {
            $appointmentProcessor->process((int) $matches[1], $data);
        } else {
            $processor->process($data);
        }

        return response()->json(['success' => true]);
    }
}
