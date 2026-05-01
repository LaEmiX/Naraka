<?php

declare(strict_types=1);

if (!isset($pdo)) {
    return;
}

if (!function_exists('render_bbcode')) {
    function render_bbcode(string $value): string
    {
        $text = e($value);

        $text = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $text);
        $text = preg_replace('/\[u\](.*?)\[\/u\]/is', '<u>$1</u>', $text);
        $text = preg_replace('/\[i\](.*?)\[\/i\]/is', '<em>$1</em>', $text);
        $text = preg_replace('/\[center\](.*?)\[\/center\]/is', '<div style="text-align:center;">$1</div>', $text);
        $text = preg_replace('/\[left\](.*?)\[\/left\]/is', '<div style="text-align:left;">$1</div>', $text);
        $text = preg_replace('/\[right\](.*?)\[\/right\]/is', '<div style="text-align:right;">$1</div>', $text);
        $text = preg_replace('/\[justify\](.*?)\[\/justify\]/is', '<div style="text-align:justify;">$1</div>', $text);
        $text = preg_replace('/\[color=([#a-zA-Z0-9]+)\](.*?)\[\/color\]/is', '<span style="color:$1;">$2</span>', $text);

        return nl2br((string) $text);
    }
}

$stmt = $pdo->query("
    SELECT name, slug, description, image_path
    FROM city_districts
    WHERE is_active = 1
    ORDER BY sort_order ASC, name ASC
");

$districts = $stmt->fetchAll();

?>

<div class="districts-grid">

    <?php foreach ($districts as $district) { ?>

        <div class="district-card">

            <a href="/index.php?district=<?php echo e((string) $district['slug']); ?>" class="district-image-wrap">
                <img
                    src="<?php echo e((string) ($district['image_path'] ?: '/themes/images/default_district.jpg')); ?>"
                    class="district-image"
                    alt="<?php echo e((string) $district['name']); ?>"
                >
            </a>

            <div class="district-meta">
                <span class="district-name"><?php echo e((string) $district['name']); ?></span>

                <button
                    type="button"
                    class="district-info-btn"
                    data-description="<?php echo e(render_bbcode((string) $district['description'])); ?>"
                    aria-label="Descrizione quartiere"
                >
                    <svg viewBox="0 0 24 24" class="district-info-icon" aria-hidden="true" focusable="false">
                        <circle cx="12" cy="12" r="9"></circle>
                        <line x1="12" y1="11" x2="12" y2="16"></line>
                        <circle cx="12" cy="7.6" r="1.1"></circle>
                    </svg>
                </button>
            </div>

        </div>

    <?php } ?>

</div>

<div id="district-modal" class="district-modal">
    <div class="district-modal-content">
        <button type="button" class="district-modal-close" aria-label="Chiudi">×</button>
        <div id="district-modal-body"></div>
    </div>
</div>

<script>
document.querySelectorAll('.district-info-btn').forEach(function (button) {
    button.addEventListener('click', function () {
        const modal = document.getElementById('district-modal');
        const body = document.getElementById('district-modal-body');

        body.innerHTML = this.getAttribute('data-description') || '';
        modal.style.display = 'flex';
    });
});

document.querySelector('.district-modal-close').addEventListener('click', function () {
    document.getElementById('district-modal').style.display = 'none';
});

document.getElementById('district-modal').addEventListener('click', function (event) {
    if (event.target === this) {
        this.style.display = 'none';
    }
});
</script>

<?php
// by LaEmiX