    </main>

    <footer class="dani-footer mt-5">
        <div class="container py-4">
            <div class="row gy-4">
                <div class="col-md-4">
                    <h5 class="footer-title">Dani Group</h5>
                    <p class="footer-text">
                        Premium auto parts, accessories, bike parts, delivery services,
                        towing, and customer-first support.
                    </p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/p/Dani-Group-100095323403839/" class="social-link" title="Facebook" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.91h-2.34V22c4.78-.79 8.44-4.94 8.44-9.94Z"/></svg>
                        </a>
                        <a href="https://www.instagram.com/danigroupsa_danigroupsa/" class="social-link" title="Instagram" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24"><path d="M12 2c2.72 0 3.06.01 4.12.06 1.06.05 1.79.22 2.43.47.66.26 1.21.6 1.76 1.15.5.5.9 1.1 1.15 1.76.25.64.42 1.37.47 2.43.05 1.06.06 1.4.06 4.12s-.01 3.06-.06 4.12c-.05 1.06-.22 1.79-.47 2.43a4.9 4.9 0 0 1-1.15 1.76 4.9 4.9 0 0 1-1.76 1.15c-.64.25-1.37.42-2.43.47-1.06.05-1.4.06-4.12.06s-3.06-.01-4.12-.06c-1.06-.05-1.79-.22-2.43-.47a4.9 4.9 0 0 1-1.76-1.15 4.9 4.9 0 0 1-1.15-1.76c-.25-.64-.42-1.37-.47-2.43C2.01 15.06 2 14.72 2 12s.01-3.06.06-4.12c.05-1.06.22-1.79.47-2.43.26-.66.6-1.21 1.15-1.76a4.9 4.9 0 0 1 1.76-1.15c.64-.25 1.37-.42 2.43-.47C8.94 2.01 9.28 2 12 2Zm0 1.8c-2.67 0-2.99.01-4.04.06-.87.04-1.34.18-1.65.3-.42.16-.71.35-1.02.66-.31.31-.5.6-.66 1.02-.12.31-.26.78-.3 1.65C4.28 8.53 4.27 8.85 4.27 12s.01 3.47.06 4.51c.04.87.18 1.34.3 1.65.16.42.35.71.66 1.02.31.31.6.5 1.02.66.31.12.78.26 1.65.3 1.05.05 1.37.06 4.04.06s2.99-.01 4.04-.06c.87-.04 1.34-.18 1.65-.3.42-.16.71-.35 1.02-.66.31-.31.5-.6.66-1.02.12-.31.26-.78.3-1.65.05-1.04.06-1.36.06-4.51s-.01-3.47-.06-4.51c-.04-.87-.18-1.34-.3-1.65-.16-.42-.35-.71-.66-1.02a2.7 2.7 0 0 0-1.02-.66c-.31-.12-.78-.26-1.65-.3C14.99 3.81 14.67 3.8 12 3.8Zm0 3.05a5.15 5.15 0 1 1 0 10.3 5.15 5.15 0 0 1 0-10.3Zm0 1.8a3.35 3.35 0 1 0 0 6.7 3.35 3.35 0 0 0 0-6.7Zm5.35-1.98a1.2 1.2 0 1 1-2.4 0 1.2 1.2 0 0 1 2.4 0Z"/></svg>
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <h5 class="footer-title">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="/index.php">Home</a></li>
                        <li><a href="/products.php">Store</a></li>
                        <li><a href="/dropper.php">Dropper</a></li>
                        <li><a href="/towing.php">Towing</a></li>
                        <li><a href="/about.php">About</a></li>
                        <li><a href="/contact.php">Contact</a></li>
                    </ul>
                </div>

                <div class="col-md-4">
                    <h5 class="footer-title">Business Info</h5>
                    <p class="footer-text mb-1">Dani Group (Pty) Ltd.</p>
                    <p class="footer-text mb-1">South Africa</p>
                    <p class="footer-text mb-1">Support for parts, transport, and returns</p>
                </div>
            </div>

            <hr class="footer-line">
            <div class="text-center footer-bottom">
                &copy; <?= date('Y') ?> Dani Group (Pty) Ltd. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/address-autocomplete.js"></script>
    <script src="/assets/js/theme-toggle.js"></script>
    <?php if (!empty($extraScripts)) { echo $extraScripts; } ?>
</body>
</html>
