<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

use Exception;

use PHPMailer\PHPMailer\PHPMailer;
use QuickDRY\Interfaces\IEmailAttachment;

/**
 * Class Mailer
 */
class Mailer extends strongType
{
    public ?string $message = null;
    public ?string $subject = null;
    public ?string $to_email = null;
    public ?string $to_name = null;
    /**
     * Comma-separated list of additional recipients to Cc on the SAME message as to_email.
     * When set (even to an empty string), Send() sends ONE message with to_email in the To:
     * field and these addresses in the Cc: field, instead of the default behavior of sending
     * a separate individual message per address found in to_email.
     */
    public ?string $cc_emails = null;
    public ?bool $is_sent = null;
    public ?string $sent_at = null;
    public ?string $log = null;
    public ?string $headers = null;
    public ?string $from_email = null;
    public ?string $from_name = null;
    public ?array $embedded_images = null;

    public ?PHPMailer $mail = null;

    /**
     *
     * @param string $to_email
     * @param string $to_name
     * @param string $subject
     * @param string $message
     * @param array|null $attachments
     * @param string|null $from_email
     * @param string|null $from_name
     * @param array|null $embedded_images
     * @return Mailer
     */
    public static function Queue(
        string  $to_email,
        string  $to_name,
        string  $subject,
        string  $message,
        ?array  $attachments = null,
        ?string $from_email = null,
        ?string $from_name = null,
        ?array  $embedded_images = null
    ): Mailer
    {
        $t = new self();
        $t->to_email = $to_email;
        $t->to_name = $to_name;
        $t->from_email = $from_email;
        $t->from_name = $from_name;
        $t->subject = $subject;
        $t->message = $message;
        $t->log = null;
        $t->headers = json_encode($attachments);
        $t->embedded_images = $embedded_images;

        return $t;
    }

    /**
     * Opt-in variant of Queue() for sending ONE message to multiple recipients
     * (to_email in the To: field, cc_emails in the Cc: field) instead of the
     * default behavior, which sends a separate individual message per address.
     *
     * @param string $to_email Primary recipient (To:)
     * @param string $to_name
     * @param string|null $cc_emails Comma-separated list of additional recipients (Cc:). Pass '' for none.
     * @param string $subject
     * @param string $message
     * @param array|null $attachments
     * @param string|null $from_email
     * @param string|null $from_name
     * @param array|null $embedded_images
     * @return Mailer
     */
    public static function QueueWithCC(
        string  $to_email,
        string  $to_name,
        ?string $cc_emails,
        string  $subject,
        string  $message,
        ?array  $attachments = null,
        ?string $from_email = null,
        ?string $from_name = null,
        ?array  $embedded_images = null
    ): Mailer
    {
        $t = self::Queue($to_email, $to_name, $subject, $message, $attachments, $from_email, $from_name, $embedded_images);
        $t->cc_emails = $cc_emails ?? '';

        return $t;
    }

    /**
     * Returns 1 on success, 0 on failure (reason in $this->log),
     * -1 if SMTP is switched off, -2 if debug mode has no SMTP_DEBUG_EMAIL.
     *
     * @param bool $debug
     * @param bool $smtp_output
     * @return int
     * @throws Exception when required SMTP constants are missing
     */
    public function Send(bool $debug = false, bool $smtp_output = false): int
    {
        if (defined('SMTP_ON') && SMTP_ON == 0) {
            return -1;
        }

        if (!defined('SMTP_FROM_EMAIL') || !defined('SMTP_FROM_NAME')) {
            throw new Exception('SMTP_FROM_EMAIL or SMTP_FROM_NAME not defined');
        }
        if (!defined('SMTP_HOST')) {
            throw new Exception('SMTP_HOST undefined');
        }

        // Local copies, so debug mode never overwrites the queued recipient or subject
        $to_list = (string)$this->to_email;
        $cc_list = $this->cc_emails;
        $subject = (string)$this->subject;

        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            if (!defined('SMTP_DEBUG_EMAIL')) {
                return -2;
            }
            $to_list = SMTP_DEBUG_EMAIL;
            $cc_list = null; // never leak real Cc recipients in debug mode
            $subject = 'TEST EMAIL: ' . $subject;
        }

