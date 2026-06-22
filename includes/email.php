<?php

/**
 * Email notification functions for PandaPickle
 * Uses Brevo (formerly Sendinblue) SMTP for reliable email delivery
 */

/**
 * Send email via Brevo SMTP
 */
function sendBrevoEmail(string $toEmail, string $toName, string $subject, string $htmlContent): bool
{
    // Get Brevo SMTP credentials from environment variables
    $smtpHost = 'smtp-relay.brevo.com';
    $smtpPort = 587; // Use 587 for TLS
    $smtpUser = getenv('BREVO_SMTP_USER') ?: '';
    $smtpPass = getenv('BREVO_SMTP_PASS') ?: '';
    $fromEmail = getenv('BREVO_FROM_EMAIL') ?: 'noreply@pandapickle.com';
    $fromName = 'PandaPickle';
    
    // If credentials not set, fall back to PHP mail()
    if (empty($smtpUser) || empty($smtpPass)) {
        error_log('Brevo credentials not set, falling back to PHP mail()');
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            "From: {$fromName} <{$fromEmail}>",
            'X-Mailer: PHP/' . phpversion()
        ];
        return mail($toEmail, $subject, $htmlContent, implode("\r\n", $headers));
    }
    
    // Create email message
    $boundary = md5(uniqid(time()));
    
    $headers = [
        "From: {$fromName} <{$fromEmail}>",
        "To: {$toName} <{$toEmail}>",
        "Subject: {$subject}",
        "MIME-Version: 1.0",
        "Content-Type: multipart/alternative; boundary=\"{$boundary}\""
    ];
    
    $message = "--{$boundary}\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= strip_tags($htmlContent) . "\r\n\r\n";
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $htmlContent . "\r\n\r\n";
    $message .= "--{$boundary}--";
    
    // Connect to Brevo SMTP server
    $smtp = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);
    
    if (!$smtp) {
        error_log("Brevo SMTP connection failed: {$errstr} ({$errno})");
        return false;
    }
    
    // Read server greeting
    fgets($smtp, 515);
    
    // Send EHLO command
    fputs($smtp, "EHLO {$smtpHost}\r\n");
    fgets($smtp, 515);
    
    // Start TLS
    fputs($smtp, "STARTTLS\r\n");
    fgets($smtp, 515);
    stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
    // Send EHLO again after STARTTLS
    fputs($smtp, "EHLO {$smtpHost}\r\n");
    fgets($smtp, 515);
    
    // Authenticate
    fputs($smtp, "AUTH LOGIN\r\n");
    fgets($smtp, 515);
    fputs($smtp, base64_encode($smtpUser) . "\r\n");
    fgets($smtp, 515);
    fputs($smtp, base64_encode($smtpPass) . "\r\n");
    $authResponse = fgets($smtp, 515);
    
    if (strpos($authResponse, '235') === false) {
        error_log("Brevo SMTP authentication failed: {$authResponse}");
        fclose($smtp);
        return false;
    }
    
    // Send MAIL FROM
    fputs($smtp, "MAIL FROM: <{$fromEmail}>\r\n");
    fgets($smtp, 515);
    
    // Send RCPT TO
    fputs($smtp, "RCPT TO: <{$toEmail}>\r\n");
    fgets($smtp, 515);
    
    // Send DATA
    fputs($smtp, "DATA\r\n");
    fgets($smtp, 515);
    
    // Send headers and message
    foreach ($headers as $header) {
        fputs($smtp, "{$header}\r\n");
    }
    fputs($smtp, "\r\n{$message}\r\n.\r\n");
    fgets($smtp, 515);
    
    // Send QUIT
    fputs($smtp, "QUIT\r\n");
    fgets($smtp, 515);
    
    fclose($smtp);
    return true;
}

/**
 * Send booking confirmation email to customer
 */
