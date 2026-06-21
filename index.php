<?php

require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'PandaPickle - Pickleball Court Reservations';
$currentPage = 'home';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1>Reserve Your Court. Join Open Play.</h1>
        <p>PandaPickle makes exclusive court reservations and open play registration simple. Book from 5:00 AM to 10:00 PM at PHP 250/hour.</p>
        <div class="hero-actions">
            <a href="reservations.php" class="btn btn-primary">Book a Court</a>
            <a href="open-play.php" class="btn btn-outline">View Open Play</a>
        </div>
    </div>
</section>

<div class="container">
    <div class="grid-3 mb-2">
        <div class="stat-card">
            <div class="value">5AM – 10PM</div>
            <div class="label">Operating Hours</div>
        </div>
        <div class="stat-card">
            <div class="value">PHP 250</div>
            <div class="label">Per Hour (Exclusive)</div>
        </div>
        <div class="stat-card">
            <div class="value">PHP 50</div>
            <div class="label">Per Player (Open Play)</div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h3>Exclusive Court Reservation</h3></div>
            <div class="card-body">
                <p>Select your date, start time, and number of hours. The system automatically calculates your end time and total amount.</p>
                <ul class="mt-2 text-muted">
                    <li>Automatic overlap prevention</li>
                    <li>Admin approval workflow</li>
                    <li>70% cashless downpayment for advance booking</li>
                    <li>Cancelation has no Refunds</li>
                    <li>Pay Via cash or cashless</li>
                </ul>
                <a href="login.php" class="btn btn-primary mt-2">Get Started</a>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3>Open Play Registration</h3></div>
            <div class="card-body">
                <p>Join community open play sessions created by our admin team. Register players and pay the session fee online.</p>
                <ul class="mt-2 text-muted">
                    <li>View available slots</li>
                    <li>Register multiple players</li>
                    <li>Track registration status</li>
                    <li>Register With Friends Only</li>
                    <li>Pay Via cash or cashless</li>
                </ul>
                <a href="open-play.php" class="btn btn-primary mt-2">Browse Sessions</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
