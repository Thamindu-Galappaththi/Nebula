@if($modules->total() > 0)
    @foreach($modules as $module)
        <tr id="module-row-{{ $module->module_id }}" data-module-id="{{ $module->module_id }}" data-category="{{ $module->module_category ?? 'degree' }}" data-type="{{ $module->module_type }}" data-credits="{{ $module->credits }}">
            <td class="module-select-cell" data-label="">
                <input type="checkbox" class="form-check-input module-checkbox" data-module-id="{{ $module->module_id }}" aria-label="Select {{ $module->module_name }}">
            </td>
            <td class="module-name" data-label="Module Name" style="text-transform:none !important;">{{ $module->module_name }}</td>
            <td class="module-code" data-label="Module Code" style="text-transform:none !important;">{{ $module->module_code }}</td>
            <td class="module-category" data-label="Category">
                @if(($module->module_category ?? 'degree') === 'certificate')
                    <span class="badge bg-success">Certificate</span>
                @else
                    <span class="badge bg-secondary">Degree/Diploma</span>
                @endif
            </td>
            <td class="module-credits" data-label="Credits">
                @if(($module->module_category ?? 'degree') === 'certificate')
                    N/A
                @else
                    {{ $module->credits ?? 'N/A' }}
                @endif
            </td>
            <td class="module-type" data-label="Type">
                @if(($module->module_category ?? 'degree') === 'certificate')
                    <span class="text-muted">-</span>
                @elseif($module->module_type === 'core')
                    <span class="badge bg-primary">Core</span>
                @elseif($module->module_type === 'elective')
                    <span class="badge bg-info">Elective</span>
                @elseif($module->module_type === 'special_unit_compulsory')
                    <span class="badge bg-warning">S/U</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td class="module-actions-cell" data-label="Actions">
                <div class="btn-group btn-group-sm module-actions" role="group">
                    <button type="button" class="btn btn-outline-primary edit-module-btn" title="Edit">
                        <i class="ti ti-edit"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger delete-module-btn" title="Delete">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    @endforeach
@else
    <tr class="empty-row">
        <td colspan="7" class="text-center py-4">No modules found.</td>
    </tr>
@endif
