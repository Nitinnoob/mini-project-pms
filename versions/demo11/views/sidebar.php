        <!-- Right: analytics sidebar (Student / Leader only) -->
        <div class="flex flex-col gap-6">
            <div class="card p-5" style="border-top: 2px solid var(--accent-2);">
                <h3 class="font-semibold text-sm mb-4 font-head"><i class="fas fa-chart-pie me-2 text-accent-2"></i>Contribution tracker</h3>
                <div class="relative h-48 w-full">
                    <canvas id="contributionChart"></canvas>
                </div>

                <div class="mt-5 pt-4 border-t border-ui">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-muted-ui font-semibold">Activity heatmap</span>
                        <span class="text-[10px] text-muted-ui">last 10 weeks</span>
                    </div>
                    <div class="flex gap-[3px] overflow-x-auto pb-1">
                        <?php for ($w = 0; $w < $heatmapWeeks; $w++): ?>
                        <div class="flex flex-col gap-[3px]">
                            <?php for ($day = 0; $day < 7; $day++):
                                $level = heat_level($heatmapPattern[$w * 7 + $day] ?? 0);
                                $opacities = [0.12, 0.3, 0.5, 0.75, 1];
                            ?>
                            <div class="heat-square <?php echo $level === 4 ? 'heat-glow' : ''; ?>"
                                style="background: var(--accent-2); opacity: <?php echo $opacities[$level]; ?>;"
                                title="<?php echo $heatmapPattern[$w * 7 + $day] ?? 0; ?> tasks checked off"></div>
                            <?php endfor; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="flex items-center justify-end gap-1 mt-2 text-[10px] text-muted-ui">
                        <span>less</span>
                        <?php foreach ([0.12, 0.3, 0.5, 0.75, 1] as $o): ?>
                            <span class="heat-square" style="background: var(--accent-2); opacity: <?php echo $o; ?>;"></span>
                        <?php endforeach; ?>
                        <span>more</span>
                    </div>
                </div>
            </div>

            <!-- Deliverables Feed -->
            <div class="card p-4 flex-1 flex flex-col h-[280px]">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold font-head">Deliverables</h3>
                    <i class="fas fa-folder text-muted-ui"></i>
                </div>
                <div class="flex-1 overflow-y-auto pr-2 space-y-3">
                    <?php if (!empty($viewData['deliverables'])): ?>
                        <?php foreach ($viewData['deliverables'] as $file): ?>
                        <div class="flex items-center justify-between p-2 rounded border border-ui hover:border-accent transition" style="background: rgba(0,0,0,0.2);">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-8 h-8 rounded bg-panel flex items-center justify-center flex-shrink-0 text-accent">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="min-w-0">
                                    <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="block text-sm font-semibold truncate hover:text-accent transition"><?php echo htmlspecialchars($file['file_name']); ?></a>
                                    <span class="block text-xs text-muted-ui truncate">by <?php echo htmlspecialchars($file['uploader_name']); ?></span>
                                </div>
                            </div>
                            <a href="<?php echo htmlspecialchars($file['file_path']); ?>" download class="text-muted-ui hover:text-white ml-2"><i class="fas fa-download"></i></a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="h-full flex flex-col items-center justify-center text-muted-ui text-sm italic">
                            <i class="fas fa-cloud-upload-alt text-2xl mb-2 opacity-50"></i>
                            No files uploaded yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Audit Log Feed -->
            <div class="card p-4 flex-1 flex flex-col h-[280px]">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold font-head">Audit Log</h3>
                    <i class="fas fa-history text-muted-ui"></i>
                </div>
                <div class="flex-1 overflow-y-auto pr-2 space-y-4">
                    <?php if (!empty($viewData['activity_log'])): ?>
                        <?php foreach ($viewData['activity_log'] as $log): ?>
                            <div class="flex gap-3">
                                <div class="mt-1">
                                    <?php if ($log['action'] === 'Created Task'): ?>
                                        <i class="fas fa-plus text-accent-2"></i>
                                    <?php elseif ($log['action'] === 'Updated Task'): ?>
                                        <i class="fas fa-exchange-alt text-muted-ui"></i>
                                    <?php elseif ($log['action'] === 'Uploaded Deliverable'): ?>
                                        <i class="fas fa-upload text-accent"></i>
                                    <?php else: ?>
                                        <i class="fas fa-info-circle text-muted-ui"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-sm"><span class="font-semibold"><?php echo htmlspecialchars($log['username']); ?></span> <?php echo htmlspecialchars($log['details']); ?></p>
                                    <span class="text-xs text-muted-ui"><?php echo htmlspecialchars(date('M j, g:i A', strtotime($log['created_at']))); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="h-full flex flex-col items-center justify-center text-muted-ui text-sm italic">
                            <i class="fas fa-shoe-prints text-2xl mb-2 opacity-50"></i>
                            No activity recorded yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
