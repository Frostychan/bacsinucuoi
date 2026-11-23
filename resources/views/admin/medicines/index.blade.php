@extends('layouts.app')
@section('title', 'Quản lý giá thuốc')
@section('content')
    @if (session('error'))
        <div class="alert" role="alert">{{ session('error') }}</div>
    @endif

    <div class="page-heading">
        <div>
            <p class="eyebrow">Quản trị</p>
            <h1>Danh mục thuốc và giá bán</h1>
            <p class="subtitle">Giá tham khảo ước tính theo thuốc generic và hàm lượng phổ biến; liên kết chỉ giúp đối chiếu dạng thuốc, không xác nhận giá hiện hành. Vui lòng kiểm tra với nhà thuốc trước khi dùng giá làm giá bán chính thức.</p>
        </div>
    </div>

    <section class="card" style="margin-bottom: 24px; padding: 22px;">
        <div>
            <h2>Thêm thuốc vào danh mục</h2>
            <p class="form-help">Danh mục được cập nhật tự động từ openFDA. Bạn cũng có thể thêm thuốc hoặc tên biệt dược ngoài danh mục.</p>
        </div>
        <form method="POST" action="{{ route('admin.medicines.store') }}" enctype="multipart/form-data" style="display: flex; align-items: flex-end; flex-wrap: wrap; gap: 12px; margin-top: 16px;">
            @csrf
            <div style="flex: 1 1 180px;">
                <label for="medicine-image">Ảnh thuốc</label>
                <input id="medicine-image" class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </div>
            <div style="flex: 2 1 220px;">
                <label for="medicine-name">Tên thuốc</label>
                <input id="medicine-name" class="form-control" name="name" value="{{ old('name') }}" maxlength="255" required placeholder="Ví dụ: Efferalgan">
            </div>
            <div style="flex: 1 1 150px;">
                <label for="medicine-strength">Hàm lượng</label>
                <input id="medicine-strength" class="form-control" name="strength" value="{{ old('strength') }}" maxlength="100" placeholder="Ví dụ: 500 mg">
            </div>
            <div style="flex: 1 1 120px;">
                <label for="medicine-unit">Đơn vị</label>
                <input id="medicine-unit" class="form-control" name="unit" value="{{ old('unit') }}" maxlength="50" placeholder="Ví dụ: viên">
            </div>
            <button type="submit" class="btn btn-primary">Thêm thuốc</button>
        </form>
        @error('name') <p class="form-help booking-error">{{ $message }}</p> @enderror
    </section>

    <section class="card" style="margin-bottom: 24px; padding: 22px;">
        <h2>Tra cứu nhãn thuốc openFDA</h2>
        <p class="form-help">Tìm nhãn thuốc Hoa Kỳ theo tên biệt dược hoặc hoạt chất. Kết quả chỉ để tham khảo, không dùng để quyết định chăm sóc y tế; nội dung do đơn vị nộp cho FDA và có thể không phải nhãn đang lưu hành hoặc đã được xác nhận.</p>
        <form method="GET" action="{{ route('admin.medicines.index') }}" style="display: flex; align-items: flex-end; flex-wrap: wrap; gap: 12px; margin-top: 16px;">
            <div style="flex: 1 1 240px;">
                <label for="fda-medicine-search">Tên thuốc</label>
                <input id="fda-medicine-search" class="form-control" type="search" name="search" value="{{ old('search', $search) }}" minlength="2" maxlength="100" placeholder="Ví dụ: Tylenol hoặc acetaminophen" required>
                @error('search') <p class="form-help booking-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-primary">Tra cứu FDA</button>
        </form>

        @if ($search !== '')
            <div style="margin-top: 20px;">
                @if ($fdaError)
                    <p class="alert" role="alert">{{ $fdaError }}</p>
                @elseif ($fdaLabels === [])
                    <p class="form-help">Không tìm thấy nhãn thuốc phù hợp với “{{ $search }}”.</p>
                @else
                    <h3>Kết quả cho “{{ $search }}”</h3>
                    <div class="catalog-grid">
                        @foreach ($fdaLabels as $label)
                            <article class="card" style="padding: 20px;">
                                <h3>{{ $label['brand_name'] ?? 'Không có tên biệt dược' }}</h3>
                                @if ($label['generic_name'])
                                    <p><strong>Hoạt chất / tên generic:</strong> {{ $label['generic_name'] }}</p>
                                @endif
                                @if ($label['active_ingredient'])
                                    <p><strong>Thành phần hoạt tính:</strong> {{ $label['active_ingredient'] }}</p>
                                @endif
                                @if ($label['manufacturer_name'])
                                    <p><strong>Nhà sản xuất:</strong> {{ $label['manufacturer_name'] }}</p>
                                @endif
                                @if ($label['dosage_form'] || $label['route'])
                                    <p><strong>Dạng / đường dùng:</strong> {{ $label['dosage_form'] }}{{ $label['dosage_form'] && $label['route'] ? ' · ' : '' }}{{ $label['route'] }}</p>
                                @endif
                                @if ($label['purpose'])
                                    <p><strong>Mục đích ghi trên nhãn:</strong> {{ $label['purpose'] }}</p>
                                @endif
                                @if ($label['indications_and_usage'])
                                    <details>
                                        <summary>Công dụng ghi trên nhãn</summary>
                                        <p style="white-space: pre-line;">{{ $label['indications_and_usage'] }}</p>
                                    </details>
                                @endif
                                @if ($label['warnings'])
                                    <details>
                                        <summary>Cảnh báo trên nhãn</summary>
                                        <p style="white-space: pre-line;">{{ $label['warnings'] }}</p>
                                    </details>
                                @endif
                                @if ($label['dosage_and_administration'])
                                    <details>
                                        <summary>Hướng dẫn sử dụng trên nhãn</summary>
                                        <p style="white-space: pre-line;">{{ $label['dosage_and_administration'] }}</p>
                                    </details>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    <p class="form-help" style="margin-top: 14px;">Nguồn dữ liệu: openFDA Drug Product Labeling API.</p>
                @endif
            </div>
        @endif
    </section>

    <form method="GET" action="{{ route('admin.medicines.index') }}" class="card" style="margin-bottom: 16px; padding: 16px 22px; display: flex; align-items: flex-end; gap: 12px;">
        <div style="flex: 1 1 240px;">
            <label for="catalog-search">Tra cứu trong danh mục đã lưu</label>
            <input id="catalog-search" class="form-control" type="search" name="catalog_search" value="{{ $catalogSearch }}" maxlength="100" placeholder="Nhập tên thuốc">
        </div>
        <button type="submit" class="btn btn-primary">Tìm</button>
        @if ($catalogSearch !== '')
            <a href="{{ route('admin.medicines.index') }}" class="btn btn-secondary">Xóa lọc</a>
        @endif
    </form>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ảnh</th>
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
                        <td>
                            @php($medicineImage = $medicine->image_path
                                ? (str_starts_with($medicine->image_path, 'images/') ? asset($medicine->image_path) : asset('storage/'.$medicine->image_path))
                                : asset(\App\Support\MedicationCatalog::imagePath($medicine->name)))
                            <img class="medicine-admin-photo" src="{{ $medicineImage }}" alt="Ảnh thuốc {{ $medicine->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/medicines/placeholder.svg') }}';">
                            <input form="medicine-form-{{ $medicine->id }}" class="form-control medicine-image-input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-label="Ảnh {{ $medicine->name }}">
                        </td>
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
                            <form id="medicine-form-{{ $medicine->id }}" method="POST" action="{{ route('admin.medicines.update', $medicine) }}" enctype="multipart/form-data">
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
