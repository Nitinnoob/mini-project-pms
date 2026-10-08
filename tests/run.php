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
        CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT);
        CREATE TABLE classrooms (id INTEGER PRIMARY KEY, name TEXT DEFAULT 'Classroom', created_by INTEGER, start_date TEXT, end_date TEXT, max_team_size INTEGER DEFAULT 10, min_team_size INTEGER DEFAULT 1);
        CREATE TABLE classroom_members (classroom_id INTEGER, user_id INTEGER, role TEXT, usn TEXT NULL);
        CREATE TABLE projects (id INTEGER PRIMARY KEY, classroom_id INTEGER, name TEXT DEFAULT 'Project', description TEXT NULL, mentor_id INTEGER NULL);
        CREATE TABLE project_members (project_id INTEGER, user_id INTEGER, is_leader INTEGER DEFAULT 0, join_status TEXT);
        CREATE TABLE classroom_phases (classroom_id INTEGER, week_number INTEGER, label TEXT NULL, merged_into_week INTEGER NULL);
        CREATE TABLE weekly_submissions (id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INTEGER, week_number INTEGER, submitted_by INTEGER NULL, work_summary TEXT NULL, next_steps TEXT NULL, submitted_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE weekly_reviews (submission_id INTEGER PRIMARY KEY, reviewed_by INTEGER NULL, status TEXT DEFAULT 'pending', mentor_remarks TEXT NULL, reviewed_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE weekly_submission_files (id INTEGER PRIMARY KEY AUTOINCREMENT, meeting_id INTEGER NULL, submission_id INTEGER NULL, original_name TEXT NULL, stored_name TEXT NULL, file_name TEXT, file_path TEXT, file_size INTEGER NULL, mime_type TEXT NULL, uploaded_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE weekly_attendance (submission_id INTEGER, user_id INTEGER, present INTEGER DEFAULT 0);
        CREATE TABLE weekly_meetings (id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INTEGER, week_number INTEGER, meeting_date TEXT, status TEXT DEFAULT 'scheduled', team_update TEXT NULL, guide_feedback TEXT NULL, submitted_by INTEGER NULL, submitted_at TEXT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE meeting_attendance (meeting_id INTEGER, user_id INTEGER, status TEXT DEFAULT 'present', marked_by INTEGER NULL, marked_at TEXT DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (meeting_id, user_id));
        CREATE TABLE attendance_changes (id INTEGER PRIMARY KEY AUTOINCREMENT, meeting_id INTEGER, user_id INTEGER, old_status TEXT, new_status TEXT, changed_by INTEGER, reason TEXT, changed_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE guide_instructions (id INTEGER PRIMARY KEY AUTOINCREMENT, meeting_id INTEGER, text TEXT, status TEXT DEFAULT 'open', created_by INTEGER, closed_at TEXT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE meeting_feedback_history (id INTEGER PRIMARY KEY AUTOINCREMENT, meeting_id INTEGER, reviewer_id INTEGER, feedback TEXT, status TEXT DEFAULT 'held', created_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE classroom_milestones (id INTEGER PRIMARY KEY AUTOINCREMENT, classroom_id INTEGER, title TEXT, due_date TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE project_marks (project_id INTEGER PRIMARY KEY, report_marks REAL DEFAULT NULL, is_finalized INTEGER DEFAULT 0, finalized_by INTEGER NULL, finalized_at TEXT NULL, updated_by INTEGER NULL, updated_at TEXT DEFAULT CURRENT_TIMESTAMP, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE student_marks (project_id INTEGER, user_id INTEGER, presentation_marks REAL DEFAULT NULL, qa_marks REAL DEFAULT NULL, updated_by INTEGER NULL, updated_at TEXT DEFAULT CURRENT_TIMESTAMP, created_at TEXT DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (project_id, user_id));
        CREATE TABLE marks_changes (id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INTEGER, user_id INTEGER NULL, field_name TEXT, old_value TEXT NULL, new_value TEXT NULL, changed_by INTEGER, reason TEXT, changed_at TEXT DEFAULT CURRENT_TIMESTAMP);

        -- Classroom 1 (coordinator user 1), project 10 (mentor 2), leader 3, member 4, pending 5.
        INSERT INTO users VALUES (1, 'CoordinatorAdmin'), (2, 'RoopaMentor'), (3, 'LeaderStudent'), (4, 'MemberStudent'), (5, 'PendingStudent'), (6, 'Student6');
        INSERT INTO classrooms VALUES (1, 'CSE 5th Sem', 1, '2026-01-05', '2026-03-01', 4, 1), (2, 'CSE 7th Sem', 9, NULL, NULL, 10, 1);
        INSERT INTO classroom_members VALUES (1, 1, 'Admin', '1MS21CS001'), (1, 3, 'Team Member', '1MS21CS003'), (1, 4, 'Team Member', '1MS21CS004'), (1, 6, 'Team Member', '1MS21CS006');
        INSERT INTO projects VALUES (10, 1, 'AI Phishing Detection', 'ML project description', 2), (20, 2, 'Robotics', 'Robotics description', NULL);
        INSERT INTO project_members VALUES (10, 3, 1, 'Active'), (10, 4, 0, 'Active'), (10, 5, 0, 'Pending');
    ");
    return $pdo;
}

$root = dirname(__DIR__);
require_once $root . '/auth_guard.php';
require_once $root . '/phase_engine.php';
require_once $root . '/meeting_engine.php';
require_once $root . '/views/helpers.php';
require_once $root . '/views/view_contract.php';
require_once $root . '/repositories/project_repository.php';
require_once $root . '/repositories/phase_repository.php';
require_once $root . '/repositories/membership_repository.php';
require_once $root . '/repositories/weekly_log_repository.php';
require_once $root . '/repositories/meeting_repository.php';
require_once $root . '/repositories/milestone_repository.php';
require_once $root . '/repositories/marks_repository.php';

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
