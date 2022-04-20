<?php

namespace Innoweb\SendGrid;

use Config;
use Mailer;
use SendGrid;
use SendGrid\Mail\Mail;

class SendGridMailer extends Mailer
{
    /**
     * API key for SendGrid API
     *
     * @var string
     * @config
     */
    private static $api_key = '';

    /**
     * Send a plain-text email.
     *
     * @param string $to Email recipient
     * @param string $from Email from
     * @param string $subject Subject text
     * @param string $plainContent Plain text content
     * @param array $attachedFiles List of attached files
     * @param array $customHeaders List of custom headers
     * @return mixed Return false if failure, or list of arguments if success
     */
    public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = [], $customHeaders = [])
    {
        return $this->sendSendGridEmail($to, $from, $subject, false, $attachedFiles, $customHeaders, $plainContent);
    }

    /**
     * Sends an email as a both HTML and plaintext
     *
     * @param string $to Email recipient
     * @param string $from Email from
     * @param string $subject Subject text
     * @param string $htmlContent HTML Content
     * @param array $attachedFiles List of attachments
     * @param array $customHeaders User specified headers
     * @param string $plainContent Plain text content. If omitted, will be generated from $htmlContent
     * @return mixed Return false if failure, or list of arguments if success
     */
    public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = [], $customHeaders = [], $plainContent = '')
    {
        return $this->sendSendGridEmail($to, $from, $subject, $htmlContent, $attachedFiles, $customHeaders, $plainContent);
    }

    private function sendSendGridEmail($to, $from, $subject, $htmlContent, $attachedFiles = [], $customHeaders = [], $plainContent = '')
    {
        if (!$apiKey = Config::inst()->get(self::class, 'api_key')) {
            user_error(self::class . ' requires a SendGrid \'api_key\'. Please add it to your YML configuration.');
        }

        $sendGridEmail = new Mail();
        $sendGridEmail->setSubject($subject);
        $sendGridEmail->setFrom($from);
        $sendGridEmail->addTo($to);

        $cc = null;
        $bcc = null;
        $replyTo = null;

        // Parse out problematic custom headers
        if (is_array($customHeaders)) {
            if (array_key_exists('Cc', $customHeaders)) {
                $cc = $customHeaders['Cc'];
                unset($customHeaders['Cc']);
            }
            if (array_key_exists('Bcc', $customHeaders)) {
                $bcc = $customHeaders['Bcc'];
                unset($customHeaders['Bcc']);
            }
            if (array_key_exists('Reply-To', $customHeaders)) {
                $replyTo = $customHeaders['Reply-To'];
                unset($customHeaders['Reply-To']);
            }
            if (empty($customHeaders)) {
                $customHeaders = null;
            }
        } else {
            $customHeaders = null;
        }

        // add cc and bcc
        if ($cc) {
            $sendGridEmail->addCc($cc);
        }
        if ($bcc) {
            $sendGridEmail->addBcc($bcc);
        }
        if ($replyTo) {
            $sendGridEmail->setReplyTo($replyTo);
        }

        // add remaning custom headers
        if ($customHeaders && is_array($customHeaders)) {
            foreach ($customHeaders as $key => $value) {
                $sendGridEmail->addHeader($key, $value);
            }
        }

        if (!($htmlContent || $plainContent)) {
            user_error(self::class . ': ' . "Can't send email with no content");
            return false;
        }
        if ($htmlContent) {
            $sendGridEmail->addContent('text/html', $htmlContent);
        }
        if (strlen($plainContent)) {
            $sendGridEmail->addContent('text/plain', $plainContent);
        }

        // handle attachments
        if ($attachedFiles && is_array($attachedFiles)) {
            foreach ($attachedFiles as $f) {
                //$attachments[] = ::fromRawData($f['contents'], $f['filename'], $f['mimetype']);
                $sendGridEmail->addAttachment($f['contents'], $f['mimetype'], $f['filename']);
            }
        }

        $sendgrid = new SendGrid($apiKey);

        try {
            $response = $sendgrid->send($sendGridEmail);
            if ($response->statusCode() != 202) {
                $responseBody = json_decode($response->body(), true);
                user_error(self::class . ': ' . (isset($responseBody['errors'][0]['message']) ? $responseBody['errors'][0]['message'] : $response->body()));
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
