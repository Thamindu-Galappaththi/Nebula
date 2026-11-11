<style nonce="{{ $cspNonce }}">
    #attendanceSessionModal .modal-dialog {
        margin: 0.75rem auto;
        max-width: min(32rem, calc(100vw - 1.5rem));
        width: calc(100% - 1.5rem);
    }
    #attendanceSessionModal .modal-content {
        max-height: min(100dvh - 1.5rem, 100%);
    }
    #attendanceSessionModal .modal-title {
        overflow-wrap: anywhere;
        padding-right: 0.5rem;
    }
    #attendanceSessionModal .form-control,
    #attendanceSessionModal .form-select {
        width: 100%;
        max-width: 100%;
    }
    .session-time-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .session-time-row .form-control {
        flex: 1 1 8.5rem;
        min-width: 0;
        max-width: 100%;
    }
    #attendanceSessionModal .modal-footer {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: stretch;
    }
    #attendanceSessionModal .modal-footer .btn {
        flex: 1 1 8rem;
        margin: 0;
    }
    .swal2-container {
        padding: 0.75rem !important;
    }
    .swal2-popup.attendance-swal {
        width: min(26rem, calc(100vw - 1.5rem)) !important;
        max-width: 100%;
    }
    @media (max-width: 575.98px) {
        #attendanceSessionModal .modal-dialog {
            margin: 0.5rem auto;
            max-width: none;
            width: calc(100% - 1rem);
        }
        #attendanceSessionModal .modal-header,
        #attendanceSessionModal .modal-body,
        #attendanceSessionModal .modal-footer {
            padding: 0.85rem;
        }
        #attendanceSessionModal .form-control,
        #attendanceSessionModal .form-select {
            font-size: 16px;
        }
        .session-time-row {
            flex-direction: column;
            align-items: stretch;
        }
        .session-time-row .form-control {
            flex: 1 1 auto;
            width: 100%;
        }
        .session-time-row .session-time-sep {
            text-align: center;
        }
        #attendanceSessionModal .modal-footer .btn {
            flex: 1 1 100%;
            width: 100%;
        }
    }
</style>
<div class="modal fade" id="attendanceSessionModal" tabindex="-1" aria-labelledby="attendanceSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceSessionModalLabel">Edit attendance session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Present and absent marks stay the same. Only the date, session type, and time are changed.</p>
                <input type="hidden" id="session_original_date">
                <input type="hidden" id="session_original_type">
                <input type="hidden" id="session_original_start">
                <div class="mb-3">
                    <label for="session_edit_date" class="form-label fw-bold">Date</label>
                    <input type="date" class="form-control" id="session_edit_date" required>
                </div>
                <div class="mb-3">
                    <label for="session_edit_type" class="form-label fw-bold">Attendance type</label>
                    <select class="form-select" id="session_edit_type">
                        <option value="lectures">Lectures</option>
                        <option value="labs">Labs</option>
                        <option value="special_lectures">Special Lectures</option>
                        <option value="tutorials">Tutorials</option>
                        <option value="other">Other Categories</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-bold">Session time</label>
                    <div class="session-time-row">
                        <input type="time" class="form-control" id="session_edit_start" aria-label="Session start">
                        <span class="text-muted session-time-sep">to</span>
                        <input type="time" class="form-control" id="session_edit_end" aria-label="Session end">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sessionSaveBtn">Save changes</button>
            </div>
        </div>
    </div>
</div>
