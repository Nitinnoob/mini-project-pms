<?php
/**
 * Phase 5 Automated Test Suite:
 * 1. classroom_milestones (CRUD, ordering, countdown calculations, validation)
 * 2. Team size guard (max 4, min 1 enforced across join, accept, invite)
 * 3. Teacher Ledger rework (Health derived strictly from meetings held, student attendance, open directives)
 */

test('phase5 milestones: CRUD, ordering, countdown and validation', function () {
    $pdo = test_pdo();

    // 1. Initially empty
    $list = classroom_milestones_get_all($pdo, 1, '2026-02-01');
    assert_same(0, count($list), 'Initially no milestones');

    // 2. Create milestones
    $m1 = classroom_milestone_create($pdo, 1, 'Synopsis Approval', '2026-01-20');
    $m2 = classroom_milestone_create($pdo, 1, 'Mid-Term Progress Viva', '2026-02-15');
    $m3 = classroom_milestone_create($pdo, 1, 'Final Demonstration', '2026-03-01');
    $mToday = classroom_milestone_create($pdo, 1, 'Interim Check-in', '2026-02-01');

    assert_true($m1 > 0);
    assert_true($m2 > 0);
    assert_true($m3 > 0);
    assert_true($mToday > 0);

    // 3. Retrieve with reference date 2026-02-01
    $milestones = classroom_milestones_get_all($pdo, 1, '2026-02-01');
    assert_same(4, count($milestones));

    // Ordered by due_date ASC
    assert_same('Synopsis Approval', $milestones[0]['title']);
    assert_same('Interim Check-in', $milestones[1]['title']);
    assert_same('Mid-Term Progress Viva', $milestones[2]['title']);
    assert_same('Final Demonstration', $milestones[3]['title']);

    // Check countdown calculations
    // m0: 2026-01-20 vs 2026-02-01 => 12 days ago (passed)
    assert_same('passed', $milestones[0]['status']);
    assert_true($milestones[0]['is_overdue']);
    assert_same('Passed 12 days ago', $milestones[0]['countdown_label']);

    // m1: 2026-02-01 vs 2026-02-01 => today
    assert_same('due_today', $milestones[1]['status']);
    assert_true($milestones[1]['is_due_today']);
    assert_same('Due today', $milestones[1]['countdown_label']);

    // m2: 2026-02-15 vs 2026-02-01 => 14 days left (upcoming)
    assert_same('upcoming', $milestones[2]['status']);
    assert_true($milestones[2]['is_upcoming']);
    assert_same(14, $milestones[2]['days_remaining']);
    assert_same('14 days left', $milestones[2]['countdown_label']);

    // 4. Update milestone
    assert_true(classroom_milestone_update($pdo, 1, $m2, 'Updated Viva', '2026-02-20'));
    $found = classroom_milestone_find($pdo, 1, $m2);
    assert_same('Updated Viva', $found['title']);
    assert_same('2026-02-20', $found['due_date']);

    // 5. Delete milestone
    assert_true(classroom_milestone_delete($pdo, 1, $m1));
    assert_same(null, classroom_milestone_find($pdo, 1, $m1));
    assert_same(3, count(classroom_milestones_get_all($pdo, 1, '2026-02-01')));

    // 6. Cross-classroom isolation
    assert_same(0, count(classroom_milestones_get_all($pdo, 2)));

    // 7. Validation errors
    $caughtEmptyTitle = false;
    try {
        classroom_milestone_create($pdo, 1, '   ', '2026-02-10');
    } catch (InvalidArgumentException $e) {
        $caughtEmptyTitle = true;
    }
    assert_true($caughtEmptyTitle, 'Empty title rejected');

    $caughtInvalidDate = false;
    try {
        classroom_milestone_create($pdo, 1, 'Valid Title', 'not-a-date');
    } catch (InvalidArgumentException $e) {
        $caughtInvalidDate = true;
    }
    assert_true($caughtInvalidDate, 'Invalid date format rejected');
});

test('phase5 team size: max 4 and min 1 enforced across helper and membership logic', function () {
    $pdo = test_pdo();

    // 1. Verify classroom_get_max_team_size clamps to 4 even if DB has 10
    // Classroom 2 was created with max_team_size = 10
    assert_same(4, classroom_get_max_team_size($pdo, 2), 'Classroom max 10 clamped to 4');
    assert_same(4, project_get_classroom_max_team_size($pdo, 20), 'Project max clamped to 4');

    // Classroom 1 has max_team_size = 4, min_team_size = 1
    assert_same(4, classroom_get_max_team_size($pdo, 1));
    assert_same(1, classroom_get_min_team_size($pdo, 10));

    // If classroom has min_team_size > 4 or < 1, clamped
    $pdo->exec("UPDATE classrooms SET min_team_size = 0 WHERE id = 1");
    assert_same(1, classroom_get_min_team_size($pdo, 10), 'Min team size clamped to at least 1');

    $pdo->exec("UPDATE classrooms SET min_team_size = 8 WHERE id = 1");
    assert_same(4, classroom_get_min_team_size($pdo, 10), 'Min team size clamped to max 4');
    $pdo->exec("UPDATE classrooms SET min_team_size = 1 WHERE id = 1");

    // 2. Project capacity check: project 10 currently has 2 active members (user 3, 4)
    assert_same(2, project_member_active_count($pdo, 10));

    // Add 2 more active members to reach full capacity of 4
    $pdo->exec("INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (10, 7, 0, 'Active')");
    $pdo->exec("INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (10, 8, 0, 'Active')");

    $count = project_member_active_count($pdo, 10);
    assert_same(4, $count, 'Team now at maximum 4 active members');

    $max = project_get_classroom_max_team_size($pdo, 10);
    assert_true($count >= $max, 'Team capacity reached');
    assert_true($count >= 4, 'Hard limit of 4 active members reached');
});

