@extends('layouts.app')
@section('title', 'Quản lý giá thuốc')
@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Quản trị</p>
            <h1>Danh mục thuốc và giá bán</h1>
            <p class="subtitle">Giá tham khảo ước tính theo thuốc generic và hàm lượng phổ biến; liên kết chỉ giúp đối chiếu dạng thuốc, không xác nhận giá hiện hành. Vui lòng kiểm tra với nhà thuốc trước khi dùng giá làm giá bán chính thức.</p>
        </div>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tên thuốc</th>
                    <th>Hàm lượng</th>
                    <th>Đơn vị</th>
                    <th>Giá bán (VND)</th>
                    <th>Nguồn tham khảo</th>
                    <th>Cập nhật</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($medicines as $medicine)
                    <tr>
                        <td class="product-name">{{ $medicine->name }}</td>
                        <td>
                            <input form="medicine-form-{{ $medicine->id }}" class="form-control" name="strength" value="{{ $medicine->strength }}" maxlength="100" aria-label="Hàm lượng {{ $medicine->name }}">
                        </td>
                        <td>
                            <input form="medicine-form-{{ $medicine->id }}" class="form-control" name="unit" value="{{ $medicine->unit }}" maxlength="50" aria-label="Đơn vị {{ $medicine->name }}" placeholder="Ví dụ: viên">
                        </td>
                        <td>
                            <input form="medicine-form-{{ $medicine->id }}" class="form-control" name="price" type="number" min="0" max="9999999999.99" step="0.01" value="{{ $medicine->price === null ? '' : (str_ends_with($medicine->price, '.00') ? substr($medicine->price, 0, -3) : $medicine->price) }}" required aria-label="Giá {{ $medicine->name }}">
                            @if ($medicine->price === null)
                                <span class="form-help">Chưa nhập giá</span>
                            @elseif ($medicine->price_is_estimate)
                                <span class="form-help">Giá tham khảo — cần xác minh</span>
                            @else
                                @php($formattedPrice = number_format((float) $medicine->price, 2, ',', '.'))
                                <span class="form-help">{{ str_ends_with($formattedPrice, ',00') ? substr($formattedPrice, 0, -3) : $formattedPrice }} ₫</span>
                            @endif
                        </td>
                        <td>
                            <input form="medicine-form-{{ $medicine->id }}" class="form-control" type="url" name="price_source" value="{{ $medicine->price_source }}" maxlength="2048" aria-label="Nguồn giá {{ $medicine->name }}" placeholder="URL nguồn giá">
                        </td>
                        <td>
                            <form id="medicine-form-{{ $medicine->id }}" method="POST" action="{{ route('admin.medicines.update', $medicine) }}">
                            @csrf
                            @method('PATCH')
                                <button class="btn btn-primary" type="submit">Lưu</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
