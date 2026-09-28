<?php

$page_title = 'Services | Web Studio';

require_once 'includes/config.php';

include 'includes/header.php';
require_once 'includes/page-hero.php';

?>


<?php
page_hero([
    'eyebrow' => 'OUR SERVICES',
    'title'   => 'Photography &',
    'accent'  => 'videography',
    'text'    => 'Professional photography and cinematic services for your most important moments.',
    'image'   => 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?auto=format&fit=crop&w=1400&q=85',
    'alt'     => 'Pre-wedding couple session',
    'tag'     => ['Photo', 'Video', 'Cinematic'],
    'primary' => ['Book Now', 'booking.php'],
    'secondary' => ['View Packages', 'packages.php'],
]);
?>


<section class="section">

    <div class="container">

        <div class="ws-carousel" data-ws-carousel>
            <button class="ws-carousel-arrow ws-prev" type="button" aria-label="Previous">‹</button>
            <div class="ws-carousel-viewport">
                <div class="cards services ws-carousel-track">

            <?php

            $q = mysqli_query(
                $conn,
                "SELECT * FROM services
                 WHERE status='active'
                 ORDER BY id"
            );


            if (mysqli_num_rows($q)):

                while ($s = mysqli_fetch_assoc($q)):

            ?>

                    <article class="card">


                        <h3>
                            <?= htmlspecialchars($s['name']) ?>
                        </h3>


                        <p>
                            <?= htmlspecialchars($s['description']) ?>
                        </p>


                        <strong>
                            Rs. <?= number_format($s['price'], 2) ?>
                        </strong>


                        <a
                            class="text-link"
                            href="booking.php?item_type=service&amp;item_id=<?= (int) $s['id'] ?>"
                        >
                            Book Now →
                        </a>

                    </article>

            <?php

                endwhile;

            else:

            ?>

                <p>
                    No services are available yet.
                </p>

            <?php endif; ?>

                </div>
            </div>
            <button class="ws-carousel-arrow ws-next" type="button" aria-label="Next">›</button>
            <div class="ws-carousel-dots" aria-label="Service slides"></div>
        </div>

    </div>

</section>


<?php

include 'includes/footer.php';

?>