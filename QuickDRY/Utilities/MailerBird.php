<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

use Exception;
use MessageBird\Bird;

/**
 *
 */
class MailerBird extends Mailer
{
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
        if(!defined('BIRD_SMTP_APIKEY')) {
            Exception('BIRD_SMTP_APIKEY is not defined');
        }

        $bird = new Bird(BIRD_SMTP_APIKEY);

        $message = $bird->email->send(
            from: $this->from_email,
            to: $this->to_email,
            subject: $this->subject,
            html: $this->message,
        );

        echo $message->getId(), ' ', $message->getStatus(), "\n";
        return 1;
    }
}