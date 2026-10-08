<?php
declare(strict_types=1);

/**
 * Phase 4 Test Suite: Weekly Updates & Actionable Instructions.
 *
 * Verifies:
 * 1. Team weekly updates (work done, next steps, blockers) submittable by any active member, capturing submitted_by.
 * 2. Guide feedback and actionable directives inserted into guide_instructions.
 * 3. Rollover of open instructions across meetings, student acknowledgement, and guide closure.
 * 4. Append-only guide feedback audit history across review cycles.
 * 5. Secure file attachment metadata linked to meeting_id.
 */

test('phase4: any active member can submit weekly update with work done, next steps, and blockers', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = meeting_find_by_week($pdo, 10, 1);
    $meetingId = (int)$m1['id'];

    // In test_pdo(): Project 10: User 3 is Leader ('Active'), User 4 is Member ('Active'), User 5 is Pending.
    assert_true(is_active_project_member($pdo, 10, 3, 1), 'Leader is active member');
    assert_true(is_active_project_member($pdo, 10, 4, 1), 'Regular student is active member');
    assert_false(is_active_project_member($pdo, 10, 5, 1), 'Pending student is NOT active member');
    assert_false(is_active_project_member($pdo, 10, 6, 1), 'Outsider is NOT active member');

    // User 4 (regular student, NOT leader) submits weekly update
    $submitted = meeting_submit_team_update($pdo, $meetingId, 4, [
        'work_done'  => 'Implemented authentication middleware and session guards',
        'next_steps' => 'Integrate frontend Tailwind components',
        'blockers'   => 'Awaiting database credentials for staging server',
    ]);
    assert_true($submitted, 'Active student member must be able to submit update');

    $updated = meeting_find($pdo, $meetingId);
    assert_same(4, (int)$updated['submitted_by'], 'Must capture submitted_by user ID');
    assert_true(!empty($updated['submitted_at']), 'Must set submitted_at timestamp');

    $parsed = $updated['team_update_parsed'];
    assert_same('Implemented authentication middleware and session guards', $parsed['work_done']);
    assert_same('Integrate frontend Tailwind components', $parsed['next_steps']);
    assert_same('Awaiting database credentials for staging server', $parsed['blockers']);
});

test('phase4: guide inputs feedback and adds actionable instructions', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = meeting_find_by_week($pdo, 10, 1);
    $meetingId = (int)$m1['id'];

    // Project 10 mentor is User 2
    assert_true(is_project_reviewer($pdo, 10, 1, 2), 'Mentor 2 is reviewer');
    assert_false(is_project_reviewer($pdo, 10, 1, 4), 'Student 4 is NOT reviewer');

    // Guide inputs feedback
    $savedFeedback = meeting_save_guide_feedback($pdo, $meetingId, 'Good architecture overview. See action items.', 2, 'approved');
    assert_true($savedFeedback);

    // Guide adds specific action items into guide_instructions
    $instId1 = guide_instruction_add($pdo, $meetingId, 'Write automated unit tests for auth guards', 2);
    $instId2 = guide_instruction_add($pdo, $meetingId, 'Prepare circuit diagram for sensor interface', 2);
    assert_true($instId1 > 0);
    assert_true($instId2 > 0);

    $instructions = guide_instructions_get_by_meeting($pdo, $meetingId);
    assert_same(2, count($instructions));
    assert_same('Write automated unit tests for auth guards', $instructions[0]['text']);
    assert_same('open', $instructions[0]['status']);
    assert_same(2, (int)$instructions[0]['created_by']);
    assert_same('RoopaMentor', $instructions[0]['created_by_name']);

    // Check meeting state
    $m = meeting_find($pdo, $meetingId);
    assert_same('Good architecture overview. See action items.', $m['guide_feedback']);
});

test('phase4: rollover displays prior open instructions; student acknowledges; guide marks done', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = meeting_find_by_week($pdo, 10, 1);
    $m2 = meeting_find_by_week($pdo, 10, 2);

    // Mentor adds 2 instructions during Week 1 meeting
    $inst1 = guide_instruction_add($pdo, (int)$m1['id'], 'Conduct dataset bias evaluation', 2);
    $inst2 = guide_instruction_add($pdo, (int)$m1['id'], 'Refactor database queries using prepared statements', 2);

    // At start of Week 2: Both instructions roll over
    $rollover = guide_instructions_get_open_by_project($pdo, 10);
    assert_same(2, count($rollover), 'Open instructions must roll over to subsequent weeks');

    // Student (User 4) acknowledges instruction 1
    assert_true(is_active_project_member($pdo, 10, 4, 1));
    guide_instruction_update_status($pdo, $inst1, 'acknowledged');

    $foundInst1 = guide_instruction_find($pdo, $inst1);
    assert_same('acknowledged', $foundInst1['status']);
    assert_same(10, (int)$foundInst1['project_id']);

    // Both open and acknowledged instructions continue to roll over
    $rolloverAfterAck = guide_instructions_get_open_by_project($pdo, 10);
    assert_same(2, count($rolloverAfterAck), 'Acknowledged item must still roll over until done');

    // During Week 2 meeting: Guide verifies and marks instruction 1 as 'done'
    assert_true(is_project_reviewer($pdo, 10, 1, 2));
    guide_instruction_update_status($pdo, $inst1, 'done');

    $foundDone = guide_instruction_find($pdo, $inst1);
    assert_same('done', $foundDone['status']);
    assert_true(!empty($foundDone['closed_at']), 'closed_at timestamp must be set on done');

    // Rollover list now only contains the remaining open instruction
    $rolloverAfterDone = guide_instructions_get_open_by_project($pdo, 10);
    assert_same(1, count($rolloverAfterDone));
    assert_same($inst2, (int)$rolloverAfterDone[0]['id']);

    // Meeting 1 still retains both instructions in its historical record
    $m1History = guide_instructions_get_by_meeting($pdo, (int)$m1['id']);
    assert_same(2, count($m1History));
    assert_same('done', $m1History[0]['status']);
    assert_same('open', $m1History[1]['status']);
});

