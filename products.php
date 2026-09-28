<?php

$page_title = 'Products | Web Studio';

include 'includes/header.php';
require_once 'includes/page-hero.php';

?>


<?php
page_hero([
    'eyebrow' => 'MEMORY PRODUCTS',
    'title'   => 'Our',
    'accent'  => 'Products',
    'text'    => 'Premium keepsakes made to preserve your favourite memories.',
    'image'   => 'assets/images/products/premium-photo-album.jpg',
    'alt'     => 'Premium photo album',
    'tag'     => ['Frames', 'Albums', 'Keepsakes'],
    'primary' => ['View Cart', 'cart.php'],
    'secondary' => ['Contact Us', 'contact.php'],
]);
?>


<section class="section">

    <div class="container">

        <?php $flash = flash_get(); ?>

        <?php if ($flash): ?>
            <div class="alert <?= $flash['type'] === 'success' ? 'success-alert' : 'error-alert' ?>" style="margin-bottom:26px">
                <div>
                    <?= e($flash['message']) ?>
                    <?php if ($flash['type'] === 'success'): ?>
                        <a href="cart.php">View cart →</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="ws-carousel" data-ws-carousel>
            <button class="ws-carousel-arrow ws-prev" type="button" aria-label="Previous">‹</button>
            <div class="ws-carousel-viewport">
                <div class="product-grid ws-carousel-track">

            <?php

            $q = mysqli_query(
                $conn,
                "SELECT * FROM products WHERE status = 'active' ORDER BY id ASC LIMIT 6"
            );


            while ($p = mysqli_fetch_assoc($q)):

                if (($p['status'] ?? '') === 'inactive') {
                    continue;
                }

                $image = e(product_image_url($p));

            ?>

                <article class="product-card">

                    <div class="product-img">

                        <img
                            src="<?= $image ?>"
                            alt="<?= htmlspecialchars($p['name']) ?>"
                            loading="lazy"
                        >

                    </div>


                    <div class="product-body">

                        <span class="product-brand">
                            WEB STUDIO
                        </span>


                        <h3>
                            <?= htmlspecialchars($p['name']) ?>
                        </h3>


                        <p>
                            <?= htmlspecialchars($p['description']) ?>
                        </p>


                        <strong>
                            Rs. <?= number_format($p['price'], 2) ?>
                        </strong>


                        <p class="<?= $p['stock'] > 0 ? 'stock' : 'out' ?>">

                            <?= $p['stock'] > 0
                                ? 'Stock: ' . $p['stock']
                                : 'Out of Stock'
                            ?>

                        </p>


                        <?php if ($p['stock'] > 0): ?>

                            <div class="product-actions">

                                <form method="post" action="add-to-cart.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <input type="hidden" name="back" value="products.php">
                                    <button type="submit" class="btn gold small">Add to Cart</button>
                                </form>

                                <a
                                    class="btn ghost small"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    href="<?= e(whatsapp_link('Hello Web Studio, I am interested in ' . $p['name'])) ?>"
                                >
                                    WhatsApp Enquiry
                                </a>

                            </div>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endwhile; ?>

                </div>
            </div>
            <button class="ws-carousel-arrow ws-next" type="button" aria-label="Next">›</button>
            <div class="ws-carousel-dots" aria-label="Product slides"></div>
        </div>

    </div>

</section>


<?php

include 'includes/footer.php';

?>