<script nonce="{{ $cspNonce }}">
$(function () {
    const $course = $('#courseFilter');
    const $intake = $('#intakeFilter');

    function updateIntakeOptions() {
        if (!$course.length || !$intake.length) {
            return;
        }
        const courseId = String($course.val() || '');
        $intake.find('option[data-course-id]').each(function () {
            this.hidden = Boolean(courseId) && String(this.getAttribute('data-course-id')) !== courseId;
        });
        const selected = $intake.find('option:selected').get(0);
        if (selected && selected.hidden) {
            $intake.val('');
            $intake.trigger('change');
        }
    }

    $course.on('change', function () {
        $intake.val('');
        updateIntakeOptions();
        $intake.trigger('change');
    });
    updateIntakeOptions();

    function loadClearancePage(url, pushUrl) {
        const $pending = $('#pendingRequestsBody');
        const $processed = $('#processedRequestsBody');
        $pending.addClass('opacity-50');
        $processed.addClass('opacity-50');

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res && res.pending_html) {
                    $pending.html(res.pending_html);
                }
                if (res && res.processed_html) {
                    $processed.html(res.processed_html);
                }
                if (pushUrl) {
                    history.pushState({ clearanceAjax: true }, '', url);
                }
            },
            error: function () {
                if (window.Swal) {
                    Swal.fire({ title: 'Error', text: 'Failed to load the next page.', icon: 'error', confirmButtonText: 'OK' });
                } else {
                    window.alert('Failed to load the next page.');
                }
            },
            complete: function () {
                $pending.removeClass('opacity-50');
                $processed.removeClass('opacity-50');
            }
        });
    }

    $(document).on('click', '.clearance-management-page .clearance-pagination a.page-link', function (e) {
        const href = $(this).attr('href');
        if (!href || href === '#' || $(this).closest('.page-item').hasClass('disabled') || $(this).closest('.page-item').hasClass('active')) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        loadClearancePage(href, true);
    });

    window.addEventListener('popstate', function () {
        if (!$('.clearance-management-page').length) {
            return;
        }
        loadClearancePage(window.location.href, false);
    });
});
</script>