test('phase4: append-only feedback history preserves past review comments across meetings', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = meeting_find_by_week($pdo, 10, 1);
    $meetingId = (int)$m1['id'];

    // Review 1: Mentor flags revision needed
    meeting_save_guide_feedback($pdo, $meetingId, 'Methodology section lacks comparative analysis.', 2, 'revision_needed');

    // Review 2: Students resubmitted; Mentor approves with new remarks
    meeting_save_guide_feedback($pdo, $meetingId, 'Comparative analysis added. Approved.', 2, 'approved');

    // Check append-only history
    $history = meeting_get_feedback_history($pdo, $meetingId);
    assert_same(2, count($history), 'Both feedback entries must be immutably preserved');

    assert_same('Methodology section lacks comparative analysis.', $history[0]['feedback']);
    assert_same('revision_needed', $history[0]['status']);
    assert_same(2, (int)$history[0]['reviewer_id']);
    assert_same('RoopaMentor', $history[0]['reviewer_name']);

    assert_same('Comparative analysis added. Approved.', $history[1]['feedback']);
    assert_same('approved', $history[1]['status']);

    // Latest feedback is reflected on the meeting row
    $currentM = meeting_find($pdo, $meetingId);
    assert_same('Comparative analysis added. Approved.', $currentM['guide_feedback']);
});

test('phase4: secure file uploads validation and metadata tracking', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = meeting_find_by_week($pdo, 10, 1);
    $meetingId = (int)$m1['id'];

    // Record valid file upload
    $fileId = meeting_file_add(
        $pdo,
        $meetingId,
        'Project_Report_Draft.docx',
        'meet_1_a1b2c3d4e5f6.docx',
        'uploads/meetings/meet_1_a1b2c3d4e5f6.docx',
        1048576, // 1 MB
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    );
    assert_true($fileId > 0);

    $files = meeting_files_get($pdo, $meetingId);
    assert_same(1, count($files));
    assert_same('Project_Report_Draft.docx', $files[0]['original_name']);
    assert_same('meet_1_a1b2c3d4e5f6.docx', $files[0]['stored_name']);
    assert_same(1048576, (int)$files[0]['file_size']);
    assert_same('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $files[0]['mime_type']);

    // Batch fetching in meeting_get_by_project populates files, instructions, and feedback_history
    $allMeetings = meeting_get_by_project($pdo, 10);
    assert_same(1, count($allMeetings[0]['files']), 'Batch fetched files attached to meeting');
    assert_same('Project_Report_Draft.docx', $allMeetings[0]['files'][0]['original_name']);
});

test('phase4: instruction and feedback validation safeguards', function () {
    $pdo = test_pdo();
    meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-03-01');
    $m1 = meeting_find_by_week($pdo, 10, 1);
    $meetingId = (int)$m1['id'];

    // Empty instruction text must throw InvalidArgumentException
    $threwEmptyInst = false;
    try {
        guide_instruction_add($pdo, $meetingId, '   ', 2);
    } catch (InvalidArgumentException $e) {
        $threwEmptyInst = true;
    }
    assert_true($threwEmptyInst, 'Empty instruction text must be rejected');

    // Empty feedback logging must throw InvalidArgumentException
    $threwEmptyFb = false;
    try {
        meeting_feedback_log_history($pdo, $meetingId, 2, '   ');
    } catch (InvalidArgumentException $e) {
        $threwEmptyFb = true;
    }
    assert_true($threwEmptyFb, 'Empty feedback text must be rejected');

    // Reopen instruction sets status back to open and clears closed_at
    $instId = guide_instruction_add($pdo, $meetingId, 'Interface prototype testing', 2);
    guide_instruction_update_status($pdo, $instId, 'done');
    $doneInst = guide_instruction_find($pdo, $instId);
    assert_same('done', $doneInst['status']);
    assert_true(!empty($doneInst['closed_at']));

    guide_instruction_update_status($pdo, $instId, 'open');
    $reopenedInst = guide_instruction_find($pdo, $instId);
    assert_same('open', $reopenedInst['status']);
    assert_same(null, $reopenedInst['closed_at']);
});
