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
                        <?php if ($isPending): ?>
                        <button class="px-4 py-1.5 text-xs font-semibold rounded bg-black/10 cursor-not-allowed opacity-70" disabled>
                            <i class="fas fa-clock mr-1"></i> Pending
                        </button>
                        <?php else: ?>
                        <form method="POST" action="request_join.php">
                            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($viewData['classroom_id']); ?>">
                            <input type="hidden" name="project_id" value="<?php echo $proj['id']; ?>">
                            <button type="submit" class="btn-ui px-4 py-1.5 text-xs font-semibold hover:bg-black/10 transition" style="color: var(--accent); border-color: var(--accent);">
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

