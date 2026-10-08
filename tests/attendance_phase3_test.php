<?php
declare(strict_types=1);

/**
 * Phase 3 Test Suite: Guide-Controlled Attendance & Personal Metrics.
 *
 * Verifies:
 * 1. Fast-entry attendance recording across all team members.
 * 2. Server-side authorization guard (project mentor or classroom Admin).
 * 3. Mandatory edit reason and immutable audit log in attendance_changes.
 * 4. Holiday and rescheduled meeting status (no student absences counted).
 * 5. Personal attendance percentage calculation and shortage thresholds (<75%).
 */

test('phase3 auth: can_manage_project_attendance enforces mentor or admin', function () {
    $pdo = test_pdo();

    // In test_pdo():
    // Classroom 1 (coordinator user 1 is 'Admin')
    // Project 10 has mentor_id = 2.
    // Leader is user 3, member is user 4, pending is user 5.

    // 1. Assigned mentor (user 2) passes
    assert_true(can_manage_project_attendance($pdo, 10, 2), 'Assigned mentor must pass');

    // 2. Classroom coordinator / Admin (user 1) passes
    assert_true(can_manage_project_attendance($pdo, 10, 1), 'Classroom Admin must pass');

    // 3. Project leader (user 3) is REJECTED
    assert_false(can_manage_project_attendance($pdo, 10, 3), 'Project leader cannot mark attendance');

    // 4. Regular team member (user 4) is REJECTED
    assert_false(can_manage_project_attendance($pdo, 10, 4), 'Team member cannot mark attendance');

    // 5. Outsider (user 6 is member of classroom 1, but neither mentor nor Admin) is REJECTED
    assert_false(can_manage_project_attendance($pdo, 10, 6), 'Classmate cannot mark attendance');

    // 6. Unknown user / invalid project
    assert_false(can_manage_project_attendance($pdo, 10, 999), 'Unknown user fails');
    assert_false(can_manage_project_attendance($pdo, 999, 2), 'Unknown project fails');
    assert_false(can_manage_project_attendance($pdo, 0, 2), 'Zero project fails');
    assert_false(can_manage_project_attendance($pdo, 10, 0), 'Zero user fails');
});

test('phase3 attendance: fast-entry initial mark sets statuses and updates meeting to held', function () {
    $pdo = test_pdo();
    $meetings = meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = $meetings[0];
    $meetingId = (int)$m1['id'];

    assert_same('scheduled', $m1['status']);

    // Guide (user 2) marks attendance: user 3 present, user 4 absent
    $result = meeting_save_full_attendance(
        $pdo,
        $meetingId,
        [
            3 => 'present',
            4 => 'absent',
        ],
        2 // actorId (mentor)
    );

    assert_true($result['success']);
    assert_same(2, $result['updated']);
    assert_same(0, $result['changes_logged'], 'Initial mark must not log changes');
    assert_same('held', $result['meeting_status'], 'Meeting status should transition to held');

    // Verify stored attendance
    $att = meeting_attendance_get($pdo, $meetingId);
    assert_same(2, count($att));
    $byUser = array_column($att, 'status', 'user_id');
    assert_same('present', $byUser[3]);
    assert_same('absent', $byUser[4]);

    // Meeting status in DB
    $updatedM = meeting_find($pdo, $meetingId);
    assert_same('held', $updatedM['status']);
});

test('phase3 attendance: post-save edit requires mandatory reason and logs to attendance_changes', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = meeting_find_by_week($pdo, 10, 1);
    $meetingId = (int)$m1['id'];

    // Initial mark
    meeting_save_full_attendance($pdo, $meetingId, [3 => 'present', 4 => 'absent'], 2);

    // Attempt modification without reason (or whitespace) -> MUST throw InvalidArgumentException
    $threwNoReason = false;
    try {
        meeting_save_full_attendance($pdo, $meetingId, [3 => 'present', 4 => 'excused'], 2, '');
    } catch (InvalidArgumentException $e) {
        $threwNoReason = true;
    }
    assert_true($threwNoReason, 'Modifying saved attendance without reason must be rejected');

    $threwWhitespace = false;
    try {
        meeting_save_full_attendance($pdo, $meetingId, [3 => 'present', 4 => 'excused'], 2, '   ');
    } catch (InvalidArgumentException $e) {
        $threwWhitespace = true;
    }
    assert_true($threwWhitespace, 'Whitespace-only reason must be rejected');

    // Modification WITH valid reason -> MUST succeed and log immutably
    $validReason = 'Student provided health center slip for university medical leave';
    $res = meeting_save_full_attendance($pdo, $meetingId, [3 => 'present', 4 => 'excused'], 2, $validReason);

    assert_true($res['success']);
    assert_same(1, $res['changes_logged']);

    // Check attendance_changes audit log
    $changes = meeting_attendance_get_changes($pdo, $meetingId);
    assert_same(1, count($changes));
    assert_same(4, (int)$changes[0]['user_id']);
    assert_same('absent', $changes[0]['old_status']);
    assert_same('excused', $changes[0]['new_status']);
    assert_same(2, (int)$changes[0]['changed_by']);
    assert_same($validReason, $changes[0]['reason']);

    // Check current attendance
    $att = meeting_attendance_get($pdo, $meetingId);
    $byUser = array_column($att, 'status', 'user_id');
    assert_same('excused', $byUser[4]);

    // Re-saving with identical status does NOT require a reason and logs 0 changes
    $resSame = meeting_save_full_attendance($pdo, $meetingId, [3 => 'present', 4 => 'excused'], 2, null);
    assert_same(0, $resSame['changes_logged'], 'No new change logged when statuses are identical');
    assert_same(1, count(meeting_attendance_get_changes($pdo, $meetingId)));
});

