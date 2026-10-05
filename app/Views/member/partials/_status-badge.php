<?php
$badgeClasses = [
    'borrowed' => 'pill-green',
    'returned' => 'pill-muted',
    'overdue' => 'pill-red',
    'due-soon' => 'pill-yellow',
    'available' => 'pill-green',
    'unavailable' => 'pill-muted',
];
$badgeLabels = [
    'borrowed' => 'Borrowed',
    'returned' => 'Returned',
    'overdue' => 'Overdue',
    'due-soon' => 'Due soon',
    'available' => 'Available',
    'unavailable' => 'Not available',
];
$badgeClass = $badgeClasses[$badgeStatus] ?? $badgeClasses['borrowed'];
$badgeText = $badgeLabel ?? ($badgeLabels[$badgeStatus] ?? 'Borrowed');
?>
<span class="pill <?= e($badgeClass) ?>"><?= e($badgeText) ?></span>
