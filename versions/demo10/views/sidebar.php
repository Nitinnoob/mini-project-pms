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

            <div class="card p-5 flex-1 flex flex-col">
                <h3 class="font-semibold text-sm mb-4 font-head"><i class="fas fa-satellite-dish me-2 text-accent"></i>Live activity</h3>
                <div id="activity-feed" class="space-y-4 overflow-hidden relative flex-1 text-sm"></div>
            </div>
        </div>
