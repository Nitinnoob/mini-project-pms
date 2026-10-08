<?php
declare(strict_types=1);

/**
 * Tests for Phase 2: Weekly Meeting Schema and Saturday-aligned derivation engine.
 */

test('meeting_engine: first saturday derivation', function () {
    // 2026-01-05 is Monday -> next Saturday is 2026-01-10
    assert_same('2026-01-10', meeting_find_first_saturday('2026-01-05')->format('Y-m-d'));

    // 2026-01-10 is Saturday -> returns 2026-01-10
    assert_same('2026-01-10', meeting_find_first_saturday('2026-01-10')->format('Y-m-d'));

    // 2026-01-11 is Sunday -> returns 2026-01-17
    assert_same('2026-01-17', meeting_find_first_saturday('2026-01-11')->format('Y-m-d'));

    // 2026-01-09 is Friday -> returns 2026-01-10
    assert_same('2026-01-10', meeting_find_first_saturday('2026-01-09')->format('Y-m-d'));
});

test('meeting_engine: derive saturday-aligned schedule without tasks or phases', function () {
    // 2026-01-05 (Monday) to 2026-03-01 (Sunday) = 8 Saturdays (Jan 10, 17, 24, 31, Feb 7, 14, 21, 28)
    $sched = meeting_derive_schedule('2026-01-05', '2026-03-01', '2026-01-15');
    assert_same(8, count($sched));

    assert_same(1, $sched[0]['week_number']);
    assert_same('2026-01-10', $sched[0]['meeting_date']);
    assert_same('2026-01-05', $sched[0]['date_from']);
    assert_same('2026-01-10', $sched[0]['date_to']);
    assert_same('past', $sched[0]['week_state']);
    assert_false($sched[0]['is_current']);

    assert_same(2, $sched[1]['week_number']);
    assert_same('2026-01-17', $sched[1]['meeting_date']);
    assert_same('2026-01-11', $sched[1]['date_from']);
    assert_same('2026-01-17', $sched[1]['date_to']);
    assert_same('current', $sched[1]['week_state']);
    assert_true($sched[1]['is_current']);

    assert_same(8, $sched[7]['week_number']);
    assert_same('2026-02-28', $sched[7]['meeting_date']);
    assert_same('future', $sched[7]['week_state']);
});

test('meeting_engine: edge cases degrade gracefully', function () {
    assert_same([], meeting_derive_schedule(null, null));
    assert_same([], meeting_derive_schedule('', '2026-03-01'));
    assert_same([], meeting_derive_schedule('2026-03-01', '2026-01-01'), 'end before start');
    assert_same([], meeting_derive_schedule('invalid-date', '2026-03-01'));

    // Range with no Saturdays: Monday 2026-01-05 to Wednesday 2026-01-07
    assert_same([], meeting_derive_schedule('2026-01-05', '2026-01-07'));

    assert_same(0, meeting_total_weeks(null, null));
    assert_same(null, meeting_current_week(null, null));
});

test('meeting_repo: team update encoding and decoding', function () {
    $raw = meeting_encode_team_update([
        'work_done'  => 'Finished API endpoints',
        'next_steps' => 'Write frontend integration',
        'blockers'   => 'None',
    ]);
    assert_true(strpos($raw, 'Finished API endpoints') !== false);

    $parsed = meeting_decode_team_update($raw);
    assert_same('Finished API endpoints', $parsed['work_done']);
    assert_same('Write frontend integration', $parsed['next_steps']);
    assert_same('None', $parsed['blockers']);

    // Plain text fallback
    $legacy = meeting_decode_team_update('Just a simple text summary');
    assert_same('Just a simple text summary', $legacy['work_done']);
    assert_same('', $legacy['next_steps']);
});

test('meeting_repo: ensure meetings created idempotently', function () {
    $pdo = test_pdo();

    $meetings = meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    assert_same(8, count($meetings));
    assert_same(1, (int)$meetings[0]['week_number']);
    assert_same('2026-01-10', $meetings[0]['meeting_date']);
    assert_same('scheduled', $meetings[0]['status']);

    // Second call does not duplicate
    $again = meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    assert_same(8, count($again));
});

