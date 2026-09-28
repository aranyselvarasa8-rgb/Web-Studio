<?php

require_once __DIR__ . '/includes/auth.php';

$page_title = 'Contact Us | ' . SITE_NAME;

require __DIR__ . '/includes/header.php';

?>

<style>

/* =========================================
   CONTACT US PAGE
========================================= */

.contact-page {
    width: 100%;
    padding: 70px 0 60px;
    background: #ffffff;
}

.contact-container {
    width: 1180px;
    max-width: calc(100% - 40px);
    margin: auto;

    display: grid;
    grid-template-columns: 1fr 0.82fr;
    gap: 55px;
}


/* =========================================
   LEFT SIDE
========================================= */

.contact-left {
    min-width: 0;
}

.contact-eyebrow {
    display: flex;
    align-items: center;
    gap: 13px;

    color: #c99618;

    font-size: 14px;
    font-weight: 700;

    letter-spacing: 3px;

    margin-bottom: 17px;
}

.contact-eyebrow span {
    width: 42px;
    height: 3px;

    background: #c99618;

    display: inline-block;
}

.contact-left h1 {
    color: #0b2342;

    font-size: 38px;
    line-height: 1.2;

    margin: 0 0 12px;

    font-weight: 700;
}

.contact-description {
    color: #405a78;

    font-size: 16px;
    line-height: 1.7;

    margin: 0 0 28px;
}


/* =========================================
   CONTACT DETAILS
========================================= */

.contact-details {
    display: grid;

    grid-template-columns: 1fr 1fr;

    column-gap: 45px;
    row-gap: 18px;

    margin-bottom: 25px;
}

.detail-box {
    display: flex;

    align-items: flex-start;

    gap: 15px;

    min-height: 75px;
}

.detail-icon {
    width: 44px;
    height: 44px;

    min-width: 44px;

    border-radius: 50%;

    background: #d5a52a;

    color: #0b2342;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 20px;

    box-shadow: 0 3px 8px rgba(0,0,0,0.12);
}

.detail-box h3 {
    margin: 0 0 5px;

    color: #0b2342;

    font-size: 16px;

    font-weight: 700;
}

.detail-box a,
.detail-box p {
    margin: 0;

    color: #405a78;

    font-size: 15px;

    line-height: 1.55;

    text-decoration: none;
}

.detail-box a:hover {
    color: #c99618;
}


/* =========================================
   GOOGLE MAP
========================================= */

.contact-map {
    width: 100%;

    height: 280px;

    overflow: hidden;

    border-radius: 8px;

    margin-top: 5px;

    border: 1px solid #e1e1e1;
}

.contact-map iframe {
    display: block;

    width: 100%;
    height: 100%;

    border: 0;
}


/* =========================================
   RIGHT QUICK ENQUIRY
========================================= */

.contact-right {
    position: relative;

    overflow: hidden;

    background: #092443;

    border-radius: 9px;

    padding: 38px 38px 42px;

    min-height: 590px;

    box-sizing: border-box;
}

.quick-eyebrow {
    color: #d5a52a;

    font-size: 13px;

    font-weight: 700;

    letter-spacing: 3px;

    margin-bottom: 12px;
}

.contact-right h2 {
    color: #ffffff;

    font-size: 32px;

    margin: 0 0 10px;

    line-height: 1.2;
}

.quick-text {
    color: #ffffff;

    font-size: 16px;

    margin: 0 0 25px;

    line-height: 1.6;
}


/* =========================================
   QUICK BUTTONS
========================================= */

.quick-buttons {
    display: flex;

    gap: 12px;

    margin-bottom: 32px;
}

.quick-buttons a {
    min-height: 55px;

    padding: 0 22px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    border-radius: 5px;

    text-decoration: none;

    font-size: 15px;

    font-weight: 700;

    box-sizing: border-box;

    transition: all 0.25s ease;
}


/* WhatsApp */

.whatsapp-btn {
    background: #d9ad2c;

    color: #071f3b;

    border: 1px solid #d9ad2c;

    flex: 1;
}

.whatsapp-btn:hover {
    background: #e7bd42;
}


/* Call */

.call-btn {
    color: #ffffff;

    background: transparent;

    border: 1px solid #d9ad2c;

    flex: 1;
}

.call-btn:hover {
    background: #d9ad2c;

    color: #071f3b;
}


/* =========================================
   FORM
========================================= */

.quick-form {
    position: relative;

    z-index: 2;
}

.quick-form input,
.quick-form textarea {
    width: 100%;

    box-sizing: border-box;

    background: #ffffff;

    border: 1px solid #e3e6eb;

    border-radius: 7px;

    color: #0b2342;

    font-family: inherit;

    font-size: 15px;

    outline: none;

    transition: border-color 0.2s ease;
}

.quick-form input {
    height: 54px;

    padding: 0 17px;

    margin-bottom: 12px;
}

.quick-form textarea {
    height: 115px;

    padding: 16px 17px;

    resize: vertical;

    margin-bottom: 16px;
}

.quick-form input:focus,
.quick-form textarea:focus {
    border-color: #d5a52a;
}

.quick-form input::placeholder,
.quick-form textarea::placeholder {
    color: #718096;
}

.form-row {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 12px;
}


/* =========================================
   SUBMIT BUTTON
========================================= */

.submit-btn {
    min-width: 185px;

    height: 55px;

    border: 0;

    border-radius: 5px;

    background: #d9ad2c;

    color: #071f3b;

    font-size: 15px;

    font-weight: 700;

    cursor: pointer;

    transition: all 0.25s ease;
}

.submit-btn:hover {
    background: #e7bd42;

    transform: translateY(-1px);
}


/* =========================================
   CAMERA DECORATION
========================================= */

