<?php
/**
 * Evaluation & Assessment Configuration
 *
 * Config-driven assessment weights and rules for Continuous Internal Evaluation (CIE) and viva marks.
 * Conforms to Invariant 5: Assessment weights (50 Report / 25 Presentation / 25 Q&A) reside in configuration, never hardcoded.
 */

return [
    // Maximum component marks
    'max_report_marks'       => 50.0,
    'max_presentation_marks' => 25.0,
    'max_qa_marks'           => 25.0,
    'total_max_marks'        => 100.0,

    // Component weight labels
    'components' => [
        'report' => [
            'key'         => 'report_marks',
            'label'       => 'Project Report',
            'max'         => 50.0,
            'scope'       => 'project',
            'description' => 'Shared project documentation, methodology, literature survey & results',
        ],
        'presentation' => [
            'key'         => 'presentation_marks',
            'label'       => 'Presentation & Demo',
            'max'         => 25.0,
            'scope'       => 'student',
            'description' => 'Individual demonstration, slide delivery, and project walkthrough',
        ],
        'qa' => [
            'key'         => 'qa_marks',
            'label'       => 'Viva / Q&A',
            'max'         => 25.0,
            'scope'       => 'student',
            'description' => 'Individual viva voce, conceptual clarity, and technical questions',
        ],
    ],

    // Minimum attendance threshold (%) required for evaluation warning flags
    'attendance_shortage_threshold' => 75.0,
];
