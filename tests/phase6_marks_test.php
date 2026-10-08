<?php
declare(strict_types=1);

test('phase6 config: assessment weights defined in configuration', function () {
    $cfg = marks_get_config();
    assert_same(50.0, (float)$cfg['max_report_marks'], 'Max report marks must be 50');
    assert_same(25.0, (float)$cfg['max_presentation_marks'], 'Max presentation marks must be 25');
    assert_same(25.0, (float)$cfg['max_qa_marks'], 'Max QA marks must be 25');
    assert_same(100.0, (float)$cfg['total_max_marks'], 'Total marks must be 100');
    assert_same(75.0, (float)$cfg['attendance_shortage_threshold'], 'Attendance shortage threshold is 75%');
});

test('phase6 auth: evaluator access restricted to assigned mentor or classroom admin', function () {
    $pdo = test_pdo();
    // Project 10: mentor = 2, classroom Admin = 1, leader = 3, member = 4, outsider = 6
    assert_true(can_evaluate_project($pdo, 10, 2), 'Mentor 2 can evaluate project 10');
    assert_true(can_evaluate_project($pdo, 10, 1), 'Admin 1 can evaluate project 10');
    assert_false(can_evaluate_project($pdo, 10, 3), 'Leader 3 cannot evaluate project 10');
    assert_false(can_evaluate_project($pdo, 10, 4), 'Member 4 cannot evaluate project 10');
    assert_false(can_evaluate_project($pdo, 10, 6), 'Outsider 6 cannot evaluate project 10');

    // Admin override check:
    assert_true(can_override_finalized_marks($pdo, 10, 1), 'Classroom Admin can override finalized marks');
    assert_false(can_override_finalized_marks($pdo, 10, 2), 'Plain mentor cannot override finalized marks');
    assert_false(can_override_finalized_marks($pdo, 10, 3), 'Leader cannot override finalized marks');
});

test('phase6 validation: marks out-of-range boundaries rejected', function () {
    $pdo = test_pdo();

    // Report marks > 50 rejected
    $errReport = false;
    try {
        marks_save($pdo, 10, 2, 51.0, [3 => ['presentation_marks' => 20, 'qa_marks' => 20]]);
    } catch (InvalidArgumentException $e) {
        $errReport = true;
    }
    assert_true($errReport, 'Report marks > 50 must throw exception');

    // Report marks < 0 rejected
    $errNegReport = false;
    try {
        marks_save($pdo, 10, 2, -1.0, []);
    } catch (InvalidArgumentException $e) {
        $errNegReport = true;
    }
    assert_true($errNegReport, 'Report marks < 0 must throw exception');

    // Presentation marks > 25 rejected
    $errPres = false;
    try {
        marks_save($pdo, 10, 2, 45.0, [3 => ['presentation_marks' => 26.0, 'qa_marks' => 20]]);
    } catch (InvalidArgumentException $e) {
        $errPres = true;
    }
    assert_true($errPres, 'Presentation marks > 25 must throw exception');

    // Q&A marks > 25 rejected
    $errQa = false;
    try {
        marks_save($pdo, 10, 2, 45.0, [3 => ['presentation_marks' => 20.0, 'qa_marks' => 25.5]]);
    } catch (InvalidArgumentException $e) {
        $errQa = true;
    }
    assert_true($errQa, 'Q&A marks > 25 must throw exception');
});

