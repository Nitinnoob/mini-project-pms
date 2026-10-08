<?php
// Phase engine math (pure functions - no DB).

test('total_weeks: missing dates = 0', fn() => assert_same(0, phase_total_weeks(null, '2026-01-01')));
test('total_weeks: end before start = 0', fn() => assert_same(0, phase_total_weeks('2026-02-01', '2026-01-01')));
test('total_weeks: same day = 1', fn() => assert_same(1, phase_total_weeks('2026-01-05', '2026-01-05')));
test('total_weeks: exactly 7 days = 1', fn() => assert_same(1, phase_total_weeks('2026-01-05', '2026-01-11')));
test('total_weeks: 10-day span = 2 (partial week counts)', fn() => assert_same(2, phase_total_weeks('2026-01-05', '2026-01-14')));

test('current_week: before start clamps to 1', fn() => assert_same(1, phase_current_week('2026-01-05', 8, '2025-12-01')));
test('current_week: day 8 is week 2', fn() => assert_same(2, phase_current_week('2026-01-05', 8, '2026-01-12')));
test('current_week: past end clamps to last', fn() => assert_same(8, phase_current_week('2026-01-05', 8, '2027-01-01')));
test('current_week: no schedule = null', fn() => assert_same(null, phase_current_week(null, 0)));

test('for_due_date: maps due date into its week', fn() => assert_same(3, phase_for_due_date('2026-01-05', '2026-03-01', '2026-01-20')));
test('for_due_date: no schedule = null', fn() => assert_same(null, phase_for_due_date(null, null, '2026-01-20')));

test('build_list: default labels, bounds and final-week clamp', function () {
    $list = phase_build_list('2026-01-05', '2026-01-14', [], '2026-01-06');
    assert_same(2, count($list));
    assert_same('Week 1', $list[0]['label']);
    assert_same('current', $list[0]['week_state']);
    assert_same('future', $list[1]['week_state']);
    assert_same('2026-01-12', $list[1]['date_from']);
    assert_same('2026-01-14', $list[1]['date_to'], 'last week clamped to end_date');
});

test('build_list: overrides rename and merge', function () {
    $list = phase_build_list('2026-01-05', '2026-01-25', [
        ['week_number' => 1, 'label' => 'Synopsis', 'merged_into_week' => null],
        ['week_number' => 3, 'label' => null, 'merged_into_week' => 2],
    ], '2026-01-05');
    assert_same('Synopsis', $list[0]['label']);
    assert_same([3], $list[1]['merged_from']);
    assert_true($list[2]['is_merged']);
});
