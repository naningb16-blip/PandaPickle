<?php

require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'About - PandaPickle';
$currentPage = 'about';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>About PandaPickle</h1>
        <p>Your complete pickleball court reservation and open play management platform.</p>
    </div>

    <div class="card mb-2">
        <div class="card-body">
            <h2 class="text-green mb-2">Our Mission</h2>
            <p>PandaPickle provides a seamless way for pickleball enthusiasts to reserve exclusive court time and join open play sessions. We operate daily from <strong>5:00 AM to 10:00 PM</strong>.</p>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h3>Exclusive Reservations</h3></div>
            <div class="card-body">
                <p>Book a private court at <strong>PHP 250 per hour</strong>. Choose your date, start time, and duration. Total amount is calculated automatically.</p>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3>Open Play Sessions</h3></div>
            <div class="card-body">
                <p>Join group sessions at <strong>PHP 50 per player</strong>. Admin-created sessions with limited slots for friendly community games.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