        $this->log = null;

        $to_emails = array_values(array_filter(Strings::SplitEmails($to_list)));
        if (!$to_emails) {
            $this->log = 'No recipients';
            return 0;
        }

        // A display name only makes sense when there is exactly one primary recipient
        $to_name = count($to_emails) === 1 ? (string)$this->to_name : '';

        // Opt-in single-message mode: one send, all "to" addresses in To:, cc_emails in Cc:
        if ($cc_list !== null) {
            $mail = $this->buildMailer($smtp_output);
            if (!$mail) {
                return 0;
            }

            if (array_any($to_emails, fn($to) => !$this->addRecipient($mail, 'To', $to, $to_name))) {
                return 0;
            }

            if (array_any(array_filter(Strings::SplitEmails($cc_list)), fn($cc) => !$this->addRecipient($mail, 'Cc', $cc))) {
                return 0;
            }

            if (!$this->attachAndSend($mail, $subject, $debug)) {
                return 0;
            }

            $this->markSent();
            return 1;
        }

        // Default (legacy) mode: one separate message per address in to_email
        $delivered = [];
        foreach ($to_emails as $to) {
            $mail = $this->buildMailer($smtp_output);

            if (!$mail
                || !$this->addRecipient($mail, 'To', $to, $to_name)
                || !$this->attachAndSend($mail, $subject, $debug)
            ) {
                if ($delivered) {
                    // Some copies already went out; record which, so a retry can be handled deliberately
                    $this->log = 'Partial send, already delivered to: ' . implode(', ', $delivered)
                        . '. Error: ' . $this->log;
                }
                return 0;
            }

            $delivered[] = $to;
        }