test('phase3 attendance: holiday and rescheduled meetings do not count absences', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');

    $m1 = meeting_find_by_week($pdo, 10, 1);
    $m2 = meeting_find_by_week($pdo, 10, 2);
    $m3 = meeting_find_by_week($pdo, 10, 3);

    // Week 1: Held, user 4 was present
    meeting_save_full_attendance($pdo, (int)$m1['id'], [4 => 'present'], 2, null, 'held');

    // Week 2: Holiday (national holiday / college closed)
    meeting_save_full_attendance($pdo, (int)$m2['id'], [4 => 'absent'], 2, null, 'holiday');

    // Week 3: Rescheduled (mentor on university duty)
    meeting_save_full_attendance($pdo, (int)$m3['id'], [4 => 'absent'], 2, null, 'rescheduled');

    $metrics = meeting_calculate_student_attendance($pdo, 10, 4);

    assert_same(1, $metrics['held'], 'Only week 1 is held');
    assert_same(1, $metrics['holiday'], 'Week 2 is holiday');
    assert_same(1, $metrics['rescheduled'], 'Week 3 is rescheduled');
    assert_same(1, $metrics['present']);
    assert_same(0, $metrics['absent'], 'Absences in holiday and rescheduled meetings are not counted');
    assert_same(1, $metrics['evaluated']);
    assert_same(100.0, $metrics['percentage'], 'Holiday/rescheduled must not reduce attendance percentage');
    assert_false($metrics['is_shortage']);
});

test('phase3 attendance: personal attendance percentage and shortage threshold (<75%)', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');

    $m1 = (int)meeting_find_by_week($pdo, 10, 1)['id'];
    $m2 = (int)meeting_find_by_week($pdo, 10, 2)['id'];
    $m3 = (int)meeting_find_by_week($pdo, 10, 3)['id'];
    $m4 = (int)meeting_find_by_week($pdo, 10, 4)['id'];

    // Scenario A: 0 meetings evaluated yet -> defaults safely to 100%, no shortage
    $initial = meeting_calculate_student_attendance($pdo, 10, 4);
    assert_same(100.0, $initial['percentage']);
    assert_false($initial['is_shortage']);

    // Scenario B: 3 Present, 1 Absent out of 4 held -> 75.0% -> NOT shortage (threshold is < 75%)
    meeting_save_full_attendance($pdo, $m1, [4 => 'present'], 2, null, 'held');
    meeting_save_full_attendance($pdo, $m2, [4 => 'present'], 2, null, 'held');
    meeting_save_full_attendance($pdo, $m3, [4 => 'present'], 2, null, 'held');
    meeting_save_full_attendance($pdo, $m4, [4 => 'absent'], 2, null, 'held');

    $at75 = meeting_calculate_student_attendance($pdo, 10, 4);
    assert_same(75.0, $at75['percentage']);
    assert_same(3, $at75['present']);
    assert_same(1, $at75['absent']);
    assert_false($at75['is_shortage'], 'Exactly 75% should not trigger shortage');

    // Scenario C: Another meeting held and absent -> 3 Present, 2 Absent -> 60.0% -> SHORTAGE!
    $m5 = (int)meeting_find_by_week($pdo, 10, 5)['id'];
    meeting_save_full_attendance($pdo, $m5, [4 => 'absent'], 2, null, 'held');

    $at60 = meeting_calculate_student_attendance($pdo, 10, 4);
    assert_same(60.0, $at60['percentage']);
    assert_true($at60['is_shortage'], '< 75% must trigger attendance shortage alert');

    // Scenario D: Excused absence does not count against student
    // Change meeting 5 from absent to excused with medical certificate
    meeting_save_full_attendance($pdo, $m5, [4 => 'excused'], 2, 'Medical reason approved', 'held');

    $afterExcused = meeting_calculate_student_attendance($pdo, 10, 4);
    assert_same(1, $afterExcused['excused']);
    assert_same(1, $afterExcused['absent']);
    assert_same(3, $afterExcused['present']);
    assert_same(4, $afterExcused['evaluated']); // 3 present + 1 absent (excused not in evaluated denominator)
    assert_same(75.0, $afterExcused['percentage']);
    assert_false($afterExcused['is_shortage']);
});