function sendBookingConfirmationEmail(array $booking, string $customerEmail, string $customerName): bool
{
    $subject = "PandaPickle - Booking Confirmed #{$booking['code']}";
    
    $bookingTime = date('F j, Y', strtotime($booking['date'])) . ' at ' . 
                   date('g:i A', strtotime($booking['start_time']));
    
    $htmlContent = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #059669; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { padding: 20px; background: #f9f9f9; }
            .booking-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #059669; border-radius: 4px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; background: #f0f0f0; border-radius: 0 0 8px 8px; }
            .important { background: #fff3cd; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107; border-radius: 4px; }
            h1 { margin: 0; font-size: 24px; }
            h2 { margin: 0; font-size: 20px; font-weight: normal; }
            h3 { color: #059669; margin-top: 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏓 PandaPickle</h1>
                <h2>Booking Confirmed!</h2>
            </div>
            <div class='content'>
                <p>Dear {$customerName},</p>
                
                <p><strong>Your booking has been confirmed!</strong></p>
                
                <div class='booking-details'>
                    <h3>Booking Details:</h3>
                    <p><strong>Booking Code:</strong> {$booking['code']}</p>
                    <p><strong>Court:</strong> {$booking['court']}</p>
                    <p><strong>Date & Time:</strong> {$bookingTime}</p>
                    <p><strong>Duration:</strong> {$booking['hours']} hour(s)</p>
                    <p><strong>Total Amount:</strong> PHP {$booking['amount']}</p>
                </div>
                
                <div class='important'>
                    <strong>⚠️ IMPORTANT:</strong><br>
                    Please arrive at the court <strong>BEFORE {$bookingTime}</strong><br>
                    Show this email to the cashier for confirmation.
                </div>
                
                <p>We look forward to seeing you!</p>
            </div>
            <div class='footer'>
                <p>Please disregard this email and don't reply.</p>
                <p>© " . date('Y') . " PandaPickle. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendBrevoEmail($customerEmail, $customerName, $subject, $htmlContent);
}

/**
 * Send open play confirmation email to customer
 */
function sendOpenPlayConfirmationEmail(array $registration, string $customerEmail, string $customerName): bool
{
    $subject = "PandaPickle - Open Play Registration Confirmed";
    
    $sessionDate = date('F j, Y', strtotime($registration['session_date'])) . ' at ' . 
                   date('g:i A', strtotime($registration['start_time']));
    
    $htmlContent = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #059669; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { padding: 20px; background: #f9f9f9; }
            .booking-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #059669; border-radius: 4px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; background: #f0f0f0; border-radius: 0 0 8px 8px; }
            .important { background: #fff3cd; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107; border-radius: 4px; }
            h1 { margin: 0; font-size: 24px; }
            h2 { margin: 0; font-size: 20px; font-weight: normal; }
            h3 { color: #059669; margin-top: 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏓 PandaPickle</h1>
                <h2>Open Play Registration Confirmed!</h2>
            </div>
            <div class='content'>
                <p>Dear {$customerName},</p>
                
                <p><strong>Your open play registration has been confirmed!</strong></p>
                
                <div class='booking-details'>
                    <h3>Session Details:</h3>
                    <p><strong>Session:</strong> {$registration['title']}</p>
                    <p><strong>Date & Time:</strong> {$sessionDate}</p>
                    <p><strong>Your Team:</strong> {$registration['player1']} & {$registration['player2']}</p>
                    <p><strong>Total Amount:</strong> PHP {$registration['amount']}</p>
                </div>
                
                <div class='important'>
                    <strong>⚠️ IMPORTANT:</strong><br>
                    Please arrive at the court <strong>BEFORE {$sessionDate}</strong><br>
                    Show this email to the cashier for confirmation.
                </div>
                
                <p>We look forward to seeing you!</p>
            </div>
            <div class='footer'>
                <p>Please disregard this email and don't reply.</p>
                <p>© " . date('Y') . " PandaPickle. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendBrevoEmail($customerEmail, $customerName, $subject, $htmlContent);
}

