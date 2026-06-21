<?php

require_once __DIR__ . '/includes/auth.php';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = true;
    flash('success', 'Thank you! Your message has been received. We will get back to you soon.');
    header('Location: contact.php');
    exit;
}

$pageTitle = 'Contact - PandaPickle';
$currentPage = 'contact';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Contact Us</h1>
        <p>Have questions about reservations or open play? Reach out to our team.</p>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h3>Send a Message</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3>Contact Information</h3></div>
            <div class="card-body">
                <p><strong>Location:</strong> PandaPickle Courts, Tupi South Cotabato</p>
                <p><strong>Hours:</strong> 5:00 AM – 10:00 PM Daily</p>
                <p><strong>Email:</strong> info@pandapickle.com</p>
                <p><strong>Phone:</strong> +63 912 345 6789</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
