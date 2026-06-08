<?php use App\Helpers\View; View::startSection('styles'); ?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<style>
    .fc-theme-bootstrap5 a {
        text-decoration: none;
    }
    .fc-event {
        cursor: pointer;
        padding: 2px 5px;
        font-weight: 500;
        font-size: 0.8rem;
        border-radius: 4px;
    }
</style>
<?php View::endSection(); View::startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1>Maintenance Calendar</h1>
        <p>Calendar view of preventive maintenance, corrective repairs, inspections, and emergency work orders.</p>
    </div>
    <div class="page-actions">
        <a href="<?= View::url('maintenance') ?>" class="btn btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a href="<?= View::url('maintenance/work-orders') ?>" class="btn btn-outline-primary"><i class="bi bi-list-check me-1"></i>Work Orders</a>
        <a href="<?= View::url('maintenance/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Schedule</a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>
<?php View::endSection(); View::startSection('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                themeSystem: 'standard',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                events: '<?= View::url("maintenance/api/calendar/events") ?>',
                eventClick: function(info) {
                    if (info.event.url) {
                        window.location.href = info.event.url;
                        info.jsEvent.preventDefault();
                    }
                }
            });
            calendar.render();
        }
    });
</script>
<?php View::endSection(); ?>
