<?php

$page_title = 'Packages | Web Studio';

require_once 'includes/config.php';
include 'includes/header.php';
require_once 'includes/page-hero.php';


/* ============================================================
   PACKAGE DATA
   ============================================================ */

$packages = [
    [
        'id' => 1,
        'name' => 'Basic Package',
        'price' => 10000,
        'description' => 'Simple photography package for small events.',
        'popular' => false,
        'features' => [
            'Professional Photography',
            'Edited Photos',
            'Digital Delivery',
            'Event Coverage'
        ]
    ],
    [
        'id' => 2,
        'name' => 'Birthday Package',
        'price' => 20000,
        'description' => 'Perfect package for birthday celebrations.',
        'popular' => false,
        'features' => [
            'Birthday Photography',
            'Edited Photos',
            'Digital Delivery',
            'Event Coverage'
        ]
    ],
    [
        'id' => 3,
        'name' => 'Silver Wedding Package',
        'price' => 85000,
        'description' => 'Complete photography coverage for weddings.',
        'popular' => false,
        'features' => [
            'Wedding Photography',
            'Bride & Groom Session',
            'Edited Photos',
            'Digital Album'
        ]
    ],
    [
        'id' => 4,
        'name' => 'Engagement Package',
        'price' => 30000,
        'description' => 'Beautiful photography package for engagements.',
        'popular' => false,
        'features' => [
            'Engagement Photography',
            'Couple Session',
            'Edited Photos',
            'Digital Delivery'
        ]
    ],
    [
        'id' => 5,
        'name' => 'Gold Wedding Package',
        'price' => 45000,
        'description' => 'Wedding photography package with quality coverage.',
        'popular' => false,
        'features' => [
            'Wedding Photography',
            'Couple Session',
            'Professional Editing',
            'Digital Album'
        ]
    ],
    [
        'id' => 6,
        'name' => 'Event Plus Package',
        'price' => 50000,
        'description' => 'Professional coverage for important events.',
        'popular' => false,
        'features' => [
            'Event Photography',
            'Event Videography',
            'Edited Photos',
            'Digital Delivery'
        ]
    ],
    [
        'id' => 7,
        'name' => 'Premium Event Package',
        'price' => 35000,
        'description' => 'Premium photography coverage for special events.',
        'popular' => false,
        'features' => [
            'Professional Photography',
            'Event Coverage',
            'Photo Editing',
            'Digital Delivery'
        ]
    ],
    [
        'id' => 8,
        'name' => 'Platinum Wedding Package',
        'price' => 80000,
        'description' => 'Premium wedding photography and videography.',
        'popular' => false,
        'features' => [
            'Wedding Photography',
            'Wedding Videography',
            'Couple Session',
            'Premium Album'
        ]
    ],
    [
        'id' => 9,
        'name' => 'Premium Wedding Package',
        'price' => 75000,
        'description' => 'Premium wedding coverage with professional editing.',
        'popular' => false,
        'features' => [
            'Wedding Photography',
            'Videography',
            'Professional Editing',
            'Digital Album'
        ]
    ],
    [
        'id' => 10,
        'name' => 'Luxury Wedding Package',
        'price' => 100000,
        'description' => 'Luxury wedding photography and complete event coverage.',
        'popular' => false,
        'features' => [
            'Full Wedding Coverage',
            'Photography & Videography',
            'Premium Editing',
            'Luxury Album'
        ]
    ],
    [
        'id' => 11,
        'name' => 'Ultimate Wedding Package',
        'price' => 125000,
        'description' => 'Ultimate package for complete wedding memories.',
        'popular' => false,
        'features' => [
            'Full Wedding Coverage',
            'Professional Photography',
            'Professional Videography',
            'Premium Album'
        ]
    ]
];

$displayPackages = array_slice($packages, 0, 5);

?>

<style>

/* ============================================================
   HERO BANNER
   ============================================================ */
.packages-banner::after {
    display: none;
}

