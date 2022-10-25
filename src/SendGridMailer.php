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
     * @return boolean Return false if failure, or true if success
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
     * @return boolean Return false if failure, or true if success
     */
    public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = [], $customHeaders = [], $plainContent = '')
    {
        return $this->sendSendGridEmail($to, $from, $subject, $htmlContent, $attachedFiles, $customHeaders, $plainContent);
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
     * @return boolean Return false if failure, or true if success
     */
    private function sendSendGridEmail($to, $from, $subject, $htmlContent, $attachedFiles = [], $customHeaders = [], $plainContent = '')
    {
        if (!$apiKey = Config::inst()->get(self::class, 'api_key')) {
            user_error(self::class . ' requires a SendGrid \'api_key\'. Please add it to your YML configuration.', E_USER_ERROR);
        }

        try {
            $sendGridEmail = new Mail();
            $sendGridEmail->setSubject($subject);
            $sendGridEmail->setFrom($from);

            $to = $this->splitEmailAddresses($to);
            $sendGridEmail->addTos($to);

            $cc = null;
            $bcc = null;
            $replyTo = null;

            // Parse out problematic custom headers
            if (is_array($customHeaders)) {
                if (array_key_exists('Cc', $customHeaders)) {
                    $cc = $customHeaders['Cc'];
                    $cc = $this->splitEmailAddresses($cc);
                    $cc = array_diff_key($cc, $to);
                    unset($customHeaders['Cc']);
                }
                if (array_key_exists('Bcc', $customHeaders)) {
                    $bcc = $customHeaders['Bcc'];
                    $bcc = $this->splitEmailAddresses($bcc);
                    $bcc = array_diff_key($bcc, $to);
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
            if ($cc && count($cc)) {
                $sendGridEmail->addCcs($cc);
            }
            if ($bcc && count($bcc)) {
                $sendGridEmail->addBccs($bcc);
            }
            if ($replyTo) {
                $sendGridEmail->setReplyTo($replyTo);
            }

            // add remaining custom headers
            if ($customHeaders && is_array($customHeaders)) {
                foreach ($customHeaders as $key => $value) {
                    $sendGridEmail->addHeader($key, $value);
                }
            }

            if (!($htmlContent || $plainContent)) {
                user_error(self::class . ': ' . "Can't send email with no content", E_USER_ERROR);
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

            $response = $sendgrid->send($sendGridEmail);
            if ($response->statusCode() != 202) {
                $responseBody = json_decode($response->body(), true);
                user_error(self::class . ': ' . (isset($responseBody['errors'][0]['message']) ? $responseBody['errors'][0]['message'] : $response->body()), E_USER_ERROR);
                return false;
            }
            return true;
        } catch (\Exception $e) {
            user_error(self::class . ': ' . $e->getMessage(), E_USER_ERROR);
            return false;
        }
    }

    private function splitEmailAddresses($email)
    {
        if (is_array($email)) {
            return $email;
        }
        if (is_string($email) && stripos($email, ';') !== false) {
            $email = explode(';', trim(trim($email), ';'));
            return array_combine($email, $email);
        }
        if (is_string($email) && stripos($email, ',') !== false) {
            $email = explode(',', trim(trim($email), ','));
            return array_combine($email, $email);
        }
        return [$email => $email];
    }
}
