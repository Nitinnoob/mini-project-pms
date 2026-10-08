<?php
/**
 * Zero-dependency test runner (Composer/PHPUnit not installed).
 * Usage: php tests/run.php
 * Each tests/*_test.php file calls test('name', fn() => ...) using the assert_* helpers.
 * PHPUnit-compatible naming so the suite can be ported later.
 */
declare(strict_types=1);

$GLOBALS['__tests'] = [];
function test(string $name, callable $fn): void { $GLOBALS['__tests'][] = [$name, $fn]; }

final class AssertionFailed extends Exception {}
function assert_same($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new AssertionFailed(($msg ? "$msg: " : '') . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}
function assert_true($v, string $msg = ''): void  { assert_same(true, (bool) $v, $msg); }
function assert_false($v, string $msg = ''): void { assert_same(false, (bool) $v, $msg); }

/** Fresh in-memory SQLite DB with the subset of the PMS schema used by guards/repositories. */
function test_pdo(): PDO
{
    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("
        CREATE TABLE classrooms (id INTEGER PRIMARY KEY, created_by INTEGER, start_date TEXT, end_date TEXT, max_team_size INTEGER DEFAULT 10, min_team_size INTEGER DEFAULT 1);
        CREATE TABLE classroom_members (classroom_id INTEGER, user_id INTEGER, role TEXT);
        CREATE TABLE projects (id INTEGER PRIMARY KEY, classroom_id INTEGER, mentor_id INTEGER NULL);
        CREATE TABLE project_members (project_id INTEGER, user_id INTEGER, is_leader INTEGER DEFAULT 0, join_status TEXT);
        CREATE TABLE tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INTEGER, title TEXT, assigned_to INTEGER NULL,
                            priority TEXT, milestone TEXT, week_number INTEGER NULL, due_date TEXT NULL, status TEXT);
        CREATE TABLE activity_log (id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INTEGER, user_id INTEGER, action TEXT, details TEXT);
        CREATE TABLE classroom_phases (classroom_id INTEGER, week_number INTEGER, label TEXT NULL, merged_into_week INTEGER NULL);
        CREATE TABLE issues (id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INTEGER, raised_by INTEGER, title TEXT, description TEXT, week_number INTEGER NULL, task_id INTEGER NULL, severity TEXT, status TEXT, resolved_by INTEGER NULL, resolved_at TEXT NULL);
        CREATE TABLE deliverables (id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INTEGER, task_id INTEGER NULL, uploaded_by INTEGER, file_name TEXT, file_path TEXT, uploaded_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE weekly_submissions (id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INTEGER, week_number INTEGER, submitted_by INTEGER NULL, work_summary TEXT NULL, next_steps TEXT NULL, submitted_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE weekly_reviews (submission_id INTEGER PRIMARY KEY, reviewed_by INTEGER NULL, status TEXT DEFAULT 'pending', mentor_remarks TEXT NULL, reviewed_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE weekly_submission_files (id INTEGER PRIMARY KEY AUTOINCREMENT, submission_id INTEGER, file_name TEXT, file_path TEXT);
        CREATE TABLE weekly_attendance (submission_id INTEGER, user_id INTEGER, present INTEGER DEFAULT 0);

        -- Classroom 1 (coordinator user 1), project 10 (mentor 2), leader 3, member 4, pending 5.
        INSERT INTO classrooms VALUES (1, 1, '2026-01-05', '2026-03-01', 4, 1), (2, 9, NULL, NULL, 10, 1);
        INSERT INTO classroom_members VALUES (1, 1, 'Admin'), (1, 3, 'Team Member'), (1, 4, 'Team Member'), (1, 6, 'Team Member');
        INSERT INTO projects VALUES (10, 1, 2), (20, 2, NULL);
        INSERT INTO project_members VALUES (10, 3, 1, 'Active'), (10, 4, 0, 'Active'), (10, 5, 0, 'Pending');
    ");
    return $pdo;
}

$root = dirname(__DIR__);
require_once $root . '/auth_guard.php';
require_once $root . '/phase_engine.php';
require_once $root . '/views/helpers.php';
require_once $root . '/views/view_contract.php';
require_once $root . '/repositories/task_repository.php';
require_once $root . '/repositories/project_repository.php';
require_once $root . '/repositories/phase_repository.php';
require_once $root . '/repositories/issue_repository.php';
require_once $root . '/repositories/deliverable_repository.php';
require_once $root . '/repositories/membership_repository.php';
require_once $root . '/repositories/weekly_log_repository.php';

foreach (glob(__DIR__ . '/*_test.php') as $file) {
    require $file;
}

$pass = $fail = 0;
foreach ($GLOBALS['__tests'] as [$name, $fn]) {
    try {
        $fn();
        $pass++;
        echo "  PASS  $name\n";
    } catch (Throwable $e) {
        $fail++;
        echo "  FAIL  $name\n        " . $e->getMessage() . "\n";
    }
}
echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
