<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Mailgun\HttpClient\HttpClientException;
use Mailgun\Mailgun;

class MailService
{
    /**
     * Send email using the application Mailgun settings.
     *
     * @param  array{to: string, cc?: string|array<int, string>|null, subject: string, html: string, template: object, attachments?: array}  $params
     * @return array{success: bool, message: string, status_code: int|null}
     */
    public static function sendEmail(array $params): array
    {
        foreach (['to', 'subject', 'html', 'template'] as $field) {
            if (empty($params[$field])) {
                return [
                    'success' => false,
                    'message' => "Missing required parameter: {$field}",
                    'status_code' => null,
                ];
            }
        }

        $template = $params['template'];
        $to = $params['to'];
        $subject = $params['subject'];
        $htmlContent = $params['html'];
        $attachments = $params['attachments'] ?? [];
        $userId = $params['user_id'] ?? null;

        $apiKey = trim((string) config('services.mailgun.secret'));
        $domain = trim((string) config('services.mailgun.domain'));

        if ($apiKey === '' || $domain === '') {
            Log::error('Missing Mailgun credentials');

            return [
                'success' => false,
                'message' => 'Email service not configured.',
                'status_code' => null,
            ];
        }

        try {
            $mg = Mailgun::create($apiKey, 'https://api.mailgun.net');

            $localPart = $template->from ?: 'noreply';
            $fromAddress = $localPart.'@'.$domain;
            $fromName = $template->display_name ?? config('app.name', 'Servicore');
            $from = "\"{$fromName}\" <{$fromAddress}>";

            $replyEmail = trim((string) ($template->reply_to_email ?? ''));
            $replyName = trim((string) ($template->reply_to_name ?? ''));
            $replyToHeader = $replyEmail !== ''
                ? ($replyName !== ''
                    ? '"'.addcslashes($replyName, '"\\').'" <'.$replyEmail.'>'
                    : $replyEmail)
                : null;

            $messageParams = [
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'html' => $htmlContent,
                'h:Reply-To' => $replyToHeader,
            ];

            $ccHeader = self::normalizeAddressList($params['cc'] ?? null);
            if ($ccHeader !== null) {
                $messageParams['cc'] = $ccHeader;
            }

            $bccHeader = self::buildBccHeader(
                $template->bcc_recipients ?? null,
                $to,
                $params['cc'] ?? null
            );
            if ($bccHeader !== null) {
                $messageParams['bcc'] = $bccHeader;
            }

            if (! empty($attachments)) {
                $messageParams['attachment'] = [];
                foreach ($attachments as $attachment) {
                    if (is_array($attachment) && isset($attachment['path'])) {
                        $messageParams['attachment'][] = [
                            'filePath' => $attachment['path'],
                            'filename' => $attachment['filename'] ?? basename($attachment['path']),
                        ];
                    } elseif (is_string($attachment)) {
                        $messageParams['attachment'][] = ['filePath' => $attachment];
                    }
                }
            }

            $mg->messages()->send($domain, $messageParams);

            return [
                'success' => true,
                'message' => 'Email sent successfully.',
                'status_code' => 200,
            ];
        } catch (HttpClientException $e) {
            $statusCode = $e->getCode();

            if ($statusCode === 401) {
                Log::error('Invalid Mailgun API key');

                return [
                    'success' => false,
                    'message' => 'Email delivery failed. Invalid email settings.',
                    'status_code' => 401,
                ];
            }

            if ($statusCode === 403) {
                Log::error('Mailgun domain not verified');

                return [
                    'success' => false,
                    'message' => 'Email domain not verified.',
                    'status_code' => 403,
                ];
            }

            if ($statusCode === 429) {
                return [
                    'success' => false,
                    'message' => 'Daily email limit reached. Try again tomorrow.',
                    'status_code' => 429,
                ];
            }

            $userContext = $userId ? " for user {$userId}" : '';
            Log::error("Email sending failed{$userContext}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'Email sending failed.',
                'status_code' => $statusCode,
            ];
        } catch (\Exception $e) {
            $userContext = $userId ? " for user {$userId}" : '';
            Log::error("Unexpected email error{$userContext}: ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'An unexpected error occurred while sending email.',
                'status_code' => 500,
            ];
        }
    }

    public static function getMailConfig($template): array
    {
        $domain = trim((string) config('services.mailgun.domain'));
        if ($domain === '') {
            throw new \Exception('Mailgun domain not configured');
        }

        $localPart = $template->from ?: 'noreply';
        $fromAddress = $localPart.'@'.$domain;
        $displayName = $template->from_name ?? config('app.name', 'Servicore');

        return [
            'from_address' => $fromAddress,
            'from_name' => $displayName,
            'reply_to_address' => $template->reply_to_email ?? null,
            'reply_to_name' => $template->reply_to_name ?? null,
            'bcc_recipients' => $template->bcc_recipients ?? null,
        ];
    }

    private static function extractEmailAddress(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/<([^>]+)>\s*$/', $value, $m) === 1) {
            return trim($m[1]);
        }

        return $value;
    }

    /**
     * @return array<string, true>
     */
    private static function primaryRecipientKeys(string $to, mixed $cc): array
    {
        $keys = [];
        $toAddr = self::extractEmailAddress($to);
        if ($toAddr !== null && $toAddr !== '' && filter_var($toAddr, FILTER_VALIDATE_EMAIL)) {
            $keys[mb_strtolower($toAddr)] = true;
        }
        $ccNorm = self::normalizeAddressList($cc);
        if ($ccNorm === null) {
            return $keys;
        }
        foreach (preg_split('/,\s*/', $ccNorm) as $part) {
            $email = self::extractEmailAddress(trim((string) $part));
            if ($email !== null && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $keys[mb_strtolower($email)] = true;
            }
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    private static function normalizeBccRecipientList(mixed $bccRecipients): array
    {
        if ($bccRecipients === null || $bccRecipients === '' || $bccRecipients === []) {
            return [];
        }
        $items = is_array($bccRecipients) ? $bccRecipients : preg_split('/[,;]+/', (string) $bccRecipients);
        $seen = [];
        $out = [];
        foreach ($items as $entry) {
            $raw = trim((string) $entry);
            if ($raw === '') {
                continue;
            }
            $email = self::extractEmailAddress($raw);
            if ($email === null || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $key = mb_strtolower($email);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $email;
        }

        return $out;
    }

    private static function buildBccHeader(mixed $bccRecipients, string $to, mixed $cc): ?string
    {
        $list = self::normalizeBccRecipientList($bccRecipients);
        if ($list === []) {
            return null;
        }
        $skip = self::primaryRecipientKeys($to, $cc);
        $filtered = [];
        foreach ($list as $email) {
            if (! isset($skip[mb_strtolower($email)])) {
                $filtered[] = $email;
            }
        }

        return $filtered === [] ? null : implode(', ', $filtered);
    }

    private static function normalizeAddressList(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $items = is_array($value) ? $value : preg_split('/[,;]+/', (string) $value);
        $seen = [];
        $out = [];
        foreach ((array) $items as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '') {
                continue;
            }
            $key = mb_strtolower($entry);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $entry;
        }

        return $out === [] ? null : implode(', ', $out);
    }
}