test('meeting_repo: update status, submit team update & feedback', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = meeting_find_by_week($pdo, 10, 1);
    assert_true($m1 !== null);
    $meetingId = (int)$m1['id'];

    // Update status
    assert_true(meeting_update_status($pdo, $meetingId, 'held'));
    assert_false(meeting_update_status($pdo, $meetingId, 'invalid_status'));

    // Submit team update
    assert_true(meeting_submit_team_update($pdo, $meetingId, 3, [
        'work_done'  => 'Completed week 1 literature survey',
        'next_steps' => 'Architecture diagrams',
        'blockers'   => 'Need GPU lab access',
    ]));

    // Save guide feedback
    assert_true(meeting_save_guide_feedback($pdo, $meetingId, 'Solid progress. Proceed with diagram.'));

    $updated = meeting_find($pdo, $meetingId);
    assert_same('held', $updated['status']);
    assert_same(3, (int)$updated['submitted_by']);
    assert_same('Solid progress. Proceed with diagram.', $updated['guide_feedback']);
    assert_same('Completed week 1 literature survey', $updated['team_update_parsed']['work_done']);
    assert_same('Architecture diagrams', $updated['team_update_parsed']['next_steps']);
    assert_same('Need GPU lab access', $updated['team_update_parsed']['blockers']);
});

test('meeting_repo: guide attendance and immutable audit log', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = meeting_find_by_week($pdo, 10, 1);
    $meetingId = (int)$m1['id'];

    // Mark attendance: user 3 (leader) present, user 4 (member) absent
    assert_true(meeting_attendance_record($pdo, $meetingId, 3, 'present', 2));
    assert_true(meeting_attendance_record($pdo, $meetingId, 4, 'absent', 2));

    $att = meeting_attendance_get($pdo, $meetingId);
    assert_same(2, count($att));

    // Post-meeting update: change user 4 from 'absent' to 'excused' with reason
    $changeId = meeting_attendance_log_change(
        $pdo,
        $meetingId,
        4,
        'absent',
        'excused',
        2, // changed by mentor user 2
        'Student submitted valid hospital medical certificate'
    );
    assert_true($changeId > 0);

    // Apply the status update
    assert_true(meeting_attendance_record($pdo, $meetingId, 4, 'excused', 2));

    $changes = meeting_attendance_get_changes($pdo, $meetingId);
    assert_same(1, count($changes));
    assert_same('absent', $changes[0]['old_status']);
    assert_same('excused', $changes[0]['new_status']);
    assert_same('Student submitted valid hospital medical certificate', $changes[0]['reason']);
    assert_same(2, (int)$changes[0]['changed_by']);

    // Empty reason must throw InvalidArgumentException
    $threw = false;
    try {
        meeting_attendance_log_change($pdo, $meetingId, 4, 'excused', 'present', 2, '   ');
    } catch (InvalidArgumentException $e) {
        $threw = true;
    }
    assert_true($threw, 'Empty attendance change reason must be rejected');
});

test('meeting_repo: guide instructions open, acknowledged, and done lifecycle', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = meeting_find_by_week($pdo, 10, 1);
    $meetingId = (int)$m1['id'];

    $instId1 = guide_instruction_add($pdo, $meetingId, 'Refine ER diagram to include meeting tables', 2);
    $instId2 = guide_instruction_add($pdo, $meetingId, 'Benchmark response time', 2);
    assert_true($instId1 > 0);
    assert_true($instId2 > 0);

    $list = guide_instructions_get_by_meeting($pdo, $meetingId);
    assert_same(2, count($list));
    assert_same('open', $list[0]['status']);

    // Student/team acknowledges
    assert_true(guide_instruction_update_status($pdo, $instId1, 'acknowledged'));
    $openRollover = guide_instructions_get_open_by_project($pdo, 10);
    assert_same(2, count($openRollover), 'Both open and acknowledged roll over to next meeting');

    // Guide marks done
    assert_true(guide_instruction_update_status($pdo, $instId1, 'done'));
    $openAfterDone = guide_instructions_get_open_by_project($pdo, 10);
    assert_same(1, count($openAfterDone), 'Done instruction no longer rolls over');
    assert_same($instId2, (int)$openAfterDone[0]['id']);
});

test('meeting_repo: files linked to meeting_id with metadata', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = meeting_find_by_week($pdo, 10, 1);
    $meetingId = (int)$m1['id'];

    $fileId = meeting_file_add(
        $pdo,
        $meetingId,
        'Literature_Survey.pdf',
        'file_abc123_uuid.pdf',
        'uploads/meetings/file_abc123_uuid.pdf',
        256000,
        'application/pdf'
    );
    assert_true($fileId > 0);

    $files = meeting_files_get($pdo, $meetingId);
    assert_same(1, count($files));
    assert_same('Literature_Survey.pdf', $files[0]['original_name']);
    assert_same('file_abc123_uuid.pdf', $files[0]['stored_name']);
    assert_same(256000, (int)$files[0]['file_size']);
    assert_same('application/pdf', $files[0]['mime_type']);
});
