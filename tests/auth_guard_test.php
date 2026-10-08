<?php
// RBAC guards: every check must be scoped to a project/classroom.

test('member: active member passes', fn() => assert_true(is_active_project_member(test_pdo(), 10, 4)));
test('member: pending member is rejected', fn() => assert_false(is_active_project_member(test_pdo(), 10, 5)));
test('member: outsider is rejected', fn() => assert_false(is_active_project_member(test_pdo(), 10, 99)));
test('member: classroom scope mismatch is rejected', fn() => assert_false(is_active_project_member(test_pdo(), 10, 4, 2)));
test('member: classroom scope match passes', fn() => assert_true(is_active_project_member(test_pdo(), 10, 4, 1)));
test('member: null ids are rejected', fn() => assert_false(is_active_project_member(test_pdo(), null, null)));

test('leader: leader passes', fn() => assert_true(is_project_leader(test_pdo(), 10, 3)));
test('leader: plain member is not leader', fn() => assert_false(is_project_leader(test_pdo(), 10, 4)));
test('leader: wrong classroom is rejected', fn() => assert_false(is_project_leader(test_pdo(), 10, 3, 2)));

test('coordinator: creator passes', fn() => assert_true(is_classroom_coordinator(test_pdo(), 1, 1)));
test('coordinator: student fails', fn() => assert_false(is_classroom_coordinator(test_pdo(), 1, 3)));
test('coordinator: missing classroom fails', fn() => assert_false(is_classroom_coordinator(test_pdo(), 404, 1)));

test('mentor: assigned mentor passes', fn() => assert_true(is_project_mentor(test_pdo(), 10, 2)));
test('mentor: project without mentor fails', fn() => assert_false(is_project_mentor(test_pdo(), 20, 2)));

test('reviewer: mentor and coordinator pass', function () {
    $pdo = test_pdo();
    assert_true(is_project_reviewer($pdo, 10, 1, 2), 'mentor');
    assert_true(is_project_reviewer($pdo, 10, 1, 1), 'coordinator');
});
test('reviewer: leader is not a reviewer', fn() => assert_false(is_project_reviewer(test_pdo(), 10, 1, 3)));
test('reviewer: cross-classroom is rejected', fn() => assert_false(is_project_reviewer(test_pdo(), 10, 2, 1)));

test('classroom_role: returns contextual role or null', function () {
    $pdo = test_pdo();
    assert_same('Admin', classroom_role($pdo, 1, 1));
    assert_same('Team Member', classroom_role($pdo, 1, 3));
    assert_same(null, classroom_role($pdo, 2, 3));
});
