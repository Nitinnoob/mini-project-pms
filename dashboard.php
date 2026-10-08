<?php
// Thin router: resolve context, load role-specific data, render. No feature SQL here.
require_once 'bootstrap.php';
require_once 'phase_engine.php';
require_once 'views/helpers.php';

// Prevent caching so the back button doesn't work after logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_login();

require 'controllers/dashboard_context.php';   // $viewData, $modeSlug, $headerTitle, $username, $initial
require 'controllers/dashboard_common.php';    // calendar, phases, defaults

match ($viewData['actualView']) {
    'Teacher'        => require 'controllers/teacher_dashboard.php',
    'Project Leader' => require 'controllers/leader_dashboard.php',
    'Student'        => require 'controllers/student_dashboard.php',
    'Marketplace'    => require 'controllers/marketplace_dashboard.php',
};

require_once 'views/view_contract.php';
$viewData = enforce_view_contract($viewData);
require 'views/layout.php';
