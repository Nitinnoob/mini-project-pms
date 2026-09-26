<?php
require 'dbs.php';

try {
    $pdo->exec("ALTER TABLE projects ADD COLUMN mentor_id INT DEFAULT NULL AFTER created_by");
    $pdo->exec("ALTER TABLE projects ADD CONSTRAINT fk_projects_mentor FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE SET NULL");
    echo "Added mentor_id to projects.\n";
} catch (Exception $e) { echo "projects alter failed/already exists: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE tasks ADD COLUMN milestone ENUM('Synopsis', 'Phase 1', 'Phase 2', 'Final Demo') DEFAULT 'Synopsis' AFTER description");
    echo "Added milestone to tasks.\n";
} catch (Exception $e) { echo "tasks alter failed/already exists: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS deliverables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        task_id INT DEFAULT NULL,
        uploaded_by INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "Created deliverables table.\n";
} catch (Exception $e) { echo "deliverables table failed: " . $e->getMessage() . "\n"; }

?>
