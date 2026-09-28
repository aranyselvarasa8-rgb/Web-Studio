<footer class="footer">

    <div class="container footer-grid">

        <div>

            <img
                class="footer-logo"
                src="assets/images/logo-light.png"
                alt="Web Studio"
            >

            <p>
                Professional photography and videography for weddings,
                events and every beautiful moment.
            </p>

        </div>


        <div>

            <h4>Quick Links</h4>

            <a href="services.php">Services</a>
            <a href="gallery.php">Gallery</a>
            <a href="packages.php">Packages</a>
            <a href="products.php">Products</a>

        </div>


        <div>

            <h4>Contact</h4>

            <p>305, Clock Tower Rd, Jaffna 40000, Sri Lanka</p>
            <p>0763551264</p>
            <p>webstudiojaffna@gmail.com</p>

        </div>

    </div>


    <div class="copyright">
        © <?= date('Y') ?> Web Studio. All rights reserved.
    </div>

</footer>


<a
    class="whatsapp-float"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Chat with Web Studio on WhatsApp"
    href="<?= e(whatsapp_link('Hello Web Studio, I would like to make an enquiry.')) ?>"
>
    <span class="whatsapp-icon" aria-hidden="true">
        <svg viewBox="0 0 32 32" role="img" aria-hidden="true">
            <path fill="currentColor" d="M19.11 17.19c-.27-.14-1.58-.78-1.83-.87-.25-.09-.43-.14-.61.14-.18.27-.7.87-.86 1.05-.16.18-.32.2-.59.07-.27-.14-1.12-.41-2.14-1.32-.79-.7-1.32-1.56-1.47-1.83-.16-.27-.02-.42.12-.56.12-.12.27-.32.41-.48.14-.16.18-.27.27-.45.09-.18.05-.34-.02-.48-.07-.14-.61-1.47-.84-2.02-.22-.53-.45-.46-.61-.47h-.52c-.18 0-.48.07-.73.34-.25.27-.95.93-.95 2.27s.98 2.63 1.11 2.81c.14.18 1.92 2.93 4.66 4.11.65.28 1.16.45 1.55.57.65.21 1.24.18 1.7.11.52-.08 1.58-.65 1.8-1.28.23-.63.23-1.17.16-1.28-.07-.11-.25-.18-.52-.32z"/>
            <path fill="currentColor" d="M16.01 3.2c-7.06 0-12.8 5.73-12.8 12.78 0 2.25.59 4.45 1.71 6.38L3.1 28.8l6.59-1.73a12.77 12.77 0 0 0 6.32 1.67h.01c7.05 0 12.78-5.74 12.78-12.79S23.06 3.2 16.01 3.2zm0 23.37h-.01a10.6 10.6 0 0 1-5.4-1.48l-.39-.23-3.91 1.03 1.04-3.81-.25-.39a10.58 10.58 0 1 1 8.92 4.88z"/>
        </svg>
    </span>
    <span class="whatsapp-label">WhatsApp</span>
</a>


<script src="assets/js/script.js"></script>

</body>
</html>