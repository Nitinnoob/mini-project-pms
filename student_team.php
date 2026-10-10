<?php
// views/student_team.php - Team formation. Included by student.php until the guide approves the team's idea.
// Expects: $pdo, $user, $classroom, $classroomId and $project (false if the student has no team yet).

$maxTeam  = 4;
$isLeader = $project && $project['created_by'] == $user['id'];

if (!$project) {
    // Teams in this classroom that are still forming and have a free seat
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.description, leader.name AS leader_name, mentor.name AS mentor_name,
               (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) AS member_count,
               (SELECT GROUP_CONCAT(u.name ORDER BY pm.is_leader DESC, u.name SEPARATOR ', ')
                  FROM project_members pm JOIN users u ON u.id = pm.student_id
                 WHERE pm.project_id = p.id) AS member_names,
               (SELECT status FROM team_requests WHERE project_id = p.id AND student_id = ?) AS my_request
        FROM projects p
        JOIN users leader ON p.created_by = leader.id
        JOIN users mentor ON p.mentor_id = mentor.id
        WHERE p.classroom_id = ? AND p.status = 'forming'
          AND (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) < ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user['id'], $classroomId, $maxTeam]);
    $teams = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.identifier, pm.is_leader
        FROM project_members pm JOIN users u ON pm.student_id = u.id
        WHERE pm.project_id = ?
        ORDER BY pm.is_leader DESC, u.name ASC
    ");
    $stmt->execute([$project['id']]);
    $members = $stmt->fetchAll();

    $requests = [];
    if ($isLeader && $project['status'] === 'forming') {
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.identifier
            FROM team_requests tr JOIN users u ON tr.student_id = u.id
            WHERE tr.project_id = ? AND tr.status = 'pending'
            ORDER BY tr.created_at ASC
        ");
        $stmt->execute([$project['id']]);
        $requests = $stmt->fetchAll();
    }
}

include __DIR__ . '/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-8 space-y-6">

<?php if (!$project): ?>

    <section class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
        <div>
            <p class="text-sm text-zinc-500"><?= h($classroom['name']) ?>, <?= h($classroom['institution']) ?></p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight">Find a team</h1>
            <p class="mt-1 text-zinc-600">
                Teams have up to <?= $maxTeam ?> students. Ask to join a team that is still forming, or start your own idea
                and let classmates ask to join you. Your faculty guide approves the final idea.
            </p>
        </div>
        <a href="create_project.php?classroom_id=<?= (int)$classroomId ?>" class="btn btn-primary shrink-0">Start a team</a>
    </section>

    <?php if (!$teams): ?>
        <div class="panel p-8 text-center text-zinc-500">No teams are forming yet. Start one and your classmates can ask to join.</div>
    <?php endif; ?>

    <?php foreach ($teams as $t): ?>
        <article class="panel p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold"><?= h($t['title']) ?></h2>
                    <p class="muted">Guide: <?= h($t['mentor_name']) ?></p>
                </div>
                <span class="tag tag-info shrink-0"><?= (int)$t['member_count'] ?> of <?= $maxTeam ?></span>
            </div>
            <p class="mt-2 text-sm text-zinc-600 line-clamp-3"><?= h($t['description']) ?></p>
            <p class="mt-2 text-sm"><span class="text-zinc-500">Team:</span> <?= h($t['member_names']) ?></p>

            <div class="mt-4">
                <?php if ($t['my_request'] === 'pending'): ?>
                    <span class="tag tag-mute">Request sent. Waiting for <?= h($t['leader_name']) ?>.</span>
                <?php else: ?>
                    <form action="manage_team.php" method="POST" class="flex items-center gap-3">
                        <input type="hidden" name="action" value="request_join">
                        <input type="hidden" name="project_id" value="<?= (int)$t['id'] ?>">
                        <button type="submit" class="btn"><?= $t['my_request'] === 'declined' ? 'Ask again' : 'Ask to join' ?></button>
                        <?php if ($t['my_request'] === 'declined'): ?><span class="muted">Your last request was declined.</span><?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>

