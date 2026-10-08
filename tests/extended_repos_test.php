<?php
// Tests for issue, deliverable, membership, and weekly log repositories.

test('issue repo: create, find, resolve', function () {
    $pdo = test_pdo();
    $id = issue_create($pdo, [
        'project_id'  => 10,
        'raised_by'   => 4,
        'title'       => 'Blocked by API',
        'description' => 'API server down',
        'severity'    => 'high'
    ]);
    assert_true($id > 0);

    $found = issue_find_with_classroom($pdo, $id, 1);
    assert_same('Blocked by API', $found['title']);
    assert_same('open', $found['status']);

    assert_same(null, issue_find_with_classroom($pdo, $id, 2), 'wrong classroom isolates issue');

    assert_true(issue_resolve($pdo, $id, 3));
    $updated = issue_find_with_classroom($pdo, $id, 1);
    assert_same('resolved', $updated['status']);
});

test('deliverable repo: create file record', function () {
    $pdo = test_pdo();
    $id = deliverable_create($pdo, 10, null, 4, 'report.pdf', 'uploads/report.pdf');
    assert_true($id > 0);

    $count = (int)$pdo->query("SELECT COUNT(*) FROM deliverables WHERE project_id = 10")->fetchColumn();
    assert_same(1, $count);
});

test('membership repo: status, capacity, and join requests', function () {
    $pdo = test_pdo();
    assert_same('Active', project_member_get_status($pdo, 10, 4));
    assert_same('Pending', project_member_get_status($pdo, 10, 5));
    assert_same(null, project_member_get_status($pdo, 10, 99));

    assert_true(project_member_has_active_or_pending_in_classroom($pdo, 1, 4));
    assert_false(project_member_has_active_or_pending_in_classroom($pdo, 1, 6));

    assert_same(2, project_member_active_count($pdo, 10)); // user 3 and 4
    assert_same(4, classroom_get_max_team_size($pdo, 1));
    assert_same(4, project_get_classroom_max_team_size($pdo, 10));

    assert_true(project_member_add_request($pdo, 10, 6, 'Pending'));
    assert_same('Pending', project_member_get_status($pdo, 10, 6));

    assert_true(project_member_update_status($pdo, 10, 6, 'Pending', 'Active'));
    assert_same('Active', project_member_get_status($pdo, 10, 6));

    assert_true(project_member_delete_by_status($pdo, 10, 6, 'Active'));
    assert_same(null, project_member_get_status($pdo, 10, 6));

    assert_true(classroom_member_is_eligible_student($pdo, 1, 3));
    assert_false(classroom_member_is_eligible_student($pdo, 1, 1), 'Admin is not eligible student');
});

test('weekly log repo: save, file, ensure submission, reviews & attendance', function () {
    $pdo = test_pdo();
    assert_same(1, classroom_get_min_team_size($pdo, 10));

    assert_same(null, weekly_submission_get_status($pdo, 10, 1));
    $subId = weekly_submission_save($pdo, 10, 1, 3, 'Summary week 1', 'Next week goals');
    assert_true($subId > 0);

    $statusRow = weekly_submission_get_status($pdo, 10, 1);
    assert_same('pending', $statusRow['status']);

    weekly_submission_add_file($pdo, $subId, 'log1.pdf', 'uploads/weekly/log1.pdf');
    $filesCount = (int)$pdo->query("SELECT COUNT(*) FROM weekly_submission_files WHERE submission_id = $subId")->fetchColumn();
    assert_same(1, $filesCount);

    $ensuredId = weekly_submission_ensure_exists($pdo, 10, 2);
    assert_true($ensuredId > 0);
    $sameId = weekly_submission_ensure_exists($pdo, 10, 2);
    assert_same($ensuredId, $sameId);

    weekly_attendance_sync($pdo, $subId, 10, [4 => 1]); // user 4 is present, user 3 absent
    $att = $pdo->query("SELECT user_id, present FROM weekly_attendance WHERE submission_id = $subId ORDER BY user_id")->fetchAll(PDO::FETCH_ASSOC);
    assert_same(2, count($att));
    assert_same(0, (int)$att[0]['present']); // user 3
    assert_same(1, (int)$att[1]['present']); // user 4
});
