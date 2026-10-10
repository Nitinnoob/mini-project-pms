<?php
// config/evaluation.php - CIE Continuous Internal Evaluation Weights & Rules

return [
    'marks' => [
        'report_max'       => 50.0, // Project Report & Documentation (Team score)
        'presentation_max' => 25.0, // Presentation & Technical Demo (Individual score)
        'qa_max'           => 25.0, // Viva Voce & Technical Q&A (Individual score)
        'total_max'        => 100.0,
    ],
    'attendance' => [
        'min_percentage'   => 75.0, // University mandatory minimum attendance threshold
        'warning_badge'    => 'Attendance Shortage (< 75%)',
    ],
    'team' => [
        'min_members'      => 1,
        'max_members'      => 4, // 1 Leader + up to 3 batchmates
    ],
    'meeting' => [
        'day_of_week'      => 6, // 6 = Saturday (date('N'))
    ]
];
