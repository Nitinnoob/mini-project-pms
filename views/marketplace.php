<!-- ============================================================ -->
        <!-- MARKETPLACE — Unassigned Student View                         -->
        <!-- ============================================================ -->
        <div class="col-span-4 flex flex-col h-full">
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="text-2xl font-head font-semibold">Project Marketplace</h2>
                    <p class="text-muted-ui text-sm mt-1">Browse available projects to join, or start your own team.</p>
                </div>
                <a href="create_subproject.php?classroom_id=<?php echo urlencode($viewData['classroom_id']); ?>" class="btn-ui px-4 py-2 text-sm font-semibold hover:opacity-90 transition" style="background: var(--accent-2); color: var(--bg);">
                    <i class="fas fa-plus mr-1"></i> Start a Project
                </a>
            </div>

            <?php
            // Invitations awaiting this student's response — surfaced as a banner, not buried in the grid.
            $invitedProjects = array_values(array_filter(
                $viewData['availableProjects'] ?? [],
                fn($p) => ($p['my_status'] ?? null) === 'Invited'
            ));
            ?>

            <?php if (!empty($invitedProjects)): ?>
            <div class="mb-6">
                <?php foreach ($invitedProjects as $inv): ?>
                <div class="card p-5 mb-3 flex flex-wrap items-center justify-between gap-3" style="border-color: var(--accent-2);">
                    <div class="flex items-center gap-3 min-w-0">
                        <i class="fas fa-envelope-open-text text-xl flex-none" style="color: var(--accent-2);"></i>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm">
                                Invitation to join <span class="font-head"><?php echo htmlspecialchars($inv['name']); ?></span>
                            </p>
                            <p class="text-xs text-muted-ui mt-1">A project leader invited you to their team. Accepting will add you to their roster.</p>
                        </div>
                    </div>
                    <div class="flex gap-2 flex-none">
                        <button type="button"
                                class="invite-respond-btn btn-ui px-4 py-2 text-xs font-semibold transition"
                                style="background: var(--accent); color: var(--bg); border-color: var(--accent);"
                                data-project-id="<?php echo $inv['id']; ?>"
                                data-classroom-id="<?php echo htmlspecialchars($viewData['classroom_id']); ?>"
                                data-invite-action="accept_invite">
                            <i class="fas fa-check mr-1"></i> Accept
                        </button>
                        <button type="button"
                                class="invite-respond-btn btn-ui px-4 py-2 text-xs font-semibold transition"
                                style="color: var(--danger); border-color: var(--danger);"
                                data-project-id="<?php echo $inv['id']; ?>"
                                data-classroom-id="<?php echo htmlspecialchars($viewData['classroom_id']); ?>"
                                data-invite-action="decline_invite">
                            Decline
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($viewData['availableProjects'])): ?>
            <div class="card p-10 text-center flex-1 flex flex-col items-center justify-center border-dashed">
                <i class="fas fa-folder-open text-4xl mb-4 text-muted-ui opacity-50"></i>
                <h3 class="text-lg font-bold mb-2">No projects yet</h3>
                <p class="text-muted-ui text-sm mb-6 max-w-md mx-auto">It looks like no one has started a project in this classroom yet. Be the first to start a team!</p>
                <a href="create_subproject.php?classroom_id=<?php echo urlencode($viewData['classroom_id']); ?>" class="btn-ui px-6 py-2 text-sm font-semibold" style="background: var(--accent-2); color: var(--bg);">
                    Start a Project
                </a>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($viewData['availableProjects'] as $proj):
                    $isPending = ($proj['my_status'] === 'Pending');
                    $isInvited = ($proj['my_status'] === 'Invited');
                ?>
                <div class="card p-6 flex flex-col hover:border-[color:var(--accent-2)] transition-colors">
                    <h4 class="text-xl font-bold mb-2 truncate"><?php echo htmlspecialchars($proj['name']); ?></h4>
                    <p class="text-sm text-muted-ui mb-4 flex-1 overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                        <?php echo htmlspecialchars($proj['description'] ?: 'No description provided.'); ?>
                    </p>
                    <div class="flex justify-between items-center pt-4 border-t border-ui">
                        <div class="text-xs font-semibold text-muted-ui flex items-center gap-1">
                            <i class="fas fa-users"></i> <?php echo (int)$proj['active_members']; ?> members
                        </div>
                        <?php if ($isInvited): ?>
                        <span class="px-4 py-1.5 text-xs font-semibold rounded" style="background: var(--accent-2); color: var(--bg);">
                            <i class="fas fa-envelope mr-1"></i> Invited
                        </span>
                        <?php elseif ($isPending): ?>
                        <form method="POST" action="manage_join_request.php" class="inline">
                            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
                            <input type="hidden" name="project_id" value="<?php echo $proj['id']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                            <input type="hidden" name="action" value="withdraw">
                            <button type="submit" class="btn-ui px-4 py-1.5 text-xs font-semibold hover-overlay-medium transition" style="color: var(--danger); border-color: var(--danger);">
                                <i class="fas fa-times mr-1"></i> Withdraw Request
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="request_join.php">
                            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
                            <input type="hidden" name="project_id" value="<?php echo $proj['id']; ?>">
                            <button type="submit" class="btn-ui px-4 py-1.5 text-xs font-semibold hover-overlay-medium transition" style="color: var(--accent); border-color: var(--accent);">
                                Request to Join
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <script>
            document.querySelectorAll('.invite-respond-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var projectId = btn.getAttribute('data-project-id');
                    var classroomId = btn.getAttribute('data-classroom-id');
                    var inviteAction = btn.getAttribute('data-invite-action');
                    var originalHtml = btn.innerHTML;

                    document.querySelectorAll('.invite-respond-btn').forEach(function (b) { b.disabled = true; });

                    fetch('handle_invitation.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            classroom_id: classroomId,
                            project_id: projectId,
                            user_id: <?php echo (int)$_SESSION['user_id']; ?>,
                            action: inviteAction
                        })
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            // Accepting changes the student's own dashboard view, so reload.
                            window.location.reload();
                        } else {
                            alert(data.message || 'Something went wrong. Please try again.');
                            document.querySelectorAll('.invite-respond-btn').forEach(function (b) { b.disabled = false; });
                            btn.innerHTML = originalHtml;
                        }
                    })
                    .catch(function () {
                        alert('Could not reach the server. Please try again.');
                        document.querySelectorAll('.invite-respond-btn').forEach(function (b) { b.disabled = false; });
                        btn.innerHTML = originalHtml;
                    });
                });
            });
        </script>