<?php
/**
 * Classroom Milestones Repository.
 * Handles coordinator-defined project milestones, due dates, and countdown calculation.
 */

if (!function_exists('classroom_milestones_get_all')) {
    /**
     * Retrieve all milestones for a classroom with calculated countdown metadata.
     *
     * @param PDO $pdo
     * @param int $classroomId
     * @param string|null $referenceDate Optional Y-m-d date string for deterministic testing (defaults to today)
     * @return array<int, array{
     *     id: int,
     *     classroom_id: int,
     *     title: string,
     *     due_date: string,
     *     formatted_due_date: string,
     *     days_remaining: int,
     *     countdown_label: string,
     *     status: 'upcoming'|'due_today'|'passed',
     *     badge_class: string,
     *     is_upcoming: bool,
     *     is_due_today: bool,
     *     is_overdue: bool
     * }>
     */
    function classroom_milestones_get_all(PDO $pdo, int $classroomId, ?string $referenceDate = null): array
    {
        $stmt = $pdo->prepare("
            SELECT id, classroom_id, title, due_date, created_at
            FROM classroom_milestones
            WHERE classroom_id = ?
            ORDER BY due_date ASC, id ASC
        ");
        $stmt->execute([$classroomId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ref = $referenceDate !== null ? new DateTime($referenceDate) : new DateTime('today');
        $ref->setTime(0, 0, 0);

        $milestones = [];
        foreach ($rows as $r) {
            $due = new DateTime($r['due_date']);
            $due->setTime(0, 0, 0);

            // Diff in whole days (positive = in future, negative = in past, 0 = today)
            $diffDays = (int)$ref->diff($due)->format('%r%a');

            if ($diffDays > 0) {
                $status = 'upcoming';
                $countdownLabel = $diffDays === 1 ? '1 day left' : "{$diffDays} days left";
                $badgeClass = $diffDays <= 3 ? 'badge-warning' : 'badge-accent';
                $isUpcoming = true;
                $isDueToday = false;
                $isOverdue = false;
            } elseif ($diffDays === 0) {
                $status = 'due_today';
                $countdownLabel = 'Due today';
                $badgeClass = 'badge-warning';
                $isUpcoming = false;
                $isDueToday = true;
                $isOverdue = false;
            } else {
                $status = 'passed';
                $pastDays = abs($diffDays);
                $countdownLabel = $pastDays === 1 ? 'Passed 1 day ago' : "Passed {$pastDays} days ago";
                $badgeClass = 'badge-muted';
                $isUpcoming = false;
                $isDueToday = false;
                $isOverdue = true;
            }

            $milestones[] = [
                'id'                 => (int)$r['id'],
                'classroom_id'       => (int)$r['classroom_id'],
                'title'              => $r['title'],
                'due_date'           => $r['due_date'],
                'formatted_due_date' => $due->format('M j, Y'),
                'days_remaining'     => $diffDays,
                'countdown_label'    => $countdownLabel,
                'status'             => $status,
                'badge_class'        => $badgeClass,
                'is_upcoming'        => $isUpcoming,
                'is_due_today'       => $isDueToday,
                'is_overdue'         => $isOverdue,
            ];
        }

        return $milestones;
    }
}

if (!function_exists('classroom_milestone_create')) {
    /**
     * Create a new classroom milestone.
     *
     * @throws InvalidArgumentException
     */
    function classroom_milestone_create(PDO $pdo, int $classroomId, string $title, string $dueDate): int
    {
        $trimmedTitle = trim($title);
        if ($trimmedTitle === '') {
            throw new InvalidArgumentException("Milestone title cannot be empty.");
        }
        if (mb_strlen($trimmedTitle) > 255) {
            $trimmedTitle = mb_substr($trimmedTitle, 0, 255);
        }

        $trimmedDate = trim($dueDate);
        $d = DateTime::createFromFormat('Y-m-d', $trimmedDate);
        if (!$d || $d->format('Y-m-d') !== $trimmedDate) {
            throw new InvalidArgumentException("Invalid due date format. Expected YYYY-MM-DD.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO classroom_milestones (classroom_id, title, due_date)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$classroomId, $trimmedTitle, $trimmedDate]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('classroom_milestone_find')) {
    /**
     * Find a milestone by id and classroom id.
     */
    function classroom_milestone_find(PDO $pdo, int $classroomId, int $milestoneId): ?array
    {
        $stmt = $pdo->prepare("
            SELECT id, classroom_id, title, due_date, created_at
            FROM classroom_milestones
            WHERE classroom_id = ? AND id = ?
        ");
        $stmt->execute([$classroomId, $milestoneId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('classroom_milestone_delete')) {
    /**
     * Delete a milestone scoped to a classroom.
     */
    function classroom_milestone_delete(PDO $pdo, int $classroomId, int $milestoneId): bool
    {
        $stmt = $pdo->prepare("
            DELETE FROM classroom_milestones
            WHERE classroom_id = ? AND id = ?
        ");
        $stmt->execute([$classroomId, $milestoneId]);
        return $stmt->rowCount() > 0;
    }
}

if (!function_exists('classroom_milestone_update')) {
    /**
     * Update an existing milestone.
     *
     * @throws InvalidArgumentException
     */
    function classroom_milestone_update(PDO $pdo, int $classroomId, int $milestoneId, string $title, string $dueDate): bool
    {
        $trimmedTitle = trim($title);
        if ($trimmedTitle === '') {
            throw new InvalidArgumentException("Milestone title cannot be empty.");
        }
        if (mb_strlen($trimmedTitle) > 255) {
            $trimmedTitle = mb_substr($trimmedTitle, 0, 255);
        }

        $trimmedDate = trim($dueDate);
        $d = DateTime::createFromFormat('Y-m-d', $trimmedDate);
        if (!$d || $d->format('Y-m-d') !== $trimmedDate) {
            throw new InvalidArgumentException("Invalid due date format. Expected YYYY-MM-DD.");
        }

        $stmt = $pdo->prepare("
            UPDATE classroom_milestones
            SET title = ?, due_date = ?
            WHERE classroom_id = ? AND id = ?
        ");
        $stmt->execute([$trimmedTitle, $trimmedDate, $classroomId, $milestoneId]);
        return $stmt->rowCount() > 0;
    }
}
