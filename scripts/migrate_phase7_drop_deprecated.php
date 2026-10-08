<?php
declare(strict_types=1);

/**
 * Phase 7 Database Migration: Drop deprecated tables.
 *
 * Safely removes legacy Kanban, task-assignment, issue-tracker,
 * and activity-log audit tables that were deprecated during the
 * lean academic weekly meeting review engine pivot.
 */

require_once __DIR__ . '/../dbs.php';

echo "=== Phase 7 Migration: Dropping Deprecated Tables ===\n";

$tablesToDrop = [
    'deliverables',
    'tasks',
    'issues',
    'activity_log',
];

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

foreach ($tablesToDrop as $table) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "  [DROPPED] Table '$table'\n";
    } catch (PDOException $e) {
        echo "  [ERROR] Dropping '$table': " . $e->getMessage() . "\n";
    }
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

$stmt = $pdo->query("SHOW TABLES");
$remainingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "\nRemaining tables in database 'pms' (" . count($remainingTables) . " total):\n";
foreach ($remainingTables as $t) {
    echo "  - $t\n";
}

echo "\nMigration completed successfully.\n";
