<?php
// View helpers, $viewData contract, and repositories.

test('e(): null-safe and escapes', function () {
    assert_same('', e(null));
    assert_same('5', e(5));
    assert_same('&lt;b&gt; &quot;x&quot; &#039;y&#039;', e('<b> "x" \'y\''));
    assert_same('', e(['array']));
});

test('heat_level buckets 0..4', function () {
    assert_same(0, heat_level(null));
    assert_same(2, heat_level('2'));
    assert_same(4, heat_level(99));
});

test('task_phase_chip escapes labels and flags unscheduled', function () {
    assert_true(str_contains(task_phase_chip(['week_label' => '<x>']), '&lt;x&gt;'));
    assert_true(str_contains(task_phase_chip([]), 'Unscheduled'));
});

test('view contract: every required key is registered', function () {
    foreach (['Student', 'Project Leader', 'Marketplace', 'Teacher'] as $v) {
        foreach (view_contract_keys($v) as $k) {
            assert_true(array_key_exists($k, VIEW_KEYS), "$v -> $k");
        }
    }
});

test('view contract: back-fills defaults, preserves existing values', function () {
    $out = @enforce_view_contract(['actualView' => 'Student', 'myProjectId' => 7]);
    assert_same(7, $out['myProjectId']);
    assert_same('kanban', $out['activeBoardView']);
    assert_same([], $out['tasks']['done']);
});

test('task_legacy_milestone quartiles', function () {
    assert_same('Synopsis', task_legacy_milestone(null, 8));
    assert_same('Synopsis', task_legacy_milestone(1, 8));
    assert_same('Phase 2', task_legacy_milestone(6, 8));
    assert_same('Final Demo', task_legacy_milestone(8, 8));
});

test('task repo: create, find, update status', function () {
    $pdo = test_pdo();
    $id = task_create($pdo, ['project_id' => 10, 'title' => 'T', 'assigned_to' => 4, 'priority' => 'high',
                             'milestone' => 'Synopsis', 'week_number' => 2, 'due_date' => null], true);
    $row = task_find_with_project($pdo, $id);
    assert_same(1, (int) $row['classroom_id']);
    assert_same('todo', $row['status']);
    assert_true(task_update_status($pdo, $id, 'done'));
    assert_same('done', task_find_with_project($pdo, $id)['status']);
    assert_false(task_update_status($pdo, $id, 'bogus'), 'rejects invalid status');
    assert_same(null, task_find_with_project($pdo, 999));
});

test('project repo: schedule is classroom-scoped', function () {
    $pdo = test_pdo();
    assert_same('2026-01-05', project_find_schedule($pdo, 10, 1)['start_date']);
    assert_same(null, project_find_schedule($pdo, 10, 2), 'wrong classroom');
    activity_log_add($pdo, 10, 3, 'Created Task', 'x');
    assert_same(1, (int) $pdo->query('SELECT COUNT(*) FROM activity_log')->fetchColumn());
});

test('phase repo: rename, merge, unmerge', function () {
    $pdo = test_pdo();
    $pdo->exec("INSERT INTO classroom_phases VALUES (1,1,NULL,NULL),(1,2,NULL,NULL)");
    assert_true(phase_row_exists($pdo, 1, 1));
    assert_false(phase_row_exists($pdo, 1, 9));
    phase_rename($pdo, 1, 1, 'Synopsis');
    phase_set_merge($pdo, 1, 2, 1);
    assert_same(1, (int) phase_merge_target_of($pdo, 1, 2));
    assert_same(1, phase_absorbed_count($pdo, 1, 1));
    phase_set_merge($pdo, 1, 2, null);
    assert_same(null, phase_merge_target_of($pdo, 1, 2));
    assert_same(false, phase_merge_target_of($pdo, 1, 9));
});
