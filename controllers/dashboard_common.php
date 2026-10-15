<?php
// Shared state for every view. No phase engine dependency.

// No more phases/schedule - meetings are created on-demand by guides.
$viewData['hasSchedule'] = true; // Always true now (no schedule gating)
$viewData['phases'] = [];
$viewData['totalWeeks'] = 0;

// Defaults
$viewData['teamRoster'] = [];
$viewData['meetings'] = [];
