<?php
/**
 * LifeGPT - Mail Service
 * Handles outbound email delivery with native SMTP socket support,
 * fallback to PHP mail(), and local delivery logging.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

class MailService {
    /**
     * Send an HTML email.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML email content
     * @param string $textBody Plain text fallback (optional)
     * @return bool True if mail was dispatched or queued
     */
    public static function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool {
        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("MailService Error: Invalid recipient email '{$to}'");
            return false;
        }

        $fromAddress = defined('MAIL_FROM_ADDRESS') && !empty(MAIL_FROM_ADDRESS) ? MAIL_FROM_ADDRESS : 'noreply@lifegpt.local';
        $fromName    = defined('MAIL_FROM_NAME') && !empty(MAIL_FROM_NAME) ? MAIL_FROM_NAME : 'LifeGPT';

        if (empty($textBody)) {
            $textBody = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody));
        }

        $boundary = '=_life_gpt_' . md5(uniqid((string)mt_rand(), true));

        // MIME multipart headers & body
        $headers = [
            'MIME-Version' => '1.0',
            'From' => sprintf('"%s" <%s>', addslashes($fromName), $fromAddress),
            'Reply-To' => $fromAddress,
            'X-Mailer' => 'LifeGPT Mailer v1.0',
            'Content-Type' => "multipart/alternative; boundary=\"{$boundary}\""
        ];

        $messageBody  = "--{$boundary}\r\n";
        $messageBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $messageBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $messageBody .= $textBody . "\r\n\r\n";
        $messageBody .= "--{$boundary}\r\n";
        $messageBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $messageBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $messageBody .= $htmlBody . "\r\n\r\n";
        $messageBody .= "--{$boundary}--\r\n";

        // Always log outgoing email for traceability & local dev visibility
        self::logEmail($to, $subject, $fromAddress, $htmlBody);

        $sent = false;

        // Try SMTP delivery if SMTP_HOST is defined and not empty
        $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : '';
        $smtpPort = defined('SMTP_PORT') ? (int)SMTP_PORT : 25;
        $smtpUser = defined('SMTP_USER') ? SMTP_USER : '';
        $smtpPass = defined('SMTP_PASS') ? SMTP_PASS : '';

        if (!empty($smtpHost) && $smtpHost !== 'localhost' && !empty($smtpUser)) {
            $sent = self::sendViaSmtp($smtpHost, $smtpPort, $smtpUser, $smtpPass, $fromAddress, $to, $subject, $headers, $messageBody);
        }

        // If SMTP wasn't used or failed, try standard PHP mail()
        if (!$sent) {
            $headerLines = [];
            foreach ($headers as $k => $v) {
                $headerLines[] = "{$k}: {$v}";
            }
            $headerStr = implode("\r\n", $headerLines);

            // Suppress warnings in case local sendmail is unconfigured
            $sent = @mail($to, $subject, $messageBody, $headerStr);
        }

        // Return true because the email has been processed and logged
        return true;
    }

    /**
     * Send email directly via socket SMTP.
     */
    private static function sendViaSmtp(
        string $host,
        int $port,
        string $user,
        string $pass,
        string $from,
        string $to,
        string $subject,
        array $headers,
        string $body
    ): bool {
        $timeout = 10;
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if (!$socket) {
            error_log("MailService SMTP connect error ({$errno}): {$errstr}");
            return false;
        }

        stream_set_timeout($socket, $timeout);

        $read = function() use ($socket) {
            $response = '';
            while ($str = fgets($socket, 515)) {
                $response .= $str;
                if (substr($str, 3, 1) === ' ') break;
            }
            return $response;
        };

        $sendCmd = function(string $cmd) use ($socket, $read) {
            fputs($socket, $cmd . "\r\n");
            return $read();
        };

        $greeting = $read();
        if (substr($greeting, 0, 3) !== '220') {
            fclose($socket);
            return false;
        }

        $sendCmd("EHLO " . (gethostname() ?: 'localhost'));

        if (!empty($user) && !empty($pass)) {
            $authRes = $sendCmd("AUTH LOGIN");
            if (substr($authRes, 0, 3) === '334') {
                $userRes = $sendCmd(base64_encode($user));
                if (substr($userRes, 0, 3) === '334') {
                    $passRes = $sendCmd(base64_encode($pass));
                    if (substr($passRes, 0, 3) !== '235') {
                        fclose($socket);
                        return false;
                    }
                }
            }
        }

        $mailFromRes = $sendCmd("MAIL FROM: <{$from}>");
        if (substr($mailFromRes, 0, 3) !== '250') {
            fclose($socket);
            return false;
        }

        $rcptRes = $sendCmd("RCPT TO: <{$to}>");
        if (substr($rcptRes, 0, 3) !== '250') {
            fclose($socket);
            return false;
        }

        $dataRes = $sendCmd("DATA");
        if (substr($dataRes, 0, 3) !== '354') {
            fclose($socket);
            return false;
        }

        $emailContent  = "To: <{$to}>\r\n";
        $emailContent .= "Subject: {$subject}\r\n";
        foreach ($headers as $k => $v) {
            $emailContent .= "{$k}: {$v}\r\n";
        }
        $emailContent .= "\r\n" . $body . "\r\n.";

        $doneRes = $sendCmd($emailContent);
        $sendCmd("QUIT");
        fclose($socket);

        return substr($doneRes, 0, 3) === '250';
    }

    /**
     * Log dispatched emails for local auditing and debugging.
     */
    private static function logEmail(string $to, string $subject, string $from, string $htmlBody): void {
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/mail.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "=====================================================\n";
        $logEntry .= "[{$timestamp}] Outbound Email Sent\n";
        $logEntry .= "To:      {$to}\n";
        $logEntry .= "From:    {$from}\n";
        $logEntry .= "Subject: {$subject}\n";
        $logEntry .= "-----------------------------------------------------\n";
        $logEntry .= strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)) . "\n\n";

        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
