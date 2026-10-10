-- PMS Database Schema
-- Academic Project Review & Continuous Evaluation System

CREATE DATABASE IF NOT EXISTS `pms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pms`;

-- 1. Users Table (Clear Role-Based Access)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher') NOT NULL,
    identifier VARCHAR(50) NOT NULL, -- USN for student, Staff ID for teacher
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Classrooms Table (Created by Teachers, Multi-College Isolated)
CREATE TABLE IF NOT EXISTS classrooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,            -- e.g. "6th Sem Mini Project (Sec A)"
    institution VARCHAR(255) NOT NULL,     -- e.g. "RV College of Engineering"
    department VARCHAR(150) NOT NULL,      -- e.g. "Computer Science & Engineering"
    teacher_id INT NOT NULL,               -- Classroom Coordinator (Teacher)
    invite_code VARCHAR(10) UNIQUE NOT NULL, -- Short unique code, e.g. "RV-CS6B" or "K9X2P7"
    start_date DATE NOT NULL,              -- Drives Saturday Review Derivation
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Classroom Enrollments (Students enrolled in a classroom)
CREATE TABLE IF NOT EXISTS classroom_students (
    classroom_id INT NOT NULL,
    student_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (classroom_id, student_id),
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Projects Table (Scoped to Classroom)
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,           -- Student team leader
    mentor_id INT NOT NULL,            -- Assigned Teacher Guide
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Project Members (1-4 students per project)
CREATE TABLE IF NOT EXISTS project_members (
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    is_leader TINYINT(1) DEFAULT 0,
    PRIMARY KEY (project_id, student_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Content Management: Project Media & Showcase Assets
CREATE TABLE IF NOT EXISTS project_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    asset_type ENUM('screenshot', 'photo', 'codebase', 'demo_link') NOT NULL,
    title VARCHAR(255) NOT NULL,
    caption TEXT DEFAULT NULL,
    file_path VARCHAR(255) DEFAULT NULL,      -- Local path for images
    external_url VARCHAR(500) DEFAULT NULL,   -- URL for GitHub / Demo links
    is_spotlight TINYINT(1) DEFAULT 0,        -- Highlighted for exhibition
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Saturday Guide Review Engine
CREATE TABLE IF NOT EXISTS weekly_meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    week_number INT NOT NULL,
    meeting_date DATE NOT NULL,
    status ENUM('scheduled', 'held', 'rescheduled', 'holiday') NOT NULL DEFAULT 'scheduled',
    team_update TEXT DEFAULT NULL,            -- JSON: work_done, next_steps, blockers
    guide_feedback TEXT DEFAULT NULL,
    submitted_by INT DEFAULT NULL,
    submitted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_meeting (project_id, week_number),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Guide-Controlled Attendance
CREATE TABLE IF NOT EXISTS meeting_attendance (
    meeting_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('present', 'absent', 'excused') NOT NULL DEFAULT 'present',
    marked_by INT NOT NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (meeting_id, student_id),
    FOREIGN KEY (meeting_id) REFERENCES weekly_meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Attendance Audit Trail
CREATE TABLE IF NOT EXISTS attendance_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    student_id INT NOT NULL,
    old_status ENUM('present', 'absent', 'excused') NOT NULL,
    new_status ENUM('present', 'absent', 'excused') NOT NULL,
    changed_by INT NOT NULL,
    reason TEXT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES weekly_meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Actionable Guide Directives (With Rollover)
CREATE TABLE IF NOT EXISTS guide_instructions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    text TEXT NOT NULL,
    status ENUM('open', 'acknowledged', 'done') NOT NULL DEFAULT 'open',
    created_by INT NOT NULL,
    closed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id) REFERENCES weekly_meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Continuous Internal Evaluation (CIE) Marks Engine
CREATE TABLE IF NOT EXISTS project_marks (
    project_id INT PRIMARY KEY,
    report_marks DECIMAL(5,2) DEFAULT NULL,   -- Out of 50
    is_finalized TINYINT(1) NOT NULL DEFAULT 0,
    finalized_by INT DEFAULT NULL,
    finalized_at TIMESTAMP NULL DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (finalized_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_marks (
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    presentation_marks DECIMAL(5,2) DEFAULT NULL, -- Out of 25
    qa_marks DECIMAL(5,2) DEFAULT NULL,           -- Out of 25
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, student_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS marks_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    student_id INT DEFAULT NULL,
    field_name VARCHAR(50) NOT NULL,
    old_value VARCHAR(50) DEFAULT NULL,
    new_value VARCHAR(50) DEFAULT NULL,
    changed_by INT NOT NULL,
    reason TEXT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
