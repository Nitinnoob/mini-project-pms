CREATE DATABASE IF NOT EXISTS pms;
USE pms;

-- PMS MySQL Schema: Lean Academic Weekly Meeting & Evaluation Engine
-- Character set: utf8mb4 (Full Unicode Support)

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT NULL, -- Global role is kept NULL (contextual role in classroom_members)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Classrooms Table
CREATE TABLE IF NOT EXISTS classrooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,
    invite_code VARCHAR(10) COLLATE utf8mb4_bin UNIQUE, -- Case-sensitive binary collation for strict validation
    requires_usn TINYINT(1) DEFAULT 0,
    min_team_size INT DEFAULT 1,
    max_team_size INT DEFAULT 4,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Classroom Members Table (Contextual Roles & USNs)
CREATE TABLE IF NOT EXISTS classroom_members (
    classroom_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) NOT NULL,
    usn VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (classroom_id, user_id),
    INDEX idx_user_id (user_id),
    CONSTRAINT uq_classroom_usn UNIQUE (classroom_id, usn), -- Safely allows multiple NULLs in MySQL
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Projects (Academic mini-projects within a classroom)
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    mentor_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Project Members Table
CREATE TABLE IF NOT EXISTS project_members (
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    is_leader TINYINT(1) DEFAULT 0,
    join_status VARCHAR(50) DEFAULT 'Active', -- 'Pending', 'Active'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, user_id),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Classroom Phases (Schedule-derived academic weeks)
CREATE TABLE IF NOT EXISTS classroom_phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT NOT NULL,
    week_number INT NOT NULL,
    label VARCHAR(120) NOT NULL,
    merged_into_week INT DEFAULT NULL, -- non-NULL => this week folds into that week
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_classroom_week (classroom_id, week_number),
    INDEX idx_phase_classroom (classroom_id),
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Classroom Milestones (Coordinator-defined timeline targets with live countdowns)
CREATE TABLE IF NOT EXISTS classroom_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    due_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cm_classroom (classroom_id),
    INDEX idx_cm_due (due_date),
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Weekly Meetings (Saturday Guide Review Engine: Core Operational Record)
CREATE TABLE IF NOT EXISTS weekly_meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    week_number INT NOT NULL,
    meeting_date DATE NOT NULL,
    status ENUM('scheduled', 'held', 'rescheduled', 'holiday') NOT NULL DEFAULT 'scheduled',
    team_update TEXT DEFAULT NULL,
    guide_feedback TEXT DEFAULT NULL,
    submitted_by INT DEFAULT NULL,
    submitted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_meeting_project_week (project_id, week_number),
    INDEX idx_meeting_project (project_id),
    INDEX idx_meeting_date (meeting_date),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Meeting Attendance (Guide-Controlled Fast-Entry)
CREATE TABLE IF NOT EXISTS meeting_attendance (
    meeting_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('present', 'absent', 'excused') NOT NULL DEFAULT 'present',
    marked_by INT DEFAULT NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (meeting_id, user_id),
    INDEX idx_att_user (user_id),
    INDEX idx_att_marked_by (marked_by),
    FOREIGN KEY (meeting_id) REFERENCES weekly_meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Attendance Changes (Immutable Post-Meeting Audit Trail)
CREATE TABLE IF NOT EXISTS attendance_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    user_id INT NOT NULL,
    old_status ENUM('present', 'absent', 'excused') NOT NULL,
    new_status ENUM('present', 'absent', 'excused') NOT NULL,
    changed_by INT NOT NULL,
    reason TEXT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_att_change_meeting (meeting_id),
    INDEX idx_att_change_user (user_id),
    INDEX idx_att_change_changed_by (changed_by),
    FOREIGN KEY (meeting_id) REFERENCES weekly_meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Guide Instructions (Actionable Mentor Directives with Lifecycle & Rollover)
CREATE TABLE IF NOT EXISTS guide_instructions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    text TEXT NOT NULL,
    status ENUM('open', 'acknowledged', 'done') NOT NULL DEFAULT 'open',
    created_by INT NOT NULL,
    closed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inst_meeting (meeting_id),
    INDEX idx_inst_status (status),
    INDEX idx_inst_created_by (created_by),
    FOREIGN KEY (meeting_id) REFERENCES weekly_meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Meeting Feedback History (Append-Only Guide Feedback Audit Trail)
CREATE TABLE IF NOT EXISTS meeting_feedback_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    feedback TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'held',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feedback_meeting (meeting_id),
    INDEX idx_feedback_reviewer (reviewer_id),
    FOREIGN KEY (meeting_id) REFERENCES weekly_meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Weekly Submission Files (Secure File Attachments with MIME Metadata)
CREATE TABLE IF NOT EXISTS weekly_submission_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT DEFAULT NULL,
    submission_id INT DEFAULT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    stored_name VARCHAR(255) DEFAULT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED DEFAULT NULL,
    mime_type VARCHAR(120) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_files_meeting (meeting_id),
    FOREIGN KEY (meeting_id) REFERENCES weekly_meetings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Project Marks (Continuous Internal Evaluation: Shared Project Report Marks /50)
CREATE TABLE IF NOT EXISTS project_marks (
    project_id INT PRIMARY KEY,
    report_marks DECIMAL(5,2) DEFAULT NULL,
    is_finalized TINYINT(1) NOT NULL DEFAULT 0,
    finalized_by INT DEFAULT NULL,
    finalized_at TIMESTAMP NULL DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (finalized_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Student Marks (Continuous Internal Evaluation: Individual Pres. /25 + Viva Q&A /25)
CREATE TABLE IF NOT EXISTS student_marks (
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    presentation_marks DECIMAL(5,2) DEFAULT NULL,
    qa_marks DECIMAL(5,2) DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, user_id),
    INDEX idx_sm_user (user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Marks Changes (Immutable Post-Finalization Evaluation Audit Trail)
CREATE TABLE IF NOT EXISTS marks_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    field_name VARCHAR(50) NOT NULL,
    old_value VARCHAR(50) DEFAULT NULL,
    new_value VARCHAR(50) DEFAULT NULL,
    changed_by INT NOT NULL,
    reason TEXT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mc_project (project_id),
    INDEX idx_mc_user (user_id),
    INDEX idx_mc_changed_by (changed_by),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Notifications (Real-time header bell dropdown)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message VARCHAR(500) NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user (user_id, is_read, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Legacy Weekly Log Compatibility Tables (Maintained for backward compatibility)
CREATE TABLE IF NOT EXISTS weekly_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    week_number INT NOT NULL,
    submitted_by INT DEFAULT NULL,
    work_summary TEXT,
    next_steps TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_project_week (project_id, week_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS weekly_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL UNIQUE,
    reviewed_by INT DEFAULT NULL,
    status ENUM('pending', 'approved', 'revision_needed') DEFAULT 'pending',
    mentor_remarks TEXT,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES weekly_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS weekly_attendance (
    submission_id INT NOT NULL,
    user_id INT NOT NULL,
    present TINYINT(1) DEFAULT 0,
    PRIMARY KEY (submission_id, user_id),
    FOREIGN KEY (submission_id) REFERENCES weekly_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
