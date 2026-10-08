<?php
declare(strict_types=1);

/**
 * Phase 7 Automated Tests: Report Assistant, Swappable Template, and Context Data Mapping.
 */

require_once __DIR__ . '/../repositories/marks_repository.php';
require_once __DIR__ . '/../repositories/meeting_repository.php';
require_once __DIR__ . '/../generate_docx.php';

test('phase7 config: report settings loaded correctly', function () {
    $config = require __DIR__ . '/../config/report.php';
    assert_same('Visvesvaraya Technological University', $config['university']);
    assert_same('Department of Computer Science and Engineering', $config['department']);
    assert_true(file_exists($config['default_template']), 'Default template file must exist');
});

test('phase7 report: pre-filled context structure and data mapping', function () {
    $pdo = test_pdo();

    // Ensure Saturday weekly meetings for project 10
    $meetings = meeting_ensure_project_meetings($pdo, 10, '2026-01-05', '2026-01-25');
    assert_true(count($meetings) >= 2);

    // Submit a weekly update
    meeting_submit_team_update($pdo, (int)$meetings[0]['id'], 3, [
        'work_done'  => 'Completed system requirements and architecture.',
        'next_steps' => 'Build database schema and API endpoints.',
        'blockers'   => 'None',
    ]);

    // Guide review & instruction
    meeting_save_guide_feedback($pdo, (int)$meetings[0]['id'], 'Great architecture layout. Follow standard naming conventions.', 2);
    guide_instruction_add($pdo, (int)$meetings[0]['id'], 'Add database foreign key indexes', 2);

    // Save CIE Marks
    marks_save($pdo, 10, 1, 46.5, [
        3 => ['presentation_marks' => 24.0, 'qa_marks' => 23.5],
        4 => ['presentation_marks' => 22.0, 'qa_marks' => 21.0],
    ], false, 'Initial CIE evaluation draft');

    // Fetch marks sheet
    $evalSheet = marks_get_full_evaluation_sheet($pdo, 10);
    assert_same(46.5, $evalSheet['project_marks']['report_marks']);
    $student3 = current(array_filter($evalSheet['students'], fn($s) => $s['user_id'] === 3));
    assert_same(94.0, $student3['total_marks']);

    // Fetch meetings
    $meetingsRaw = meeting_get_by_project($pdo, 10);
    assert_true(count($meetingsRaw) >= 2);
    assert_same('Completed system requirements and architecture.', $meetingsRaw[0]['team_update_parsed']['work_done']);
    assert_same(1, count($meetingsRaw[0]['instructions']));

    // Attendance calculation
    $attStats = meeting_calculate_student_attendance($pdo, 10, 3);
    assert_true(isset($attStats['percentage']));
});

test('phase7 docx: generator bridges to Python docxtpl and produces valid Word document', function () {
    require_once __DIR__ . '/../generate_docx.php';

    $template = __DIR__ . '/../templates/report_template.docx';
    assert_true(file_exists($template), 'Master report template must exist');

    $groupData = [
        'project_name'        => 'Test Automated System',
        'project_description' => 'Test project description for Phase 7 automated testing.',
        'mentor_name'         => 'Test Guide',
        'coordinator_name'    => 'Test Coordinator',
        'hod_name'            => 'Test HOD',
        'members'             => [
            ['name' => 'Leader Student', 'usn' => '1MS21CS003', 'is_leader' => true],
            ['name' => 'Member Student', 'usn' => '1MS21CS004', 'is_leader' => false],
        ],
        'student_evaluations' => [
            ['name' => 'Leader Student', 'usn' => '1MS21CS003', 'report_marks' => 45.0, 'presentation_marks' => 23.0, 'qa_marks' => 22.5, 'total_marks' => 90.5],
            ['name' => 'Member Student', 'usn' => '1MS21CS004', 'report_marks' => 45.0, 'presentation_marks' => 21.0, 'qa_marks' => 20.0, 'total_marks' => 86.0],
        ],
        'weekly_meetings'     => [
            [
                'week_number'        => 1,
                'meeting_date'       => '2026-01-10',
                'status'             => 'held',
                'work_done'          => 'Synopsis approval and environment setup.',
                'next_steps'         => 'Module 1 coding.',
                'blockers'           => 'None',
                'guide_feedback'     => 'Proceed with unit test suite.',
                'instructions'       => [['text' => 'Submit draft test plan', 'status' => 'open']],
                'attendance_summary' => '2/2 Present (100%)',
            ]
        ],
        'attendance_summary'  => [
            'total_meetings' => 4,
            'held'           => 1,
            'percentage'     => 100.0,
        ]
    ];

    $res = generate_group_report_docx($groupData, $template);
    assert_true($res['success'], 'generate_group_report_docx must succeed');
    assert_true(!empty($res['file']) && file_exists($res['file']), 'Generated docx file must exist on disk');
    assert_true(filesize($res['file']) > 20000, 'Generated docx file size must be > 20KB');

    @unlink($res['file']);
});
