@if($logs->total() > 0)
    @foreach($logs as $log)
        @php
            $when = $log->occurredAtSriLanka()->format('d M Y, h:i A');
            $softWrap = fn ($value) => $value ? preg_replace('/([\/._-])/', '$1' . "\u{200B}", $value) : '—';
        @endphp
        <tr id="audit-row-{{ $log->id }}" data-audit-id="{{ $log->id }}">
            <td class="col-select" data-label="">
                <input type="checkbox" class="form-check-input audit-checkbox" data-audit-id="{{ $log->id }}" aria-label="Select audit entry">
            </td>
            <td class="col-time" data-label="Time">
                <div>{{ $when }}</div>
                <small class="text-muted">Asia/Colombo</small>
            </td>
            <td class="col-staff" data-label="Staff">
                <div>
                    <div class="fw-semibold">{{ $log->user_name ?: 'Unknown' }}</div>
                    <small class="text-muted">{{ $log->user_role ?: '—' }}</small>
                    @if($log->user_email)
                        <div><small class="text-muted">{{ $log->user_email }}</small></div>
                    @endif
                </div>
            </td>
            <td class="col-action" data-label="Action">
                <div><span class="badge {{ $log->actionBadgeClass() }}">{{ $log->actionLabel() }}</span></div>
            </td>
            <td class="col-summary" data-label="Summary">
                <div>
                    {{ $log->summary }}
                    @if(!empty($log->new_values) && $log->action === 'updated')
                        <ul class="audit-change-list">
                            @foreach($log->new_values as $field => $value)
                                <li>{{ str_replace('_', ' ', $field) }}: {{ $log->old_values[$field] ?? 'empty' }} → {{ $value }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </td>
            <td class="col-item" data-label="Item"><div>{{ $log->item_label ?: '—' }}</div></td>
            <td class="col-page" data-label="Page">
                <div>
                    <div class="audit-path-text">{{ $softWrap($log->path) }}</div>
                    @if($log->route)
                        <small class="text-muted audit-route-text">{{ $softWrap($log->route) }}</small>
                    @endif
                    @if($log->method)
                        <div><small class="text-muted">{{ $log->method }}</small></div>
                    @endif
                </div>
            </td>
            <td class="col-ip" data-label="IP / Location">
                <div>
                    <div>{{ $log->ip_address ?: '—' }}</div>
                    <small class="text-muted">{{ $log->location ?: '—' }}</small>
                </div>
            </td>
            <td class="col-actions" data-label="Actions">
                <div>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-audit-btn" data-audit-id="{{ $log->id }}" title="Delete">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    @endforeach
@else
    <tr class="empty-row">
        <td colspan="9" class="text-center py-4">No audit entries yet. Staff create, update, delete, and login actions will appear here.</td>
    </tr>
@endif
