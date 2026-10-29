@extends('layouts.app')
@section('title', 'Thanh toán')
@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Lịch khám #{{ $appointment->id }}</p>
            <h1>Thanh toán lịch khám</h1>
            <p class="subtitle">Phí đặt lịch và thuốc được kê sau buổi khám (nếu có).</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('patient.appointments.show', $appointment) }}">Chi tiết lịch khám</a>
    </div>

    <div class="card form-card">
        @if ($simulated)
            <div class="payment-simulation-result" role="status">
                <h2>Đã hoàn tất mô phỏng</h2>
                <p>Bạn đã thử luồng thanh toán bằng {{ $paymentMethods[$selectedPaymentMethod] }}.</p>
                <p><strong>Không có giao dịch thật nào được gửi và đơn hàng vẫn chưa được đánh dấu đã thanh toán.</strong></p>
            </div>
        @endif

        @if ($sandboxEnabled && ($appointment->payment_status !== 'paid' || ($hasMedicineOrder && ! in_array($appointment->medicineOrder->payment_status, ['paid', 'simulated_paid'], true))))
            <section class="virtual-qr-panel" aria-labelledby="virtual-qr-heading">
                <div>
                    <p class="eyebrow">Chế độ thử nghiệm · Không kết nối ngân hàng</p>
                    <h2 id="virtual-qr-heading">Mã QR thanh toán ảo</h2>
                    <p>Mã minh họa, không thể quét để chuyển tiền. Dùng nút mô phỏng bên dưới để thử quy trình.</p>
                </div>
                <svg class="virtual-qr" viewBox="0 0 21 21" role="img" aria-label="Mã QR trang trí, không thể dùng để thanh toán">
                    <rect width="21" height="21" fill="#fff"/>
                    <path fill="#16324f" d="M1 1h7v7H1zm1 1v5h5V2zm1 1h3v3H3zM13 1h7v7h-7zm1 1v5h5V2zm1 1h3v3h-3zM1 13h7v7H1zm1 1v5h5v-5zm1 1h3v3H3zM10 1h1v2h-1zm0 4h2v1h-2zm-1 3h2v2H9zm4 0h1v2h-1zm2 0h1v2h-1zm3 0h1v1h-1zM8 10h2v1H8zm3 0h1v2h-1zm2 0h2v1h-2zm3 0h1v2h-1zm3 0h1v1h-1zM8 12h1v2H8zm2 1h2v1h-2zm3 0h1v2h-1zm2 1h2v1h-2zm3-1h2v2h-2zM9 15h1v2H9zm2 1h2v1h-2zm3 1h1v2h-1zm2-1h1v2h-1zm2 2h2v1h-2zM9 19h3v1H9z"/>
                </svg>
            </section>
        @endif

        <section class="payment-methods" aria-labelledby="appointment-payment-heading">
            <h2 id="appointment-payment-heading">Phí đặt lịch bác sĩ</h2>
            <div class="payment-total">
                <span>Phí tư vấn</span>
                <strong>{{ $appointment->consultation_fee === null ? 'Chưa xác định' : ((float) $appointment->consultation_fee === 0.0 ? 'Miễn phí' : number_format((float) $appointment->consultation_fee, 0, ',', '.').' ₫') }}</strong>
            </div>
            <p>Bác sĩ: {{ $appointment->doctor->name }} · Lịch khám: {{ $appointment->starts_at->format('d/m/Y H:i') }}</p>
            <p>Trạng thái thanh toán:
                <strong>{{ match ($appointment->payment_status) {
                    'paid' => 'Đã thanh toán',
                    'simulated_paid' => 'Đã mô phỏng (chưa thanh toán thật)',
                    default => 'Chưa thanh toán',
                } }}</strong>
            </p>
            @if ($appointment->payment_status === 'paid')
                <div class="payment-simulation-result" role="status">
                    <h2>Đã nhận phí đặt lịch</h2>
                    <p>SePay đã xác nhận khoản thanh toán này.</p>
                    @if ($appointment->paid_at)
                        <p>Thời gian: {{ $appointment->paid_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            @elseif ($appointment->payment_status === 'simulated_paid')
                <div class="payment-simulation-notice" role="status">Giao dịch phí đặt lịch đã được mô phỏng. Chưa có tiền thật được chuyển.</div>
            @elseif ($appointment->status === 'cancelled')
                <div class="payment-simulation-notice" role="alert">Lịch đã hủy nên không thể thanh toán.</div>
            @elseif ($appointmentAmount === null && (float) $appointment->consultation_fee > 0)
                <div class="payment-simulation-notice" role="alert">Phí đặt lịch cần là số nguyên đồng Việt Nam để tạo giao dịch.</div>
            @elseif ($appointmentAmount !== null)
                @if ($appointmentPaymentEnabled)
                    <h3>Chuyển khoản phí đặt lịch qua SePay</h3>
                    <p><strong>Ngân hàng:</strong> {{ $bankCode }} · <strong>Số tài khoản:</strong> {{ $bankAccount }}</p>
                    <p><strong>Số tiền:</strong> {{ number_format($appointmentAmount, 0, ',', '.') }} ₫ · <strong>Nội dung:</strong> <code>{{ $appointmentReference }}</code></p>
                    <img src="{{ $appointmentQrUrl }}" alt="Mã QR thanh toán phí đặt lịch {{ $appointmentReference }}" width="280" height="280" loading="lazy">
                    <p>Khoản phí chỉ được xác nhận sau khi SePay nhận giao dịch.</p>
                @elseif (! $paymentReady)
                    <div class="payment-simulation-notice">Chưa cấu hình SePay thật. Mã QR ảo ở trên chỉ để thử giao diện, không chuyển tiền.</div>
                @endif
                @if ($sandboxEnabled)
                    <form method="POST" action="{{ route('patient.appointments.payment.sepay-sandbox', $appointment) }}">
                        @csrf
                        <input type="hidden" name="bill" value="appointment">
                        <input type="hidden" name="scenario" value="paid">
                        <button class="btn btn-primary" type="submit">Mô phỏng thanh toán phí đặt lịch</button>
                    </form>
                    <form method="POST" action="{{ route('patient.appointments.payment.sepay-sandbox', $appointment) }}" style="margin-top:10px;">
                        @csrf
                        <input type="hidden" name="bill" value="appointment">
                        <input type="hidden" name="scenario" value="wrong_amount">
                        <button class="btn btn-ghost" type="submit">Thử phí đặt lịch sai số tiền</button>
                    </form>
                @endif
            @endif
        </section>

        @if ($hasMedicineOrder)
        @if ($appointment->medicineOrder->payment_status === 'simulated_paid')
            <div class="payment-simulation-result" role="status">
                <h2>Đã hoàn tất thanh toán thử nghiệm SePay cho đơn thuốc</h2>
                <p><strong>Đây là giao dịch ảo. Không có tiền thật được chuyển và đơn chưa được xác nhận thanh toán thật.</strong></p>
                @if ($latestPayment?->provider_transaction_id)
                    <p>Mã giao dịch thử: {{ $latestPayment->provider_transaction_id }}</p>
                @endif
            </div>
        @elseif ($appointment->medicineOrder->payment_status === 'paid')
            <div class="payment-simulation-result" role="status">
                <h2>Đã nhận thanh toán đơn thuốc</h2>
                <p>SePay đã xác nhận giao dịch chuyển khoản của bạn.</p>
                @if ($appointment->medicineOrder->paid_at)
                    <p>Thời gian: {{ $appointment->medicineOrder->paid_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        @endif
        <section class="payment-order-summary" aria-labelledby="payment-order-heading">
            <h2 id="payment-order-heading">Thuốc được kê sau khám</h2>
            <ul>
                @foreach ($paymentItems as $item)
                    <li>
                        <span>
                            {{ $item['name'] }} × {{ $item['quantity'] }}
                            @if ($item['unitPrice'] !== null)
                                @php($formattedUnitPrice = number_format((float) $item['unitPrice'], 2, ',', '.'))
                                <small>({{ str_ends_with($formattedUnitPrice, ',00') ? substr($formattedUnitPrice, 0, -3) : $formattedUnitPrice }} ₫ / đơn vị)</small>
                            @endif
                            @if ($item['isEstimate'])
                                <small>Giá tham khảo, cần xác nhận trước khi mua.</small>
                            @endif
                            @if ($item['source'])
                                <small><a href="{{ $item['source'] }}" target="_blank" rel="noopener noreferrer">Nguồn tham khảo</a></small>
                            @endif
                        </span>
                        <strong>
                            {{ $item['lineTotalCents'] === null ? 'Chưa có giá' : number_format(intdiv($item['lineTotalCents'], 100), 0, ',', '.').($item['lineTotalCents'] % 100 === 0 ? '' : ','.str_pad((string) ($item['lineTotalCents'] % 100), 2, '0', STR_PAD_LEFT)).' ₫' }}
                        </strong>
                    </li>
                @endforeach
            </ul>
            <div class="payment-total">
                <span>Tổng tiền</span>
                <strong>
                    {{ $totalAmountCents === null ? 'Chưa có giá thuốc' : number_format(intdiv($totalAmountCents, 100), 0, ',', '.').($totalAmountCents % 100 === 0 ? '' : ','.str_pad((string) ($totalAmountCents % 100), 2, '0', STR_PAD_LEFT)).' ₫' }}
                </strong>
            </div>
        </section>

        @if ($unpricedMedicines !== [])
            <div class="payment-simulation-notice" role="alert">
                Chưa thể thanh toán: chưa có giá chính thức cho {{ implode(', ', array_unique($unpricedMedicines)) }}. Vui lòng chờ quản trị viên cập nhật.
            </div>
        @endif
        @if ($hasEstimates)
            <div class="payment-simulation-notice" role="note">
                Một hoặc nhiều giá trong đơn là giá tham khảo, không phải giá bán đã xác nhận. Tổng tiền chỉ để ước tính.
            </div>
        @endif

        @if ($latestPayment?->is_simulated && $latestPayment->status !== 'simulated_paid')
            <div class="payment-simulation-notice" role="status">
                Kết quả SePay ảo gần nhất: {{ match ($latestPayment->status) {
                    'amount_mismatch' => 'số tiền không khớp, đơn chưa thanh toán',
                    'price_not_confirmed' => 'giá chưa được xác nhận, đơn chưa thanh toán',
                    default => $latestPayment->status,
                } }}.
            </div>
        @endif

        @if ($sandboxEnabled && ! in_array($appointment->medicineOrder->payment_status, ['paid', 'simulated_paid'], true))
            <section class="payment-methods" aria-labelledby="sepay-sandbox-heading">
                <h2 id="sepay-sandbox-heading">SePay ảo — chỉ môi trường thử nghiệm</h2>
                <p>Không kết nối ngân hàng, không gửi tiền và không xác nhận thanh toán thật. Giá tham khảo sẽ bị từ chối như webhook thật.</p>
                <form method="POST" action="{{ route('patient.appointments.payment.sepay-sandbox', $appointment) }}">
                    @csrf
                    <input type="hidden" name="bill" value="medicine">
                    <input type="hidden" name="scenario" value="paid">
                    <button class="btn btn-primary" type="submit">Thử giao dịch SePay đúng số tiền</button>
                </form>
                <form method="POST" action="{{ route('patient.appointments.payment.sepay-sandbox', $appointment) }}" style="margin-top:10px;">
                    @csrf
                    <input type="hidden" name="bill" value="medicine">
                    <input type="hidden" name="scenario" value="wrong_amount">
                    <button class="btn btn-ghost" type="submit">Thử giao dịch sai số tiền</button>
                </form>
            </section>
        @endif

        @if ($medicinePaymentEnabled)
            <section class="payment-methods" aria-labelledby="bank-transfer-heading">
                <h2 id="bank-transfer-heading">Chuyển khoản ngân hàng qua SePay</h2>
                <p>Quét QR bằng ứng dụng ngân hàng, kiểm tra đúng số tiền và giữ nguyên nội dung chuyển khoản.</p>
                <p><strong>Ngân hàng:</strong> {{ $bankCode }}</p>
                <p><strong>Số tài khoản:</strong> {{ $bankAccount }}</p>
                <p><strong>Số tiền:</strong> {{ number_format(intdiv($totalAmountCents, 100), 0, ',', '.') }} ₫</p>
                <p><strong>Nội dung:</strong> <code>{{ $medicineReference }}</code></p>
                <img src="{{ $medicineQrUrl }}" alt="Mã QR chuyển khoản SePay cho đơn thuốc {{ $appointment->medicineOrder->id }}" width="280" height="280" loading="lazy">
                <p>Trạng thái chỉ chuyển thành đã thanh toán sau khi SePay xác nhận giao dịch.</p>
                <a class="btn btn-ghost" href="{{ route('patient.appointments.payment', $appointment) }}">Làm mới trạng thái</a>
            </section>
        @elseif ($appointment->medicineOrder->payment_status !== 'paid' && ! $simulated && ! $paymentReady)
            <div class="payment-simulation-notice" role="status">
                Cổng thanh toán thật chưa được cấu hình. Cần khai báo tài khoản nhận tiền và webhook SePay trước khi phát hành QR.
            </div>
        @elseif ($appointment->medicineOrder->payment_status !== 'paid' && ! $simulated && $hasEstimates)
            <div class="payment-simulation-notice" role="alert">
                Không thể thanh toán thật bằng giá ước tính. Quản trị viên cần xác nhận giá chính thức cho từng thuốc.
            </div>
        @elseif ($appointment->medicineOrder->payment_status !== 'paid' && ! $simulated && $totalAmountCents !== null && $totalAmountCents % 100 !== 0)
            <div class="payment-simulation-notice" role="alert">
                Tổng tiền cần là số nguyên đồng Việt Nam để tạo chuyển khoản. Vui lòng liên hệ quản trị viên để điều chỉnh giá thuốc.
            </div>
        @endif

        @if (! $medicinePaymentEnabled && ! $simulated && $appointment->medicineOrder->payment_status !== 'paid' && $totalAmountCents !== null && ! $hasEstimates && $unpricedMedicines === [])
            <form method="POST" action="{{ route('patient.medicine-orders.payment.simulate', $appointment) }}">
                @csrf
                <fieldset class="payment-methods">
                    <legend>Chỉ thử luồng mô phỏng (không chuyển tiền)</legend>
                    @foreach ($paymentMethods as $method => $label)
                        <label class="payment-method">
                            <input
                                type="radio"
                                name="payment_method"
                                value="{{ $method }}"
                                @checked(old('payment_method', 'vnpay') === $method)
                                required
                            >
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </fieldset>
                <button class="btn btn-primary" type="submit">Chạy mô phỏng thử (không thanh toán thật)</button>
            </form>
        @endif
        @endif
    </div>
@endsection
