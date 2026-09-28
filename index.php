<?php

$page_title = 'Web Studio | Photography & Videography';

include 'includes/header.php';

?>

<!-- =========================================
     HERO SECTION
========================================= -->

<section class="hero">

    <div class="container hero-content">

        <span class="eyebrow">
            PHOTOGRAPHY • VIDEOGRAPHY • PRINTING
        </span>

        <h1>
            We capture your<br>
            <span>beautiful moments.</span>
        </h1>

        <p>
            Elegant wedding photography, cinematic films and premium
            photo products created with care.
        </p>


        <div class="hero-buttons">

            <a
                class="btn gold"
                href="admin/index.php"
            >
                Admin Panel <span>›</span>
            </a>


            <a
                class="btn outline"
                href="gallery.php"
            >
                View Gallery
            </a>

        </div>

    </div>

</section>


<!-- =========================================
     WHAT WE DO
========================================= -->

<section class="section">

    <div class="container">

        <div class="section-head">

            <span class="eyebrow">
                WHAT WE DO
            </span>

            <h2>
                Photography that tells your story
            </h2>

            <p>
                From the first smile to the final celebration,
                we preserve memories you can keep forever.
            </p>

        </div>


        <div class="cards">

            <!-- CARD 1 -->

            <article class="card">

                <div class="icon">
                    ♡
                </div>

                <h3>
                    Wedding Photography
                </h3>

                <p>
                    Natural, elegant and emotional wedding memories.
                </p>

            </article>


            <!-- CARD 2 -->

            <article class="card">

                <div class="icon">
                    ◉
                </div>

                <h3>
                    Cinematic Videography
                </h3>

                <p>
                    Beautiful films that bring your special day back to life.
                </p>

            </article>


            <!-- CARD 3 -->

            <article class="card">

                <div class="icon">
                    ◇
                </div>

                <h3>
                    Premium Products
                </h3>

                <p>
                    Crystal, marble frames and albums made for your memories.
                </p>

            </article>

        </div>

    </div>

</section>


<!-- =========================================
     WHY WEB STUDIO
========================================= -->

<section class="dark-section">

    <div class="container split">

        <div>

            <span class="eyebrow">
                WHY WEB STUDIO
            </span>

            <h2>
                Timeless images. Genuine emotions.
            </h2>

            <p>
                We focus on real moments, beautiful composition and
                a smooth experience from booking to delivery.
            </p>

        </div>


        <a
            class="btn gold"
            href="contact.php"
        >
            Let's Talk
        </a>

    </div>

</section>


<!-- =========================================
     FEATURED PRODUCTS
========================================= -->

<section class="section">

    <div class="container">

        <div class="section-head">

            <span class="eyebrow">
                FEATURED
            </span>

            <h2>
                Our popular products
            </h2>

        </div>


        <div
            class="ws-carousel"
            data-ws-carousel
        >

            <!-- Previous -->

            <button
                class="ws-carousel-arrow ws-prev"
                type="button"
                aria-label="Previous"
            >
                ‹
            </button>


            <!-- Viewport -->

            <div class="ws-carousel-viewport">

                <div class="product-grid ws-carousel-track">


                    <?php

                    /*
                    |--------------------------------------------------------------------------
                    | Default product images
                    |--------------------------------------------------------------------------
                    */

                    $homeProductImages = [

                        'Premium Photo Album' =>
                            'assets/images/products/premium-photo-album.jpg',

                        'Marble Frame' =>
                            'assets/images/products/marble-frame.jpg',

                        '3D Crystal Frame' =>
                            'assets/images/products/3d-crystal-frame.jpg',

                        '3D Crystal Cutting Frame' =>
                            'assets/images/products/3d-crystal-frame.jpg',

                        'Canvas Photo Print' =>
                            'assets/images/products/canvas-photo-print.jpg',

                        'Custom Photo Calendar' =>
                            'assets/images/products/custom-photo-calendar.jpg'

                    ];


                    /*
                    |--------------------------------------------------------------------------
                    | Get products
                    |--------------------------------------------------------------------------
                    */

                    $q = mysqli_query(
                        $conn,
                        "SELECT * FROM products ORDER BY id DESC LIMIT 6"
                    );


                    if ($q && mysqli_num_rows($q) > 0):


                        while ($p = mysqli_fetch_assoc($q)):


                            /*
                            |--------------------------------------------------------------------------
                            | Product name
                            |--------------------------------------------------------------------------
                            */

                            $productName = trim(
                                $p['name'] ?? ''
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Database image
                            |--------------------------------------------------------------------------
                            */

                            $databaseImage = trim(
                                $p['image'] ?? ''
                            );


                            $homeImage = '';


                            /*
                            |--------------------------------------------------------------------------
                            | Try database image paths
                            |--------------------------------------------------------------------------
                            */

                            if ($databaseImage !== '') {

                                $possibleImages = [

                                    'assets/images/' . $databaseImage,

                                    'assets/images/products/' . $databaseImage,

                                    $databaseImage

                                ];


                                foreach ($possibleImages as $possibleImage) {

                                    $fullPath =
                                        __DIR__ . '/' . $possibleImage;


                                    if (file_exists($fullPath)) {

                                        $homeImage = $possibleImage;

                                        break;

                                    }

                                }

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | If database image is not found,
                            | use product name mapping
                            |--------------------------------------------------------------------------
                            */

                            if ($homeImage === '') {

                                if (
                                    isset(
                                        $homeProductImages[$productName]
                                    )
                                ) {

                                    $mappedImage =
                                        $homeProductImages[$productName];


                                    if (
                                        file_exists(
                                            __DIR__ . '/' . $mappedImage
                                        )
                                    ) {

                                        $homeImage = $mappedImage;

                                    }

                                }

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Final fallback
                            |--------------------------------------------------------------------------
                            */

                            if ($homeImage === '') {

                                $homeImage =
                                    'assets/images/products/premium-photo-album.jpg';

                            }

                    ?>


                        <!-- =====================================
                             PRODUCT CARD
                        ====================================== -->

                        <div class="product-card">


                            <!-- Product image -->

                            <div class="product-img">

                                <img
                                    src="<?= htmlspecialchars($homeImage) ?>"
                                    alt="<?= htmlspecialchars($productName) ?>"
                                    loading="lazy"
                                    onerror="this.style.display='none';"
                                >

                            </div>


                            <!-- Product name only -->

                            <div class="product-body">

                                <h3>
                                    <?= htmlspecialchars($productName) ?>
                                </h3>

                            </div>


                        </div>


                    <?php

                        endwhile;


                    else:

                    ?>

                        <!-- No products -->

                        <div class="product-card">

                            <div class="product-img">

                                <img
                                    src="assets/images/products/premium-photo-album.jpg"
                                    alt="Premium Photo Album"
                                >

                            </div>


                            <div class="product-body">

                                <h3>
                                    Premium Photo Album
                                </h3>

                            </div>

                        </div>

                    <?php

                    endif;

                    ?>


                </div>

            </div>


            <!-- Next -->

            <button
                class="ws-carousel-arrow ws-next"
                type="button"
                aria-label="Next"
            >
                ›
            </button>


            <!-- Dots -->

            <div
                class="ws-carousel-dots"
                aria-label="Product slides"
            ></div>

        </div>

    </div>

</section>


<?php

include 'includes/footer.php';

?>