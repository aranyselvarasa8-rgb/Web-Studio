<?php

/**
 * Shared inner-page hero (navy + gold, framed image on the right).
 * Every page passes its own image, so each hero looks different.
 *
 * $h = [
 *   'eyebrow' => 'ABOUT US',
 *   'title'   => 'Creating memories',   // white part
 *   'accent'  => 'that last.',          // gold italic part
 *   'text'    => 'Paragraph...',
 *   'image'   => 'https://... or assets/images/...',
 *   'alt'     => 'Image description',
 *   'tag'     => ['Moments', 'Stories', 'Memories'],
 *   'primary' => ['Our Services', 'services.php'],
 *   'secondary' => ['Book Now', 'booking.php'],
 * ]
 */
function page_hero(array $h): void
{
    $e = function ($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    };
    $fallback = 'assets/images/hero-studio.png';
    ?>
<section class="page-hero">
    <span class="ph-dot ph-dot-1"></span>
    <span class="ph-dot ph-dot-2"></span>
    <span class="ph-dot ph-dot-3"></span>

    <div class="container page-hero-grid">

        <div class="page-hero-text">
            <span class="ph-eyebrow"><?= $e($h['eyebrow']) ?></span>

            <h1>
                <?= $e($h['title']) ?>
                <?php if (!empty($h['accent'])): ?>
                    <em><?= $e($h['accent']) ?></em>
                <?php endif; ?>
            </h1>

            <?php if (!empty($h['text'])): ?>
                <p><?= $e($h['text']) ?></p>
            <?php endif; ?>

            <div class="ph-actions">
                <?php if (!empty($h['primary'])): ?>
                    <a class="ph-btn ph-btn-gold" href="<?= $e($h['primary'][1]) ?>"><?= $e($h['primary'][0]) ?> <span aria-hidden="true">›</span></a>
                <?php endif; ?>
                <?php if (!empty($h['secondary'])): ?>
                    <a class="ph-btn ph-btn-line" href="<?= $e($h['secondary'][1]) ?>"><?= $e($h['secondary'][0]) ?></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-hero-media">
            <div class="ph-frame">
                <img
                    src="<?= $e($h['image']) ?>"
                    alt="<?= $e($h['alt'] ?? '') ?>"
                    onerror="this.onerror=null;this.src='<?= $fallback ?>'"
                >
            </div>

            <?php if (!empty($h['tag'])): ?>
                <div class="ph-tag">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3v11H4z"></path><circle cx="12" cy="13" r="3.4"></circle></svg>
                    <span><?= $e(implode(' • ', $h['tag'])) ?></span>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>
<?php
}
