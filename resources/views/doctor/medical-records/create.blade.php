@extends('layouts.app')
@section('title', 'Hồ sơ y tế')
@section('content')
@php($medications = \App\Support\MedicationCatalog::all())
<div class="page-heading"><div><p class="eyebrow">Bệnh nhân: {{ $appointment->patient->name }}</p><h1>Hồ sơ y tế</h1></div></div>
<div class="card form-card">
    <form id="medical-record-form" method="POST" action="{{ route('doctor.medical-records.store', $appointment) }}">
        @csrf

        <div class="form-group">
            <label for="diagnosis">Chẩn đoán <span class="required">*</span></label>
            <textarea class="form-control" id="diagnosis" name="diagnosis" rows="5" required>{{ old('diagnosis', $appointment->medicalRecord?->diagnosis) }}</textarea>
        </div>

        <div class="form-group">
            <label for="medicine-search">Tìm thuốc trong danh sách</label>
            <input class="form-control" id="medicine-search" type="search" autocomplete="off" placeholder="Nhập tên thuốc rồi nhấn Enter">
            <p class="form-help">Chọn thuốc trong danh sách hoặc nhấn Enter để thêm kết quả đầu tiên.</p>
        </div>

        <div class="form-group">
            <label>Thuốc đã thêm</label>
            <div class="medicine-selection" id="selected-medications" aria-live="polite"></div>
            <p class="form-help" id="selected-medications-empty">Chưa có thuốc nào được thêm.</p>
        </div>

        <div class="medicine-table-wrap">
            <table class="medicine-table">
                <thead>
                    <tr><th>Tên thuốc (chọn để thêm)</th></tr>
                </thead>
                <tbody id="medicine-options">
                    @foreach ($medications as $medication)
                        <tr data-medication-row>
                            <td>
                                <button class="medicine-option" type="button" data-medication-option data-medication="{{ $medication }}">
                                    <span>{{ $medication }}</span>
                                    <span class="medicine-option-action">Thêm</span>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="form-help" id="medicine-search-status" aria-live="polite">Hiển thị {{ count($medications) }} loại thuốc.</p>
        </div>

        <div class="form-group">
            <label for="prescription-details">Chi tiết đơn thuốc và cách dùng</label>
            <textarea class="form-control" id="prescription-details" name="prescription" rows="5">{{ old('prescription', $prescriptionDetails) }}</textarea>
            <p class="form-help">Nhập cách dùng hoặc lưu ý chung cho đơn thuốc.</p>
        </div>

        <div class="form-group">
            <label for="notes">Ghi chú dành cho bệnh nhân</label>
            <textarea class="form-control" id="notes" name="notes" rows="4" maxlength="5000">{{ old('notes', $appointment->doctor_notes ?? $appointment->medicalRecord?->notes) }}</textarea>
            <p class="form-help">Bệnh nhân có thể xem ghi chú này trong chi tiết lịch khám.</p>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Lưu hồ sơ</button>
        </div>
    </form>
</div>
<script>
    (() => {
        const form = document.getElementById('medical-record-form');
        const searchInput = document.getElementById('medicine-search');
        const medicineOptions = document.getElementById('medicine-options');
        const selection = document.getElementById('selected-medications');
        const emptySelectionMessage = document.getElementById('selected-medications-empty');
        const searchStatus = document.getElementById('medicine-search-status');
        const prescriptionDetails = document.getElementById('prescription-details');
        const medicineButtons = Array.from(medicineOptions.querySelectorAll('[data-medication-option]'));
        const medicineNames = medicineButtons.map((button) => button.dataset.medication);
        const selectedMedications = new Map();
        const knownMedicineNames = new Map(medicineNames.map((name) => [name.toLowerCase(), name]));

        const normalize = (value) => value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[đĐ]/g, 'd')
            .toLowerCase();

        const renderSelection = () => {
            selection.replaceChildren();
            emptySelectionMessage.hidden = selectedMedications.size > 0;

            Array.from(selectedMedications.entries()).forEach(([name, quantity], index) => {
                const row = document.createElement('div');
                row.className = 'selected-medicine-row';

                const label = document.createElement('span');
                label.className = 'selected-medicine-name';
                label.textContent = name;

                const nameInput = document.createElement('input');
                nameInput.type = 'hidden';
                nameInput.name = `medications[${index}][name]`;
                nameInput.value = name;

                const quantityLabel = document.createElement('label');
                quantityLabel.className = 'selected-medicine-quantity-label';
                quantityLabel.textContent = 'Số lượng';

                const quantityInput = document.createElement('input');
                quantityInput.className = 'form-control selected-medicine-quantity';
                quantityInput.type = 'number';
                quantityInput.name = `medications[${index}][quantity]`;
                quantityInput.value = quantity;
                quantityInput.min = '1';
                quantityInput.max = '100';
                quantityInput.required = true;
                quantityInput.setAttribute('aria-label', `Số lượng ${name}`);
                quantityInput.addEventListener('input', () => {
                    selectedMedications.set(name, quantityInput.value);
                });

                const removeButton = document.createElement('button');
                removeButton.className = 'medicine-remove';
                removeButton.type = 'button';
                removeButton.textContent = '×';
                removeButton.setAttribute('aria-label', `Xóa ${name}`);
                removeButton.addEventListener('click', () => {
                    selectedMedications.delete(name);
                    renderSelection();
                });

                quantityLabel.append(quantityInput);
                row.append(nameInput, label, quantityLabel, removeButton);
                selection.append(row);
            });

            medicineButtons.forEach((button) => {
                const isSelected = selectedMedications.has(button.dataset.medication);
                button.disabled = isSelected;
                button.setAttribute('aria-pressed', String(isSelected));
                button.querySelector('.medicine-option-action').textContent = isSelected ? 'Đã thêm' : 'Thêm';
            });
        };

        const addMedication = (name, quantity = 1) => {
            if (selectedMedications.has(name)) {
                return;
            }

            selectedMedications.set(name, quantity);
            renderSelection();
        };

        const filterMedications = () => {
            const query = normalize(searchInput.value.trim());
            let visibleCount = 0;

            medicineOptions.querySelectorAll('[data-medication-row]').forEach((row) => {
                const name = normalize(row.querySelector('[data-medication-option]').dataset.medication);
                row.hidden = query !== '' && !name.includes(query);
                visibleCount += Number(!row.hidden);
            });

            searchStatus.textContent = `Tìm thấy ${visibleCount} loại thuốc.`;
        };

        const initialMedications = {{ Illuminate\Support\Js::from(old('medications', $prescribedMedicationQuantities)) }};
        Object.entries(initialMedications).forEach(([key, value]) => {
            const submittedName = typeof value === 'object' && value !== null ? value.name : key;
            const knownName = knownMedicineNames.get(String(submittedName).trim().toLowerCase());

            if (knownName) {
                const quantity = typeof value === 'object' && value !== null ? value.quantity : value;
                addMedication(knownName, quantity || 1);
            }
        });

        renderSelection();

        searchInput.addEventListener('input', filterMedications);
        searchInput.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();
            const query = normalize(searchInput.value.trim());
            const firstMatch = medicineNames.find((name) => normalize(name).includes(query));

            if (query !== '' && firstMatch) {
                addMedication(firstMatch);
                searchInput.value = '';
                filterMedications();
            }
        });

        medicineButtons.forEach((button) => {
            button.addEventListener('click', () => {
                addMedication(button.dataset.medication);
            });
        });
    })();
</script>
@endsection
