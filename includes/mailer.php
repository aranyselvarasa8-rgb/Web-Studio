<?php

/**
 * Minimal SMTP mailer (no Composer / PHPMailer needed).
 * Works with Gmail SMTP (smtp.gmail.com:587, STARTTLS, AUTH LOGIN)
 * using a Gmail "App Password" — see includes/config.php for setup notes.
 *
 * Using real SMTP with authentication (instead of PHP's mail()) is the
 * single biggest factor in whether your email lands in Inbox vs Spam.
 */

class SmtpMailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private $socket;
    private array $log = [];

    public function __construct(
        string $host,
        int $port,
        string $username,
        string $password,
        string $fromEmail,
        string $fromName
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    public function getLog(): string
    {
        return implode("\n", $this->log);
    }

    /**
     * Send an HTML email. Returns true on success, false on failure.
     * On failure, call getLog() to see exactly what the SMTP server said.
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        try {
            $this->connect();
            $this->hello();
            $this->startTls();
            $this->hello(); // must re-issue EHLO after STARTTLS
            $this->auth();
            $this->mailFrom();
            $this->rcptTo($toEmail);
            $this->data($toEmail, $toName, $subject, $htmlBody);
            $this->quit();
            return true;
        } catch (\Throwable $e) {
            $this->log[] = 'ERROR: ' . $e->getMessage();
            if ($this->socket) {
                @fclose($this->socket);
            }
            return false;
        }
    }

    private function connect(): void
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 15);
        if (!$this->socket) {
            throw new \Exception("Could not connect to {$this->host}:{$this->port} - $errstr");
        }
        $this->readResponse(220);
    }

    private function hello(): void
    {
        $this->write("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $this->readResponse(250);
    }

    private function startTls(): void
    {
        $this->write('STARTTLS');
        $this->readResponse(220);

        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new \Exception('TLS negotiation failed');
        }
    }

    private function auth(): void
    {
        $this->write('AUTH LOGIN');
        $this->readResponse(334);

        $this->write(base64_encode($this->username));
        $this->readResponse(334);

        $this->write(base64_encode($this->password));
        $this->readResponse(235); // 235 = authentication successful
    }

    private function mailFrom(): void
    {
        $this->write("MAIL FROM:<{$this->fromEmail}>");
        $this->readResponse(250);
    }

    private function rcptTo(string $toEmail): void
    {
        $this->write("RCPT TO:<{$toEmail}>");
        $this->readResponse(250);
    }

    private function data(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        $this->write('DATA');
        $this->readResponse(354);

        $boundary = 'bnd_' . bin2hex(random_bytes(8));
        $date = date('r');
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers = [];
        $headers[] = "Date: $date";
        $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        $headers[] = "To: {$toName} <{$toEmail}>";
        $headers[] = "Subject: {$encodedSubject}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: base64";

        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody));

        // Escape lines that start with a lone "." per SMTP rules
        $message = preg_replace('/\n\./', "\n..", $message);

        $this->write($message . "\r\n.");
        $this->readResponse(250);
    }

    private function quit(): void
    {
        $this->write('QUIT');
        @fclose($this->socket);
    }

    private function write(string $line): void
    {
        fwrite($this->socket, $line . "\r\n");
        $this->log[] = '>>> ' . substr($line, 0, 120);
    }

    private function readResponse(int $expectedCode): string
    {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            $this->log[] = '<<< ' . trim($line);
            // Multi-line SMTP responses use "code-" ; final line uses "code "
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new \Exception("Expected $expectedCode, got: " . trim($response));
        }

        return $response;
    }
}

/**
 * Convenience wrapper: send using the Gmail credentials from config.php.
 * Returns true/false. On failure it writes the SMTP conversation to
 * logs/mail-errors.log so you can see exactly what went wrong.
 */
function send_verification_email(string $toEmail, string $toName, string $verifyLink): bool
{
    $mailer = new SmtpMailer(
        'smtp.gmail.com',
        587,
        GMAIL_SMTP_USER,
        GMAIL_SMTP_APP_PASSWORD,
        GMAIL_SMTP_USER,
        SITE_NAME
    );

    $subject = 'Verify your email - ' . SITE_NAME;

    $html = '
    <div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;padding:24px;border:1px solid #eee;border-radius:8px">
        <h2 style="color:#111">Verify your email</h2>
        <p>Hi ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>
        <p>Thanks for creating an account with ' . htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') . '. Please confirm this is your email address by clicking the button below.</p>
        <p style="text-align:center;margin:32px 0">
            <a href="' . htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8') . '"
               style="background:#111;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;display:inline-block">
               Verify Email Address
            </a>
        </p>
        <p style="color:#666;font-size:13px">If the button does not work, copy and paste this link into your browser:<br>' . htmlspecialchars($verifyLink, ENT_QUOTES, 'UTF-8') . '</p>
        <p style="color:#666;font-size:13px">This link expires in 24 hours. If you did not create this account, you can ignore this email.</p>
    </div>';

    $ok = $mailer->send($toEmail, $toName, $subject, $html);

    if (!$ok) {
        $logDir = __DIR__ . '/../logs';
        if (is_dir($logDir)) {
            @file_put_contents(
                $logDir . '/mail-errors.log',
                '[' . date('Y-m-d H:i:s') . "] Failed sending to $toEmail\n" . $mailer->getLog() . "\n\n",
                FILE_APPEND
            );
        }
    }

    return $ok;
}