/* ============================================================
   SEARCH AREA (CENTER ALIGNED)
   ============================================================ */
.package-search-wrapper { 
    width: min(1230px, 92%); 
    margin: 40px auto 20px; 
    display: flex; 
    justify-content: center; 
}

.package-search { 
    width: min(460px, 100%); 
    text-align: center; 
}

.package-search label { 
    display: block; 
    margin: 0 0 10px; 
    font-family: 'DM Sans', sans-serif; 
    font-size: 15px; 
    font-weight: 700; 
    color: #101d38; 
}

.package-search-box { 
    position: relative; 
    display: flex; 
    align-items: center; 
    height: 58px; 
    padding: 0 12px; 
    border: 1px solid #d9dee8; 
    border-radius: 14px; 
    background: #fff; 
    box-shadow: 0 10px 30px rgba(16, 29, 56, 0.08); 
    transition: all 0.3s ease; 
}

.package-search-box:focus-within { 
    border-color: #d5a52a; 
    box-shadow: 0 0 0 4px rgba(213, 165, 42, 0.15), 0 10px 30px rgba(16, 29, 56, 0.1); 
}

.package-search-box .search-icon-wrap { 
    flex: 0 0 auto; 
    width: 38px; 
    height: 38px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    border-radius: 10px; 
    background: #101d38; 
    color: #d5a52a; 
}

.package-search-box .search-icon { 
    width: 18px; 
    height: 18px; 
}

.package-search-box .search-prefix { 
    flex: 0 0 auto; 
    margin-left: 12px; 
    color: #b88a2d; 
    font-size: 15px; 
    font-weight: 700; 
}

.package-search input { 
    flex: 1 1 auto; 
    min-width: 0; 
    width: 100%; 
    height: 100%; 
    padding: 0 10px; 
    border: 0; 
    outline: none; 
    background: transparent; 
    color: #101d38; 
    font-family: 'DM Sans', sans-serif; 
    font-size: 16px; 
    font-weight: 500; 
}

.package-search input::placeholder { 
    color: #98a2b3; 
    font-size: 15px; 
}

.package-search .search-clear { 
    display: none; 
    flex: 0 0 auto; 
    height: 36px; 
    padding: 0 14px; 
    border: 0; 
    border-radius: 8px; 
    background: #101d38; 
    color: #fff; 
    font-family: 'DM Sans', sans-serif; 
    font-size: 13px; 
    font-weight: 700; 
    cursor: pointer; 
}

.package-search-box.has-value .search-clear { 
    display: block; 
}

/* ============================================================
   PACKAGE SECTION (WHITE BACKGROUND)
   ============================================================ */
.packages-section { 
    padding: 60px 0 100px; 
    overflow: hidden; 
    background: #ffffff; 
    position: relative;
}

.package-carousel { 
    width: min(1230px, 92%); 
    margin: 0 auto; 
}

.package-card { 
    position: relative;
    min-width: 0; 
    min-height: 500px; 
    display: flex; 
    flex-direction: column; 
    background: #ffffff; 
    border: 1px solid #e2e8f0; 
    border-radius: 20px; 
    padding: 32px 26px; 
    box-shadow: 0 10px 30px rgba(16, 29, 56, 0.08); 
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1); 
}

.package-card:hover { 
    transform: translateY(-8px); 
    border-color: #d5a52a; 
    box-shadow: 0 20px 40px rgba(16, 29, 56, 0.12), 0 0 15px rgba(213, 165, 42, 0.15); 
}

.package-card h3 { 
    margin: 0 0 12px; 
    color: #101d38; 
    font-family: 'Playfair Display', serif; 
    font-size: 23px; 
    font-weight: 700;
    line-height: 1.3; 
}

.package-price-wrap {
    display: flex;
    align-items: baseline;
    gap: 6px;
    min-height: 54px;
        margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f0f4f8;
}

.package-price-wrap .currency {
    color: #b88a2d;
    font-size: 16px;
    font-weight: 700;
}