        $this->markSent();
        return 1;
    }

    /**
     * @return void
     */
    private function markSent(): void
    {
        $this->is_sent = true;
        $this->sent_at = Dates::Timestamp(time());
    }

    /**
     * @param PHPMailer $mail
     * @param string $kind 'To' or 'Cc'
     * @param string $email
     * @param string $name
     * @return bool
     */
    private function addRecipient(PHPMailer $mail, string $kind, string $email, string $name = ''): bool
    {
        try {
            if ($kind === 'Cc') {
                $mail->addCC($email, $name);
            } else {
                $mail->addAddress($email, $name);
            }
            return true;
        } catch (Exception $e) {
            $this->log = 'Mailer add ' . $kind . ' (' . $email . '): ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Builds and configures a fresh PHPMailer instance shared by both send modes.
     * Returns null (with the reason in $this->log) if the From address is invalid.
     *
     * @param bool $smtp_output
     * @return PHPMailer|null
     * @throws Exception when SMTP auth constants are incomplete
     */
    private function buildMailer(bool $smtp_output): ?PHPMailer
    {
        $mail = new PHPMailer(true); // throw on errors instead of silently returning false

        $mail->Mailer = 'smtp';
        $mail->Host = SMTP_HOST;
        $mail->Port = defined('SMTP_PORT') ? (int)SMTP_PORT : 25;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->SMTPDebug = $smtp_output ? 1 : 0;

        try {
            $mail->setFrom($this->from_email ?? SMTP_FROM_EMAIL, $this->from_name ?? SMTP_FROM_NAME);
        } catch (Exception $e) {
            $this->log = 'Mailer invalid From address: ' . $e->getMessage();
            return null;
        }

        // Certificate checks stay on unless explicitly disabled (e.g. a self-signed local relay)
        if (defined('SMTP_ALLOW_INSECURE_TLS') && SMTP_ALLOW_INSECURE_TLS) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        $this->from_email = $mail->From;
        $this->from_name = $mail->FromName;

        if (defined('SMTP_USER') && defined('SMTP_PASS') && SMTP_USER && SMTP_PASS) {
            if (!defined('SMTP_AUTH')) {
                throw new Exception('SMTP_AUTH undefined');
            }
            $mail->Password = SMTP_PASS;
            $mail->Username = SMTP_USER;
            $mail->AuthType = SMTP_AUTH;
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : '';
        }

        return $mail;
    }

    /**
     * Decodes the stored attachment list (JSON, or legacy serialized data).
     *
     * @return array
     */
    private function getAttachments(): array
    {
        if (!$this->headers) {
            return [];
        }

        $attachments = json_decode($this->headers, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Legacy rows; only allow the one expected class to be instantiated
            $attachments = @unserialize($this->headers, ['allowed_classes' => ['EmailAttachment']]);
        }

        return is_array($attachments) ? $attachments : [];
    }

    /**
     * Attaches subject/body/embedded images/attachments to a configured PHPMailer
     * instance and sends it. Shared by both send modes in Send().
     *
     * @param PHPMailer $mail
     * @param string $subject
     * @param bool $debug
     * @return bool
     */
    private function attachAndSend(PHPMailer $mail, string $subject, bool $debug): bool
    {
        $mail->Subject = $subject;

        try {
            $mail->msgHTML((string)$this->message);

            foreach ((array)$this->embedded_images as $id => $file) {
                $mail->addEmbeddedImage((string)$file, (string)$id, basename((string)$file));
            }
        } catch (Exception $e) {
            $this->log = 'Mailer body/embedded images: ' . $e->getMessage();
            return false;
        }

        foreach ($this->getAttachments() as $name => $path) {
            if ($name === 'report_id') {
                // not an attachment; the email queue record handles this
                continue;
            }

            if (is_object($path)) {
                if (!$path instanceof IEmailAttachment) {
                    $this->log = 'Unsupported attachment object: ' . get_class($path);
                    return false;
                }
                $name = $path->FileName;
                $path = $path->FileLocation;
            }

            $name = (string)$name;
            $path = (string)$path;

            try {
                if ($path !== '' && file_exists($path)) {
                    $mail->addAttachment($path, $name);
                } else {
                    // Not a file on disk, so it must be base64 content
                    $data = base64_decode($path, true);
                    if ($data === false || $data === '') {
                        $this->log = "Attachment '" . $name . "' is neither an existing file nor valid base64";
                        return false;
                    }
                    $mail->addStringAttachment($data, $name);
                }
            } catch (Exception $e) {
                $this->log = $e->getMessage();
                return false;
            }
        }

        try {
            $mail->send();
        } catch (Exception $e) {
            if ($debug) {
                Testing([$mail->ErrorInfo, $mail]);
            }
            $this->log = $mail->ErrorInfo ?: $e->getMessage();
            $this->mail = $mail;
            return false;
        }

        return true;
    }

    /**
     * Replaces ##key## placeholders (case-insensitive) with values.
     *
     * @param string $filename
     * @param array $values
     * @param bool $escape HTML-escape values (use for anything user-supplied)
     * @return string
     */
    public static function Template(string $filename, array $values, bool $escape = false): string
    {
        if (!file_exists($filename)) {
            Exception(['error' => 'File does not exist', $filename]);
        }
        $html = (string)file_get_contents($filename);

        $lookup = [];
        foreach ($values as $key => $value) {
            $lookup[strtolower((string)$key)] = (string)$value;
        }

        // Single pass, so a value containing ##something## is never substituted again
        $missing = [];
        $html = preg_replace_callback(
            '/##(.*?)##/s',
            function (array $m) use ($lookup, $escape, &$missing): string {
                $key = strtolower($m[1]);
                if (!array_key_exists($key, $lookup)) {
                    $missing[] = $m[1];
                    return $m[0];
                }
                return $escape
                    ? htmlspecialchars($lookup[$key], ENT_QUOTES | ENT_HTML5, 'UTF-8')
                    : $lookup[$key];
            },
            $html
        ) ?? $html;

        if ($missing) {
            Exception(['Error' => 'HTML still contains variables', $missing, $html]);
        }
        return $html;
    }
}