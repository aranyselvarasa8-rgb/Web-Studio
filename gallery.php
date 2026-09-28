<?php

$page_title = 'Gallery | Web Studio';

include 'includes/header.php';
require_once 'includes/page-hero.php';

?>

<?php
page_hero([
    'eyebrow' => 'OUR WORK',
    'title'   => 'Our',
    'accent'  => 'Gallery',
    'text'    => 'Explore our favourite moments captured with care.',
    'image'   => 'https://images.unsplash.com/photo-1609220136736-443140cffec6?auto=format&fit=crop&w=1400&q=85',
    'alt'     => 'Family portrait',
    'tag'     => ['Weddings', 'Portraits', 'Nature'],
    'primary' => ['Book a Session', 'booking.php'],
    'secondary' => ['Our Services', 'services.php'],
]);
?>


<section class="section">

    <div class="container">

        <div class="ws-carousel" data-ws-carousel>
            <button class="ws-carousel-arrow ws-prev" type="button" aria-label="Previous">‹</button>
            <div class="ws-carousel-viewport">
                <div class="gallery-grid ws-carousel-track">

            <?php

            $items = [

                [
                    'Wedding',
                    'Wedding Moments',
                    'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1200&q=85'
                ],

                [
                    'Bridal',
                    'Bridal Portrait',
                    'https://images.unsplash.com/photo-1591604466107-ec97de577aff?auto=format&fit=crop&w=1200&q=85'
                ],

                [
                    'Pre-Wedding',
                    'Couple Session',
                    'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?auto=format&fit=crop&w=1200&q=85'
                ],

                [
                    'Baby',
                    'Baby Photography',
                    'https://images.unsplash.com/photo-1544126592-807ade215a0b?auto=format&fit=crop&w=1200&q=85'
                ],

                [
                    'Family',
                    'Family Portrait',
                    'https://images.unsplash.com/photo-1609220136736-443140cffec6?auto=format&fit=crop&w=1200&q=85'
                ],

                [
                    'Nature',
                    'Nature & Landscape',
                    'https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=1200&q=85'
                ]

            ];


            foreach ($items as $g):

            ?>

                <article class="gallery-item">

                    <div class="gallery-image">

                        <img
                            src="<?= htmlspecialchars($g[2]) ?>"
                            alt="<?= htmlspecialchars($g[1]) ?>"
                            loading="lazy"
                        >

                    </div>


                    <div class="gallery-info">

                        <span class="gallery-tag">
                            <?= htmlspecialchars($g[0]) ?>
                        </span>

                        <h3>
                            <?= htmlspecialchars($g[1]) ?>
                        </h3>

                        <small>
                            Web Studio
                        </small>

                    </div>

                </article>

            <?php

            endforeach;

            ?>

                </div>
            </div>
            <button class="ws-carousel-arrow ws-next" type="button" aria-label="Next">›</button>
            <div class="ws-carousel-dots" aria-label="Gallery slides"></div>
        </div>

    </div>

</section>


<?php

include 'includes/footer.php';

?>