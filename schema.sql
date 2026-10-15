CREATE DATABASE IF NOT EXISTS pms;
USE pms;

-- PMS MySQL Schema: Lean Academic Project Lab Manager
-- Character set: utf8mb4 (Full Unicode Support)

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Classrooms Table
CREATE TABLE IF NOT EXISTS classrooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,
    invite_code VARCHAR(10) COLLATE utf8mb4_bin UNIQUE,
    requires_usn TINYINT(1) DEFAULT 0,
    min_team_size INT DEFAULT 1,
    max_team_size INT DEFAULT 4,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Classroom Members (Contextual Roles & USNs)
CREATE TABLE IF NOT EXISTS classroom_members (
    classroom_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) NOT NULL,           -- 'Admin' or 'Team Member'
    usn VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (classroom_id, user_id),
    INDEX idx_user_id (user_id),
    CONSTRAINT uq_classroom_usn UNIQUE (classroom_id, usn),
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Projects (Teams within a classroom)
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    mentor_id INT DEFAULT NULL,
    github_url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Project Members
CREATE TABLE IF NOT EXISTS project_members (
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    is_leader TINYINT(1) DEFAULT 0,
    join_status VARCHAR(50) DEFAULT 'Active',  -- 'Pending', 'Active'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, user_id),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Project Meetings (Guide adds a meeting on any date)
CREATE TABLE IF NOT EXISTS project_meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    meeting_date DATE NOT NULL,
    guidance_notes TEXT DEFAULT NULL,     -- "Guidance / Changes Requested" plain text
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_meeting_project (project_id),
    INDEX idx_meeting_date (meeting_date),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Meeting Attendance (Present / Absent per member)
CREATE TABLE IF NOT EXISTS meeting_attendance (
    meeting_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('present', 'absent') NOT NULL DEFAULT 'present',
    PRIMARY KEY (meeting_id, user_id),
    INDEX idx_att_user (user_id),
    FOREIGN KEY (meeting_id) REFERENCES project_meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Project Marks (Shared Report marks /50)
CREATE TABLE IF NOT EXISTS project_marks (
    project_id INT PRIMARY KEY,
    report_marks DECIMAL(5,2) DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Student Marks (Individual Presentation /25 + Viva Q&A /25)
CREATE TABLE IF NOT EXISTS student_marks (
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    presentation_marks DECIMAL(5,2) DEFAULT NULL,
    qa_marks DECIMAL(5,2) DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, user_id),
    INDEX idx_sm_user (user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
