<?php
// Dashboard page shell. Presentation only - no SQL.
require 'views/header.php';
require __DIR__ . '/partials/flash_alerts.php';

echo '<div class="flex-1 p-6 grid grid-cols-1 gap-6">';

if ($viewData['actualView'] === 'Student') {
    require 'views/student.php';
} elseif ($viewData['actualView'] === 'Marketplace') {
    require 'views/marketplace.php';
} elseif ($viewData['actualView'] === 'Teacher') {
    require 'views/teacher.php';
} else {
    header('Location: hub.php');
    exit;
}

echo '</div>';

// Modals available for teacher drilldown
if ($viewData['actualView'] === 'Student' && !empty($viewData['isTeacherDrilldown'])) {
    require 'views/meeting_modal.php';
    require 'views/marks_modal.php';
}

require 'views/footer.php';
