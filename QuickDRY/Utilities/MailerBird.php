<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

use Exception;
use MessageBird\Bird;
use MessageBird\Exception\ApiException;
use MessageBird\Wire\Model\EmailAttachment;
use Throwable;

/**
 * Drop-in replacement for Mailer that delivers through the Bird email API
 * instead of SMTP/PHPMailer.
 *
 * Send() is inherited from Mailer, so behavior is identical:
 *  - SMTP_ON == 0 returns -1
 *  - SMTP_DEBUG redirects to SMTP_DEBUG_EMAIL (-2 if undefined), drops Cc,
 *    and prefixes the subject with "TEST EMAIL: "
 *  - default mode sends one message per address in to_email;
 *    QueueWithCC() sends one message with To + Cc
 *  - from_email/from_name fall back to SMTP_FROM_EMAIL/SMTP_FROM_NAME
 *  - attachments (file paths, base64, IEmailAttachment) and embedded images are sent
 *  - returns 1 on success / 0 on failure with the reason in $this->log,
 *    and sets is_sent/sent_at on success
 *
 * Requires BIRD_SMTP_APIKEY. $smtp_output has no effect on this transport.
 */
class MailerBird extends Mailer
{
    /**
     * Bird message ids (em_...) returned by the most recent Send(), one per message sent.
     *
     * @var string[]
     */
    protected array $bird_message_ids = [];

    private ?Bird $bird = null;

    /**
     * @return string[]
     */
    public function GetBirdMessageIds(): array
    {
        return $this->bird_message_ids;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function checkTransportConfig(): void
    {
        if (!defined('BIRD_SMTP_APIKEY') || !BIRD_SMTP_APIKEY) {
            throw new Exception('BIRD_SMTP_APIKEY undefined');
        }
        if (!class_exists(Bird::class)) {
            throw new Exception('MailerBird requires the Bird SDK: composer require messagebird/sdk');
        }
        $this->bird_message_ids = [];
    }

    /**
     * @param string[] $to_emails
     * @param string $to_name
     * @param string[] $cc_emails
     * @param string $subject
     * @param bool $debug
     * @param bool $smtp_output unused for Bird
     * @return bool
     */
    protected function deliver(array $to_emails, string $to_name, array $cc_emails, string $subject, bool $debug, bool $smtp_output): bool
    {
        // Same From fallback as Mailer, and record what was actually used
        $this->from_email = $this->from_email ?? SMTP_FROM_EMAIL;
        $this->from_name = $this->from_name ?? SMTP_FROM_NAME;

        $from = ['email' => $this->from_email];
        if ((string)$this->from_name !== '') {
            $from['name'] = (string)$this->from_name;
        }

        $to = [];
        foreach ($to_emails as $email) {
            $to[] = $to_name !== '' ? ['email' => $email, 'name' => $to_name] : $email;
        }

        $attachments = $this->buildBirdAttachments();
        if ($attachments === null) {
            return false;
        }

        try {
            $this->bird ??= new Bird(BIRD_SMTP_APIKEY);

            $message = $this->bird->email->send(
                from: $from,
                to: $to,
                subject: $subject,
                html: (string)$this->message,
                cc: $cc_emails ?: null,
                attachments: $attachments ?: null,
            );
        } catch (Throwable $e) {
            if ($debug) {
                Testing([$e]);
            }
            $this->log = self::describeError($e);
            return false;
        }

        if ($message->getId()) {
            $this->bird_message_ids[] = $message->getId();
        }

        return true;
    }

    /**
     * Converts queued attachments and embedded images into Bird attachment models.
     * Embedded images keep their id as the content id, so <img src="cid:ID"> works
     * the same as with PHPMailer's addEmbeddedImage().
     *
     * @return EmailAttachment[]|null null on failure (reason in $this->log)
     */
    private function buildBirdAttachments(): ?array
    {
        $result = [];

        foreach ((array)$this->embedded_images as $id => $file) {
            $file = (string)$file;
            $data = is_file($file) ? file_get_contents($file) : false;
            if ($data === false) {
                $this->log = 'Mailer body/embedded images: Could not access file: ' . $file;
                return null;
            }
            $result[] = (new EmailAttachment())
                ->setFilename(basename($file))
                ->setContent(base64_encode($data))
                ->setContentId((string)$id);
        }

        $attachments = $this->resolveAttachments();
        if ($attachments === null) {
            return null;
        }

        foreach ($attachments as $attachment) {
            $data = $attachment['data'];
            if ($attachment['path'] !== null) {
                $data = file_get_contents($attachment['path']);
                if ($data === false) {
                    $this->log = 'Could not access file: ' . $attachment['path'];
                    return null;
                }
            }

            $name = $attachment['name'] !== '' || $attachment['path'] === null
                ? $attachment['name']
                : basename($attachment['path']);

            $result[] = (new EmailAttachment())
                ->setFilename($name)
                ->setContent(base64_encode($data));
        }

        return $result;
    }

    /**
     * @param Throwable $e
     * @return string
     */
    private static function describeError(Throwable $e): string
    {
        if ($e instanceof ApiException) {
            return 'Bird API error ' . $e->status
                . ($e->errorCode ? ' (' . $e->errorCode . ')' : '')
                . ': ' . $e->getMessage();
        }
        return 'Bird: ' . $e->getMessage();
    }
}