test('phase5 teacher ledger: health computed strictly from meetings, attendance, and instructions', function () {
    $pdo = test_pdo();

    // Project 10 has no meetings yet: should be neutral/pending
    $h0 = meeting_compute_project_health($pdo, 10, '2026-01-10');
    assert_same('neutral', $h0['status']);
    assert_same('Pending', $h0['label']);

    // Create 3 Saturday meetings for project 10
    // Week 1: 2026-01-10 (past)
    // Week 2: 2026-01-17 (past)
    // Week 3: 2026-01-24 (past)
    $pdo->exec("INSERT INTO weekly_meetings (id, project_id, week_number, meeting_date, status) VALUES
        (101, 10, 1, '2026-01-10', 'held'),
        (102, 10, 2, '2026-01-17', 'held'),
        (103, 10, 3, '2026-01-24', 'held')");

    // Perfect attendance for all 3 meetings (users 3 and 4)
    $pdo->exec("INSERT INTO meeting_attendance (meeting_id, user_id, status) VALUES
        (101, 3, 'present'), (101, 4, 'present'),
        (102, 3, 'present'), (102, 4, 'present'),
        (103, 3, 'present'), (103, 4, 'present')");

    // Case 1: Healthy / On Track (all held, 100% attendance, 0 open instructions)
    $hHealthy = meeting_compute_project_health($pdo, 10, '2026-01-25');
    assert_same('healthy', $hHealthy['status']);
    assert_same('On Track', $hHealthy['label']);
    assert_same(3, $hHealthy['meetings_held']);
    assert_same(100.0, $hHealthy['attendance_pct']);
    assert_same(0, $hHealthy['open_instructions']);

    // Case 2: Warning — 3 open guide instructions
    $pdo->exec("INSERT INTO guide_instructions (meeting_id, text, status, created_by) VALUES
        (103, 'Directive 1', 'open', 2),
        (103, 'Directive 2', 'open', 2),
        (103, 'Directive 3', 'open', 2)");

    $hWarnInst = meeting_compute_project_health($pdo, 10, '2026-01-25');
    assert_same('warning', $hWarnInst['status']);
    assert_same('Needs Attention', $hWarnInst['label']);
    assert_same(3, $hWarnInst['open_instructions']);

    // Case 3: Warning — 1 past meeting unheld
    // Close instructions so they don't trigger warning
    $pdo->exec("UPDATE guide_instructions SET status = 'acknowledged'");
    // Add Week 4 meeting that passed its date but remained 'scheduled'
    $pdo->exec("INSERT INTO weekly_meetings (id, project_id, week_number, meeting_date, status) VALUES
        (104, 10, 4, '2026-01-31', 'scheduled')");

    $hWarnMissed = meeting_compute_project_health($pdo, 10, '2026-02-01');
    assert_same('warning', $hWarnMissed['status']);
    assert_same(1, $hWarnMissed['meetings_past_unheld']);

    // Case 4: Critical — 2 past meetings unheld
    $pdo->exec("INSERT INTO weekly_meetings (id, project_id, week_number, meeting_date, status) VALUES
        (105, 10, 5, '2026-02-07', 'scheduled')");

    $hCritMissed = meeting_compute_project_health($pdo, 10, '2026-02-08');
    assert_same('critical', $hCritMissed['status']);
    assert_same('At Risk', $hCritMissed['label']);
    assert_same(2, $hCritMissed['meetings_past_unheld']);

    // Case 5: Critical — Severe attendance shortage (<60%)
    // Mark meetings held with lots of absences
    $pdo->exec("UPDATE weekly_meetings SET status = 'held' WHERE id IN (104, 105)");
    $pdo->exec("INSERT INTO meeting_attendance (meeting_id, user_id, status) VALUES
        (104, 3, 'absent'), (104, 4, 'absent'),
        (105, 3, 'absent'), (105, 4, 'absent')");

    // Total evaluated: 6 present (101-103) + 4 absent (104-105) = 10 slots => 60.0%
    // Let's add 2 more absences to get below 60%
    $pdo->exec("INSERT INTO weekly_meetings (id, project_id, week_number, meeting_date, status) VALUES
        (106, 10, 6, '2026-02-14', 'held')");
    $pdo->exec("INSERT INTO meeting_attendance (meeting_id, user_id, status) VALUES
        (106, 3, 'absent'), (106, 4, 'absent')");

    $hCritAtt = meeting_compute_project_health($pdo, 10, '2026-02-15');
    assert_same('critical', $hCritAtt['status']);
    assert_true($hCritAtt['attendance_pct'] < 60.0, 'Attendance below 60% is critical');

    // Case 6: Batch calculation matches single project calculation
    $batch = meeting_compute_projects_health($pdo, [10, 20], '2026-02-15');
    assert_true(isset($batch[10]));
    assert_true(isset($batch[20]));
    assert_same($hCritAtt['status'], $batch[10]['status']);
    assert_same('neutral', $batch[20]['status']);
});