.package-price { 
    color: #101d38; 
    font-size: 30px; 
    font-weight: 800; 
    letter-spacing: -0.5px;
}

.package-description { 
    min-height: 48px; 
    margin-bottom: 22px; 
    color: #5a6881; 
    font-size: 14px; 
    line-height: 1.6; 
}

.package-features { 
    margin: 0 0 28px; 
    padding: 0; 
    list-style: none; 
}

.package-features li { 
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px; 
    color: #334155; 
    font-size: 14px; 
}

.package-features li svg {
    flex-shrink: 0;
    width: 16px;
    height: 16px;
    color: #d5a52a;
}

/* Buttons */
.package-card .btn-wrap {
    margin-top: auto;
}

.package-card .btn-action { 
    display: block;
    width: 100%;
    text-align: center;
    padding: 14px 20px;
    border-radius: 12px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
}

.package-card .btn-action.outline { 
    background: #f8fafc; 
    color: #101d38; 
    border: 1.5px solid #cbd5e1; 
}

.package-card .btn-action.outline:hover { 
    background: #101d38; 
    color: #ffffff; 
    border-color: #101d38; 
    transform: translateY(-2px);
}

.package-card.hidden { 
    display: none !important; 
}

/* Slider Controls */
.packages-section .ws-carousel .ws-carousel-arrow { 
    background: #101d38; 
    color: #ffffff; 
    box-shadow: 0 6px 18px rgba(16, 29, 56, 0.15); 
    border: none;
    transition: all 0.3s ease;
}

.packages-section .ws-carousel .ws-carousel-arrow:hover {
    background: #d5a52a;
    color: #101d38;
}

.packages-section .ws-carousel-dots button { 
    background: #cbd5e1; 
}

.packages-section .ws-carousel-dots button.is-active { 
    background: #101d38; 
    width: 24px;
    border-radius: 6px;
}

/* NO RESULT */
.no-package-result { 
    display: none; 
    width: min(1120px, 92%); 
    margin: 30px auto 0; 
    padding: 30px; 
    text-align: center; 
    border-radius: 14px; 
    background: #f8fafc; 
    border: 1px dashed #cbd5e1;
    color: #64748b; 
    font-size: 16px;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .package-search-wrapper { justify-content: center; }
    .package-search-box { height: 56px; }
    .package-search-box .search-icon-wrap { width: 36px; height: 36px; }
    .package-search input { font-size: 15px; }
}

@media (max-width: 650px) {
    .package-card { min-height: auto; padding: 26px 20px; }
    .package-card h3 { min-height: 0; }
    .package-description { min-height: 0; }
    .package-features { min-height: 0; }
}

</style>


<!-- HERO -->
<?php
page_hero([
    'eyebrow' => 'PACKAGES',
    'title'   => 'Our',
    'accent'  => 'Packages',
    'text'    => 'Choose the perfect photography and videography package for your special moments.',
    'image'   => 'https://images.unsplash.com/photo-1591604466107-ec97de577aff?auto=format&fit=crop&w=1400&q=85',
    'alt'     => 'Bridal portrait',
    'tag'     => ['Choose', 'Book', 'Celebrate'],
    'primary' => ['Book Now', 'booking.php'],
    'secondary' => ['Contact Us', 'contact.php'],
]);
?>


<!-- SEARCH -->
<div class="package-search-wrapper">
    <div class="package-search">
        <label for="amountSearch">Search Package Amount</label>
        <div class="package-search-box" id="packageSearchBox">
            <span class="search-icon-wrap">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </span>
            <span class="search-prefix">Rs.</span>
            <input
                id="amountSearch"
                type="number"
                min="0"
                max="90000"
                step="1000"
                placeholder="Type your budget (max 90,000)"
                oninput="searchPackages()"
            >
            <button class="search-clear" id="searchClear" type="button" aria-label="Clear search">Clear</button>
        </div>
    </div>
