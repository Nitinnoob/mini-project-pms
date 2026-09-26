    <?php if ($viewData['actualView'] !== 'Marketplace'): ?>
    <!-- Global project progress -->
    <!--<div class="px-6 pt-5">
        <div class="flex justify-between items-baseline mb-2">
            <h2 class="text-lg font-head font-semibold"><?php echo $headerTitle; ?></h2>
            <span class="text-sm font-mono-ui">
                <span class="font-bold"><?php echo $viewData['progressPercent']; ?>%</span>
                <span class="text-muted-ui"> — <?php echo $viewData['progressWord']; ?></span>
            </span>
        </div>
        <div class="progress-track w-full h-2.5">
            <div class="progress-fill h-full status-<?php echo $viewData['progressStatus']; ?>" style="width: <?php echo $viewData['progressPercent']; ?>%;"></div>
        </div>
    </div>-->
    <?php endif; ?>

    <!-- Main workspace -->
    <div class="flex-1 p-6 grid grid-cols-4 gap-6">

