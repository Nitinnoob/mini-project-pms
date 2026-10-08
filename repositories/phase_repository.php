<?php
/**
 * Phase repository - `classroom_phases` overrides (rename / merge).
 */

if (!function_exists('phase_row_exists')) {
    function phase_row_exists(PDO $pdo, $classroomId, int $week): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM classroom_phases WHERE classroom_id = ? AND week_number = ?");
        $stmt->execute([$classroomId, $week]);
        return (bool) $stmt->fetch();
    }
}

if (!function_exists('phase_merge_target_of')) {
    /** merged_into_week for a phase: int|null, or false when the row does not exist. */
    function phase_merge_target_of(PDO $pdo, $classroomId, int $week)
    {
        $stmt = $pdo->prepare("SELECT merged_into_week FROM classroom_phases WHERE classroom_id = ? AND week_number = ?");
        $stmt->execute([$classroomId, $week]);
        return $stmt->fetchColumn();
    }
}

if (!function_exists('phase_absorbed_count')) {
    /** How many phases currently roll up into $week. */
    function phase_absorbed_count(PDO $pdo, $classroomId, int $week): int
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM classroom_phases WHERE classroom_id = ? AND merged_into_week = ?");
        $stmt->execute([$classroomId, $week]);
        return (int) $stmt->fetchColumn();
    }
}

if (!function_exists('phase_rename')) {
    function phase_rename(PDO $pdo, $classroomId, int $week, string $label): void
    {
        $stmt = $pdo->prepare("UPDATE classroom_phases SET label = ? WHERE classroom_id = ? AND week_number = ?");
        $stmt->execute([$label, $classroomId, $week]);
    }
}

if (!function_exists('phase_set_merge')) {
    /** Merge $week into $target, or pass null to unmerge. */
    function phase_set_merge(PDO $pdo, $classroomId, int $week, ?int $target): void
    {
        $stmt = $pdo->prepare("UPDATE classroom_phases SET merged_into_week = ? WHERE classroom_id = ? AND week_number = ?");
        $stmt->execute([$target, $classroomId, $week]);
    }
}
