<?php
// footer.php - Common Footer Component
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Fixed footer with copyright info and links.
// Ensures consistent layout across pages.

if (!defined('IN_FOOTER')) {
    define('IN_FOOTER', true);
}

?>
<footer class="fixed-bottom bg-secondary p-3 text-center shadow-top">
    <div class="container">
        <p class="mb-0">&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi. All rights reserved.</p>
        <a href="privacy.php" class="text-white mx-2">Privacy Policy</a> |
        <a href="contact.php" class="text-white mx-2">Contact</a>
    </div>
</footer>