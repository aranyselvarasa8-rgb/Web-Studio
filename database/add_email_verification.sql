USE web_studio;

/* Run this once. Adds email verification support to the users table. */

ALTER TABLE users
    ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN verification_token VARCHAR(64) DEFAULT NULL AFTER email_verified,
    ADD COLUMN verification_expires DATETIME DEFAULT NULL AFTER verification_token;

/* If you already have existing users and don't want to force them to
   re-verify, uncomment the line below after running the ALTER above: */
-- UPDATE users SET email_verified = 1;
