-- Run once on an existing "pms" database (new installs just use schema.sql)
USE `pms`;

ALTER TABLE projects
    ADD COLUMN status ENUM('forming', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'forming' AFTER mentor_id,
    ADD COLUMN review_note TEXT DEFAULT NULL AFTER status,
    ADD COLUMN reviewed_at TIMESTAMP NULL DEFAULT NULL AFTER review_note;

-- Projects that already exist keep working as before
UPDATE projects SET status = 'approved';

CREATE TABLE IF NOT EXISTS team_requests (
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'declined') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, student_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
