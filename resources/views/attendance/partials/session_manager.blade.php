<div class="modal fade" id="attendanceSessionModal" tabindex="-1" aria-labelledby="attendanceSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <input type="time" class="form-control" id="session_edit_start" style="max-width: 10rem;" aria-label="Session start">
                        <span class="text-muted">to</span>
                        <input type="time" class="form-control" id="session_edit_end" style="max-width: 10rem;" aria-label="Session end">
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
