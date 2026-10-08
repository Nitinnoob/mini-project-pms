<?php
declare(strict_types=1);

/**
 * VTU Project Report Assistant Configuration.
 *
 * Defines academic department details, default course metadata,
 * and the swappable Word document template path.
 */

return [
    // Path to the swappable master .docx template
    'default_template' => __DIR__ . '/../templates/report_template.docx',

    // Academic & Department Defaults
    'university'       => 'Visvesvaraya Technological University',
    'university_addr'  => 'Jnana Sangama, Belagavi - 590 018',
    'college_name'     => 'Sahyadri College of Engineering & Management',
    'department'       => 'Department of Computer Science and Engineering',
    'degree'           => 'Bachelor of Engineering',
    'branch'           => 'Computer Science and Engineering',
    'course_name'      => 'Mini-Project',
    'course_code'      => '21CSMP58',
    'academic_year'    => '2026-2027',
    'section'          => '5th Semester A and B Section',
    'hod_name'         => 'Dr. Rathishchandra R Gatti',
];