</div>


<!-- PACKAGES SECTION -->
<section class="packages-section">
    <div class="package-carousel ws-carousel" id="packageCarousel" data-ws-carousel>
        <button class="ws-carousel-arrow ws-prev" type="button" aria-label="Previous">‹</button>

        <div class="ws-carousel-viewport">
            <div class="packages-grid ws-carousel-track" id="packagesGrid" data-total="<?= count($packages) ?>">

                <?php foreach ($displayPackages as $p): ?>

                    <article
                        class="package-card"
                        data-amount="<?= (int)$p['price'] ?>"
                        data-package-id="<?= (int)$p['id'] ?>"
                    >
                        <h3><?= htmlspecialchars($p['name']) ?></h3>

                        <div class="package-price-wrap">
                            <span class="currency">Rs.</span>
                            <span class="package-price"><?= number_format($p['price']) ?></span>
                        </div>

                        <div class="package-description">
                            <?= htmlspecialchars($p['description']) ?>
                        </div>

                        <ul class="package-features">
                            <?php foreach ($p['features'] as $feature): ?>
                                <li>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <span><?= htmlspecialchars($feature) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="btn-wrap">
                            <a
                                class="btn-action outline"
                                href="booking.php?item_type=package&amp;item_id=<?= (int)$p['id'] ?>"
                            >
                                Choose Package
                            </a>
                        </div>
                    </article>

                <?php endforeach; ?>

            </div>
        </div>

        <button class="ws-carousel-arrow ws-next" type="button" aria-label="Next">›</button>
        <div class="ws-carousel-dots" aria-label="Package slides"></div>
    </div>

    <!-- NO RESULT -->
    <div class="no-package-result" id="noPackageResult">
        No package found matching your specified budget.
    </div>
</section>


<script>
/* ============================================================
   PACKAGE SEARCH
   ============================================================ */
function searchPackages() {
    const input = document.getElementById('amountSearch');
    const grid = document.getElementById('packagesGrid');
    const noResult = document.getElementById('noPackageResult');

    let amount = parseFloat(input.value);

    if (input.value.trim() === '') {
        grid.classList.remove('search-mode');
        document.querySelectorAll('.package-card').forEach(function(card) {
            card.classList.remove('hidden');
        });
        noResult.style.display = 'none';
        grid.style.animation = 'none';
        void grid.offsetWidth;
        grid.style.animation = '';
        grid.style.transform = '';
        grid.dispatchEvent(new Event('ws-carousel-refresh'));
        return;
    }

    if (amount > 90000) {
        amount = 90000;
        input.value = 90000;
    }

    if (amount < 0) {
        amount = 0;
        input.value = 0;
    }

    grid.classList.add('search-mode');
    grid.style.animation = 'none';
    grid.style.transform = 'translateX(0)';

    let found = false;
    const shownPackages = new Set();

    document.querySelectorAll('.package-card').forEach(function(card) {
        const packageAmount = parseFloat(card.getAttribute('data-amount'));
        const packageId = card.getAttribute('data-package-id');

        if (packageAmount <= amount && !shownPackages.has(packageId)) {
            card.classList.remove('hidden');
            shownPackages.add(packageId);
            found = true;
        } else {
            card.classList.add('hidden');
        }
    });

    noResult.style.display = found ? 'none' : 'block';
    grid.dispatchEvent(new Event('ws-carousel-refresh'));
}

/* ============================================================
   SEARCH BAR HELPER
   ============================================================ */
(function () {
    const input = document.getElementById('amountSearch');
    const box = document.getElementById('packageSearchBox');
    const clearBtn = document.getElementById('searchClear');

    function sync() {
        box.classList.toggle('has-value', input.value !== '');
    }

    input.addEventListener('input', sync);

    clearBtn.addEventListener('click', function () {
        input.value = '';
        searchPackages();
        sync();
        input.focus();
    });

    sync();
})();
</script>

<?php include 'includes/footer.php'; ?>