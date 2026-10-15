<?php
// Pure presentation helpers shared by views. No SQL here.
// Guarded so repeated includes never trigger "cannot redeclare" fatals.

if (!function_exists('e')) {
    // PHP 8 null-safe HTML escape. Accepts null/int/float/bool; never throws
    // TypeError the way htmlspecialchars(null) does. Extra args mirror
    // htmlspecialchars() so existing call sites can be swapped 1:1.
    function e(mixed $value, int $flags = ENT_QUOTES, ?string $encoding = 'UTF-8'): string
    {
        if ($value === null || is_array($value) || is_object($value) && !method_exists($value, '__toString')) {
            return '';
        }
        return htmlspecialchars((string) $value, $flags | ENT_SUBSTITUTE, $encoding ?? 'UTF-8');
    }
}

if (!function_exists('render_status_badge')) {
    /**
     * Unified Status Badge Component (Section 5 of UI/UX Cleanup Plan)
     * Always pairs semantic color with an icon and clear text for accessibility.
     */
    function render_status_badge(string $status, string $customLabel = ''): string
    {
        $normalized = strtolower(trim($status));
        return match ($normalized) {
            'needs attention', 'warning', 'critical', 'at risk', 'revision_needed' =>
                '<span class="badge badge-amber text-xs font-mono-ui inline-flex items-center gap-1.5 px-2.5 py-0.5"><i class="fas fa-exclamation-triangle text-amber-500"></i><span>' . e($customLabel ?: 'Needs Attention') . '</span></span>',
            'pending', 'neutral', 'scheduled', 'draft', 'unsubmitted', 'provisional' =>
                '<span class="badge badge-muted text-xs font-mono-ui inline-flex items-center gap-1.5 px-2.5 py-0.5"><i class="fas fa-clock text-muted-ui"></i><span>' . e($customLabel ?: 'Pending') . '</span></span>',
            'on track', 'healthy', 'approved', 'held' =>
                '<span class="badge badge-green text-xs font-mono-ui inline-flex items-center gap-1.5 px-2.5 py-0.5"><i class="fas fa-check text-emerald-500"></i><span>' . e($customLabel ?: 'On Track') . '</span></span>',
            'finalized', 'locked' =>
                '<span class="badge badge-accent-2 text-xs font-mono-ui inline-flex items-center gap-1.5 px-2.5 py-0.5"><i class="fas fa-lock text-accent-2"></i><span>' . e($customLabel ?: 'Finalized') . '</span></span>',
            default =>
                '<span class="badge badge-muted text-xs font-mono-ui inline-flex items-center gap-1.5 px-2.5 py-0.5"><span>' . e($customLabel ?: ucfirst($status)) . '</span></span>',
        };
    }
}

