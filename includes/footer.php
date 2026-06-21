    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <div>
                <strong>PandaPickle</strong>
                <p>Premium pickleball court reservations &amp; open play management.</p>
            </div>
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
                <a href="reservations.php">Reservations</a>
            </div>
            <p class="copyright">&copy; <?= date('Y') ?> PandaPickle. All rights reserved.</p>
        </div>
    </footer>
    <?php if (!empty($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
</body>
</html>
