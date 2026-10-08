<?php
// View helpers, $viewData contract, and repositories.

test('e(): null-safe and escapes', function () {
    assert_same('', e(null));
    assert_same('5', e(5));
    assert_same('&lt;b&gt; &quot;x&quot; &#039;y&#039;', e('<b> "x" \'y\''));
    assert_same('', e(['array']));
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
    assert_same([], $out['weeklyLogs']);
    assert_same([], $out['teamRoster']);
});

test('project repo: schedule is classroom-scoped', function () {
    $pdo = test_pdo();
    assert_same('2026-01-05', project_find_schedule($pdo, 10, 1)['start_date']);
    assert_same(null, project_find_schedule($pdo, 10, 2), 'wrong classroom');
    assert_same('2026-01-05', classroom_find_schedule($pdo, 1)['start_date']);
    assert_same(null, classroom_find_schedule($pdo, 99));
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
