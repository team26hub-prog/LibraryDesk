<article class="metric-panel member-metric-panel metric-<?= e($statTone) ?>">
    <span class="metric-icon"><?= icon($statIcon) ?></span>
    <div class="metric-copy">
        <p class="metric-value"><?= (int) $statValue ?></p>
        <p class="metric-label"><?= e(ucfirst(strtolower($statLabel))) ?></p>
        <p class="metric-note"><?= e($statNote) ?></p>
    </div>
</article>
