<?php
// Dashboard page shell. Presentation only - no SQL.
require 'views/header.php';
require __DIR__ . '/partials/flash_alerts.php';

echo '<div class="flex-1 p-6 grid grid-cols-1 gap-6">';

if ($viewData['actualView'] === 'Student') {
    require 'views/student.php';
} elseif ($viewData['actualView'] === 'Marketplace') {
    require 'views/marketplace.php';
} elseif ($viewData['actualView'] === 'Project Leader') {
    require 'views/leader.php';
} else {
    require 'views/teacher.php';
}

echo '</div>';

if (($viewData['actualView'] === 'Student' || $viewData['actualView'] === 'Project Leader')) {
    require 'views/weekly_log_modal.php';
    require 'views/report_assistant_modal.php';
    if (!empty($viewData['isTeacherDrilldown'])) {
        require 'views/mentor_review_modal.php';
        require 'views/attendance_modal.php';
    }
}

require 'views/footer.php';