<?php else: ?>
    <?php [$statusLabel, $statusClass] = project_status($project['status']); ?>

    <section>
        <p class="text-sm text-zinc-500"><?= h($classroom['name']) ?>, <?= h($classroom['institution']) ?></p>
        <div class="mt-1 flex items-start justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight"><?= h($project['title']) ?></h1>
            <span class="tag shrink-0 <?= $statusClass ?>"><?= $statusLabel ?></span>
        </div>
        <p class="mt-2 text-zinc-600 whitespace-pre-line"><?= h($project['description']) ?></p>
        <p class="mt-3 text-sm"><span class="text-zinc-500">Faculty guide:</span> <?= h($project['mentor_name']) ?> (<?= h($project['mentor_code']) ?>)</p>
    </section>

    <!-- Status message -->
    <?php if ($project['status'] === 'pending'): ?>
        <div class="border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">Waiting for approval</p>
            <p><?= h($project['mentor_name']) ?> is reviewing your idea. Weekly reviews start once it is approved.</p>
        </div>
    <?php elseif ($project['status'] === 'rejected'): ?>
        <div class="border-l-4 border-red-600 bg-red-50 px-4 py-3 text-sm text-red-900">
            <p class="font-semibold">Your guide asked for changes</p>
            <p class="whitespace-pre-line"><?= h($project['review_note'] ?: 'No note was left.') ?></p>
            <?php if (!$isLeader): ?><p class="mt-1 text-red-700">Your team leader can revise and resubmit the idea.</p><?php endif; ?>
        </div>
    <?php elseif ($isLeader): ?>
        <div class="border-l-4 border-teal-600 bg-teal-50 px-4 py-3 text-sm text-teal-900">
            <p class="font-semibold">Your team is open</p>
            <p>Classmates can see it under Find a team and ask to join. When your team is ready, submit it to your guide.</p>
        </div>
    <?php else: ?>
        <div class="border-l-4 border-teal-600 bg-teal-50 px-4 py-3 text-sm text-teal-900">
            You have joined this team. The team leader will submit the idea to your guide when everyone is in.
        </div>
    <?php endif; ?>

    <!-- Members -->
    <section class="panel p-5">
        <div class="flex items-center justify-between mb-2">
            <h2 class="font-semibold">Team</h2>
            <span class="muted"><?= count($members) ?> of <?= $maxTeam ?></span>
        </div>
        <ul class="divide-y divide-zinc-100 text-sm">
            <?php foreach ($members as $m): ?>
                <li class="py-2"><?= h($m['name']) ?> <span class="text-zinc-500">(<?= h($m['identifier']) ?>)</span><?= $m['is_leader'] ? ' <span class="tag tag-info">Leader</span>' : '' ?></li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$isLeader && $project['status'] === 'forming'): ?>
            <form action="manage_team.php" method="POST" class="mt-3" onsubmit="return confirm('Leave this team?');">
                <input type="hidden" name="action" value="leave">
                <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">
                <button type="submit" class="text-sm text-red-600 hover:underline">Leave team</button>
            </form>
        <?php endif; ?>
    </section>

    <!-- Join requests (leader, while forming) -->
    <?php if ($isLeader && $project['status'] === 'forming'): ?>
        <section class="panel p-5">
            <h2 class="font-semibold mb-2">Join requests</h2>
            <?php if (!$requests): ?>
                <p class="muted">No requests yet.</p>
            <?php else: ?>
                <ul class="divide-y divide-zinc-100 text-sm">
                    <?php foreach ($requests as $r): ?>
                        <li class="flex items-center justify-between gap-3 py-2">
                            <span><?= h($r['name']) ?> <span class="text-zinc-500">(<?= h($r['identifier']) ?>)</span></span>
                            <form action="manage_team.php" method="POST" class="flex gap-2">
                                <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">
                                <input type="hidden" name="student_id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" name="action" value="accept" class="btn btn-primary">Accept</button>
                                <button type="submit" name="action" value="decline" class="btn">Decline</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- Edit idea (leader, while forming or after changes were requested) -->
    <?php if ($isLeader && in_array($project['status'], ['forming', 'rejected'])): ?>
        <section class="panel p-5">
            <h2 class="font-semibold mb-3"><?= $project['status'] === 'rejected' ? 'Revise your idea' : 'Project idea' ?></h2>
            <form action="manage_team.php" method="POST" class="space-y-3">
                <input type="hidden" name="action" value="revise">
                <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">
                <div>
                    <label class="label">Title</label>
                    <input type="text" name="title" required value="<?= h($project['title']) ?>" class="field">
                </div>
                <div>
                    <label class="label">Abstract</label>
                    <textarea name="description" rows="4" class="field"><?= h($project['description']) ?></textarea>
                </div>
                <button type="submit" class="btn <?= $project['status'] === 'rejected' ? 'btn-primary' : '' ?>">
                    <?= $project['status'] === 'rejected' ? 'Resubmit for approval' : 'Save idea' ?>
                </button>
            </form>
        </section>
    <?php endif; ?>

    <!-- Submit to guide (leader, while forming) -->
    <?php if ($isLeader && $project['status'] === 'forming'): ?>
        <section class="panel p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold">Ready for your guide?</h2>
                <p class="muted">Submitting closes the team to new members. You cannot change the team afterwards.</p>
            </div>
            <form action="manage_team.php" method="POST" onsubmit="return confirm('Submit this team and idea to <?= h(addslashes($project['mentor_name'])) ?>?');">
                <input type="hidden" name="action" value="submit">
                <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">
                <button type="submit" class="btn btn-primary">Submit for approval</button>
            </form>
        </section>
    <?php endif; ?>

<?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
