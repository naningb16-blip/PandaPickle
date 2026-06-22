<?php

/**
 * Email notification functions for PandaPickle
 * Uses PHP's mail() function
 */

/**
 * Send booking confirmation email to customer
 */
function sendBookingConfirmationEmail(array $booking, string $customerEmail, string $customerName): bool
{
    $subject = "PandaPickle - Booking Confirmed #{$booking['code']}";
    
    $bookingTime = date('F j, Y', strtotime($booking['date'])) . ' at ' . 
                   date('g:i A', strtotime($booking['start_time']));
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #059669; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .booking-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #059669; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .important { background: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107; }
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
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: PandaPickle <noreply@pandapickle.com>',
        'Reply-To: noreply@pandapickle.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($customerEmail, $subject, $message, implode("\r\n", $headers));
}

/**
 * Send open play confirmation email to customer
 */
function sendOpenPlayConfirmationEmail(array $registration, string $customerEmail, string $customerName): bool
{
    $subject = "PandaPickle - Open Play Registration Confirmed";
    
    $sessionDate = date('F j, Y', strtotime($registration['session_date'])) . ' at ' . 
                   date('g:i A', strtotime($registration['start_time']));
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #059669; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .booking-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #059669; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .important { background: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107; }
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
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: PandaPickle <noreply@pandapickle.com>',
        'Reply-To: noreply@pandapickle.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($customerEmail, $subject, $message, implode("\r\n", $headers));
}
