@extends('layouts.app')
@section('title', 'Yêu cầu mua thuốc')
@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Lịch khám #{{ $appointment->id }}</p>
            <h1>Yêu cầu mua thuốc theo đơn</h1>
            <p class="subtitle">Chọn những thuốc bác sĩ đã kê và số lượng bạn cần.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('patient.appointments.show', $appointment) }}">Quay lại lịch khám</a>
    </div>

    <div class="card form-card">
        @if ($appointment->medicineOrder)
            <div class="medicine-order-confirmation">
                <h2>Đã nhận yêu cầu mua thuốc</h2>
                <p>Giá thuốc hiển thị tại trang thanh toán. Chỉ giá đã xác nhận mới được thanh toán; giá tham khảo không được dùng cho giao dịch thật.</p>
                <ul>
                    @foreach ($appointment->medicineOrder->items as $item)
                        <li>{{ $item->medicine_name }} <strong>× {{ $item->quantity }}</strong></li>
                    @endforeach
                </ul>
                <a class="btn btn-primary" href="{{ route('patient.appointments.payment', $appointment) }}">Đến trang thanh toán</a>
            </div>
        @else
            <div class="medicine-order-notice">
                Đây là yêu cầu mua thuốc theo đơn. Sau khi gửi yêu cầu, bạn có thể xem giá và thanh toán trên trang thanh toán.
            </div>

            <form method="POST" action="{{ route('patient.medicine-orders.store', $appointment) }}">
                @csrf
                @foreach ($medicines as $index => $medicine)
                    <div class="medicine-order-row">
                        <div>
                            <label class="medicine-select-label" for="medicine-selected-{{ $index }}">
                                <input
                                    id="medicine-selected-{{ $index }}"
                                    type="checkbox"
                                    name="items[{{ $index }}][selected]"
                                    value="1"
                                >
                                <strong>{{ $medicine }}</strong>
                            </label>
                            <input type="hidden" name="items[{{ $index }}][medicine_name]" value="{{ $medicine }}">
                            @if (isset($medicineQuantities[$medicine]))
                                <p class="form-help">Bác sĩ kê: {{ $medicineQuantities[$medicine] }}</p>
                            @endif
                        </div>
                        <label class="medicine-quantity-label" for="medicine-quantity-{{ $index }}">
                            Số lượng
                            <input
                                class="form-control medicine-quantity"
                                id="medicine-quantity-{{ $index }}"
                                type="number"
                                name="items[{{ $index }}][quantity]"
                                value="{{ old("items.$index.quantity", $medicineQuantities[$medicine] ?? 1) }}"
                                min="1"
                                max="100"
                                required
                            >
                        </label>
                    </div>
                @endforeach

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Gửi yêu cầu mua thuốc</button>
                </div>
            </form>
        @endif
    </div>
@endsection