test('phase6 calculation & sheet: draft save and total computation with attendance', function () {
    $pdo = test_pdo();

    // Setup a Saturday meeting with attendance for project 10
    $pdo->exec("
        INSERT INTO weekly_meetings (id, project_id, week_number, meeting_date, status) VALUES (101, 10, 1, '2026-01-10', 'held');
        INSERT INTO meeting_attendance (meeting_id, user_id, status, marked_by) VALUES (101, 3, 'present', 2), (101, 4, 'absent', 2);
    ");

    $sheet = marks_save($pdo, 10, 2, 45.0, [
        3 => ['presentation_marks' => 22.5, 'qa_marks' => 23.0],
        4 => ['presentation_marks' => 20.0, 'qa_marks' => 19.5],
    ], false);

    assert_false($sheet['is_finalized'], 'Marks saved as draft');
    assert_same(45.0, $sheet['project_marks']['report_marks'], 'Project report marks stored');
    assert_same(2, count($sheet['students']), '2 active students returned');

    // Student 3 (Leader): 45 report + 22.5 pres + 23.0 qa = 90.5
    $s3 = $sheet['students'][0]['user_id'] === 3 ? $sheet['students'][0] : $sheet['students'][1];
    assert_same(90.5, $s3['total_marks'], 'Student 3 total marks is 90.5');
    assert_same(90.5, $s3['percentage'], 'Student 3 percentage is 90.5%');
    assert_same(100.0, $s3['attendance_pct'], 'Student 3 attendance is 100%');
    assert_false($s3['is_shortage'], 'Student 3 no attendance shortage');

    // Student 4 (Member): 45 report + 20.0 pres + 19.5 qa = 84.5
    $s4 = $sheet['students'][0]['user_id'] === 4 ? $sheet['students'][0] : $sheet['students'][1];
    assert_same(84.5, $s4['total_marks'], 'Student 4 total marks is 84.5');
    assert_same(0.0, $s4['attendance_pct'], 'Student 4 attendance is 0%');
    assert_true($s4['is_shortage'], 'Student 4 has shortage alert');
});

test('phase6 lock: finalized marks reject guide/non-admin modifications', function () {
    $pdo = test_pdo();

    // Finalize marks as guide (user 2)
    marks_save($pdo, 10, 2, 44.0, [
        3 => ['presentation_marks' => 21.0, 'qa_marks' => 22.0],
        4 => ['presentation_marks' => 20.0, 'qa_marks' => 20.0],
    ], true);

    $projMarks = marks_get_project_marks($pdo, 10);
    assert_true($projMarks['is_finalized'], 'Marks must be finalized');
    assert_same(2, $projMarks['finalized_by'], 'Finalized by user 2');

    // Attempt modification by guide (user 2 is mentor, not classroom admin)
    $lockPrevented = false;
    try {
        marks_save($pdo, 10, 2, 48.0, [3 => ['presentation_marks' => 25.0, 'qa_marks' => 25.0]]);
    } catch (RuntimeException $e) {
        $lockPrevented = true;
    }
    assert_true($lockPrevented, 'Guide modification of finalized marks must be rejected');
});

test('phase6 audit: admin modification of finalized marks requires reason and logs to marks_changes', function () {
    $pdo = test_pdo();

    // Finalize marks first
    marks_save($pdo, 10, 2, 40.0, [
        3 => ['presentation_marks' => 20.0, 'qa_marks' => 20.0],
    ], true);

    // Admin (user 1) modifying without reason must fail
    $noReasonFailed = false;
    try {
        marks_save($pdo, 10, 1, 45.0, [3 => ['presentation_marks' => 22.0, 'qa_marks' => 20.0]], false, '');
    } catch (InvalidArgumentException $e) {
        $noReasonFailed = true;
    }
    assert_true($noReasonFailed, 'Admin edit of finalized marks without reason must fail');

    // Admin modifying with reason succeeds and logs audit
    $updated = marks_save($pdo, 10, 1, 45.0, [
        3 => ['presentation_marks' => 22.0, 'qa_marks' => 20.0],
    ], false, 'Re-evaluated project report quality and viva presentation');

    assert_same(45.0, $updated['project_marks']['report_marks'], 'Report marks updated to 45');

    // Verify audit logs in marks_changes
    $auditHistory = marks_get_audit_history($pdo, 10);
    assert_true(count($auditHistory) >= 2, 'At least 2 changes logged (report_marks and presentation_marks)');

    $fieldsChanged = array_column($auditHistory, 'field_name');
    assert_true(in_array('report_marks', $fieldsChanged, true), 'report_marks logged in audit trail');
    assert_true(in_array('presentation_marks', $fieldsChanged, true), 'presentation_marks logged in audit trail');
});

test('phase6 unlock: admin can unlock finalized marks with reason', function () {
    $pdo = test_pdo();

    // Finalize marks first
    marks_save($pdo, 10, 2, 42.0, [
        3 => ['presentation_marks' => 20.0, 'qa_marks' => 20.0],
    ], true);

    // Mentor (user 2) unlocking fails
    $mentorUnlockFailed = false;
    try {
        marks_unlock($pdo, 10, 2, 'Want to edit');
    } catch (RuntimeException $e) {
        $mentorUnlockFailed = true;
    }
    assert_true($mentorUnlockFailed, 'Non-admin cannot unlock marks');

    // Admin unlocking without reason fails
    $noReasonUnlockFailed = false;
    try {
        marks_unlock($pdo, 10, 1, '   ');
    } catch (InvalidArgumentException $e) {
        $noReasonUnlockFailed = true;
    }
    assert_true($noReasonUnlockFailed, 'Empty reason unlock must fail');

    // Admin unlocking with reason succeeds
    $success = marks_unlock($pdo, 10, 1, 'Committee approved re-submission of report');
    assert_true($success, 'Admin successfully unlocked marks');

    $projMarks = marks_get_project_marks($pdo, 10);
    assert_false($projMarks['is_finalized'], 'Marks is_finalized is now 0');

    // Guide can now save changes again since it is unlocked
    marks_save($pdo, 10, 2, 46.0, [3 => ['presentation_marks' => 24.0, 'qa_marks' => 23.0]], false);
    $freshMarks = marks_get_project_marks($pdo, 10);
    assert_same(46.0, $freshMarks['report_marks'], 'Guide updated report marks to 46 after unlock');
});