.camera-decoration {
    position: absolute;

    right: -15px;

    bottom: -25px;

    font-size: 115px;

    opacity: 0.16;

    transform: rotate(-8deg);

    pointer-events: none;
}


/* =========================================
   HEADER LOGO CIRCLE
========================================= */

.site-logo {
    width: 82px;
    height: 82px;

    object-fit: contain;

    border-radius: 50%;

    background: #ffffff;

    border: 2px solid #d5a52a;

    padding: 7px;

    box-sizing: border-box;
}


/* =========================================
   RESPONSIVE
========================================= */

@media (max-width: 950px) {

    .contact-container {
        grid-template-columns: 1fr;

        gap: 40px;
    }

    .contact-right {
        min-height: auto;
    }

}

@media (max-width: 650px) {

    .contact-page {
        padding: 45px 0;
    }

    .contact-container {
        max-width: calc(100% - 28px);
    }

    .contact-left h1 {
        font-size: 31px;
    }

    .contact-details {
        grid-template-columns: 1fr;

        row-gap: 15px;
    }

    .contact-right {
        padding: 30px 22px 35px;
    }

    .contact-right h2 {
        font-size: 29px;
    }

    .quick-buttons {
        flex-direction: column;
    }

    .quick-buttons a {
        width: 100%;
    }

    .form-row {
        grid-template-columns: 1fr;

        gap: 0;
    }

    .contact-map {
        height: 300px;
    }

}

</style>


<!-- =========================================
     CONTACT PAGE
========================================= -->

<section class="contact-page">

    <div class="contact-container">


        <!-- =====================================
             LEFT SIDE
        ====================================== -->

        <div class="contact-left">


            <!-- Eyebrow -->

            <div class="contact-eyebrow">

                <span></span>

                GET IN TOUCH

            </div>


            <!-- Main Heading -->

            <h1>
                We’d love to hear from you
            </h1>


            <!-- Description -->

            <p class="contact-description">

                Have a wedding, event or product enquiry?
                Contact Web Studio.

            </p>


            <!-- Contact Details -->

            <div class="contact-details">


                <!-- PHONE -->

                <div class="detail-box">

                    <div class="detail-icon">
                        ☎
                    </div>

                    <div>

                        <h3>
                            Phone
                        </h3>

                        <a href="tel:0763551264">

                            0763551264

                        </a>

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="detail-box">

                    <div class="detail-icon">
                        ✉
                    </div>

                    <div>

                        <h3>
                            Email
                        </h3>

                        <a href="mailto:jeevanstudiojaffna@gmail.com">

                            jeevanstudiojaffna@gmail.com

                        </a>

                    </div>

                </div>


                <!-- LOCATION -->

                <div class="detail-box">

                    <div class="detail-icon">
                        📍
                    </div>

                    <div>

                        <h3>
                            Location
                        </h3>

                        <p>

                            305, Clock Tower Rd,
                            Jaffna 40000,
                            Sri Lanka

                        </p>

                    </div>

                </div>


                <!-- OPENING HOURS -->

                <div class="detail-box">

                    <div class="detail-icon">
                        ◷
                    </div>

                    <div>

                        <h3>
                            Opening Hours
                        </h3>

                        <p>
                            9:00 AM – 7:30 PM
                        </p>

                    </div>

                </div>


            </div>


            <!-- =================================
                 GOOGLE LIVE MAP
            ================================== -->

            <div class="contact-map">

                <iframe

                    src="https://www.google.com/maps?q=305%20Clock%20Tower%20Rd%20Jaffna%2040000%20Sri%20Lanka&output=embed"

                    width="100%"

                    height="280"

                    style="border:0;"

                    allowfullscreen=""

                    loading="lazy"

                    referrerpolicy="no-referrer-when-downgrade">

                </iframe>

            </div>


        </div>


        <!-- =====================================
             RIGHT SIDE
        ====================================== -->

        <div class="contact-right">


            <div class="quick-eyebrow">

                WEB STUDIO

            </div>


            <h2>

                Quick Enquiry

            </h2>


            <p class="quick-text">

                Message us directly on WhatsApp for fast replies.

            </p>


            <!-- =================================
                 WHATSAPP + CALL
            ================================== -->

            <div class="quick-buttons">


                <!-- WHATSAPP -->

                <a

                    href="https://wa.me/94763551264?text=Hello%20Web%20Studio%2C%20I%20would%20like%20to%20make%20an%20enquiry."

                    target="_blank"

                    class="whatsapp-btn">

                    ☎ &nbsp; WhatsApp Us

                </a>


                <!-- CALL -->

                <a

                    href="tel:0763551264"

                    class="call-btn">

                    ☎ &nbsp; Call 0763551264

                </a>


            </div>


            <!-- =================================
                 ENQUIRY FORM
            ================================== -->

            <form

                action="contact-submit.php"

                method="POST"

                class="quick-form">


                <!-- NAME -->

                <input

                    type="text"

                    name="name"

                    placeholder="Name"

                    required>


                <!-- EMAIL + PHONE -->

                <div class="form-row">


                    <input

                        type="email"

                        name="email"

                        placeholder="E-Mail"

                        required>


                    <input

                        type="tel"

                        name="phone"

                        placeholder="Phone Number"

                        required>


                </div>


                <!-- MESSAGE -->

                <textarea

                    name="message"

                    placeholder="Your message Here"

                    required></textarea>


                <!-- SUBMIT -->

                <button

                    type="submit"

                    class="submit-btn">

                    ➤ &nbsp; Submit Now &nbsp; ➤

                </button>


            </form>


            <!-- CAMERA -->

            <div class="camera-decoration">

                📷

            </div>


        </div>


    </div>

</section>


<?php

require __DIR__ . '/includes/footer.php';

?>