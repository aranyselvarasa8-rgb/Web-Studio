<?php

$page_title = 'About | Web Studio';

include 'includes/header.php';
require_once 'includes/page-hero.php';

?>


<?php
page_hero([
    'eyebrow' => 'ABOUT US',
    'title'   => 'Creating memories',
    'accent'  => 'that last.',
    'text'    => 'We believe that every special moment deserves to be remembered. We capture the emotions, smiles, celebrations and beautiful details that make each occasion unique, creating photographs and videos that you can treasure for a lifetime.',
    'image'   => 'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1400&q=85',
    'alt'     => 'Wedding couple',
    'tag'     => ['Moments', 'Stories', 'Memories'],
    'primary' => ['Our Services', 'services.php'],
    'secondary' => ['Book Now', 'booking.php'],
]);
?>


<section class="section">

    <div class="container narrow">

        <h2>
            Welcome to Web Studio
        </h2>


        <p>
            At Web Studio, we believe that every special moment
            deserves to be remembered. We capture the emotions, smiles,
            celebrations and beautiful details that make each occasion
            unique, creating photographs and videos that you can treasure
            for a lifetime.
        </p>


        <p>
            From weddings and bridal sessions to pre-weddings, birthdays,
            special events and professional photo products, we provide
            creative and personalized photography and videography services.
            With attention to detail, modern creativity and friendly service,
            our goal is to make every experience memorable from start to finish.
        </p>


        <div class="stats">

            <div>
                <b>100+</b>
                <span>Events</span>
            </div>

            <div>
                <b>5+</b>
                <span>Years Experience</span>
            </div>

            <div>
                <b>24/7</b>
                <span>Booking Support</span>
            </div>

        </div>

    </div>

</section>


<?php include 'includes/footer.php'; ?>