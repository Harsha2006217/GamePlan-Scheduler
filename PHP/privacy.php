<?php
// GamePlan Scheduler - Privacy Policy
// Professional privacy policy page with GDPR compliance

require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - Privacy Policy</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="logo">
                    <i class="fas fa-gamepad"></i> GamePlan Scheduler
                </a>
                <nav>
                    <ul class="d-flex">
                        <?php if (isLoggedIn()): ?>
                            <li><a href="index.php">Dashboard</a></li>
                            <li><a href="profile.php">Profile</a></li>
                            <li><a href="friends.php">Friends</a></li>
                            <li><a href="schedules.php">Schedules</a></li>
                            <li><a href="events.php">Events</a></li>
                            <li><a href="?logout=1">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-header">
                            <h1 class="mb-0"><i class="fas fa-shield-alt"></i> Privacy Policy</h1>
                            <p class="text-muted mb-0 mt-2">Last updated: <?php echo date('F j, Y'); ?></p>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>GDPR Compliant:</strong> We are committed to protecting your privacy and complying with data protection regulations.
                            </div>

                            <h2>1. Introduction</h2>
                            <p>Welcome to GamePlan Scheduler ("we," "our," or "us"). We respect your privacy and are committed to protecting your personal data. This privacy policy will inform you about how we look after your personal data when you visit our website and tell you about your privacy rights and how the law protects you.</p>

                            <h2>2. Information We Collect</h2>
                            <h3>Personal Information</h3>
                            <ul>
                                <li><strong>Account Information:</strong> Username, email address, password (encrypted)</li>
                                <li><strong>Profile Information:</strong> First name, last name, bio, avatar (optional)</li>
                                <li><strong>Gaming Preferences:</strong> Favorite games, gaming schedules, events</li>
                            </ul>

                            <h3>Usage Information</h3>
                            <ul>
                                <li><strong>Activity Logs:</strong> Login times, actions performed, IP addresses</li>
                                <li><strong>Technical Data:</strong> Browser type, device information, cookies</li>
                                <li><strong>Communication Data:</strong> Messages between friends, event notifications</li>
                            </ul>

                            <h2>3. How We Use Your Information</h2>
                            <p>We use your personal data to:</p>
                            <ul>
                                <li>Provide and maintain our gaming scheduling service</li>
                                <li>Create and manage your user account</li>
                                <li>Facilitate connections with friends and gaming communities</li>
                                <li>Send important notifications about your schedules and events</li>
                                <li>Improve our services and develop new features</li>
                                <li>Ensure security and prevent fraud</li>
                                <li>Comply with legal obligations</li>
                            </ul>

                            <h2>4. Information Sharing and Disclosure</h2>
                            <p>We do not sell, trade, or otherwise transfer your personal information to third parties except in the following circumstances:</p>

                            <h3>With Your Consent</h3>
                            <ul>
                                <li>When you share events or schedules with friends</li>
                                <li>When you participate in public gaming communities</li>
                            </ul>

                            <h3>Service Providers</h3>
                            <ul>
                                <li>Web hosting and database services (encrypted and secure)</li>
                                <li>Email delivery services for notifications</li>
                                <li>Analytics services (anonymized data only)</li>
                            </ul>

                            <h3>Legal Requirements</h3>
                            <ul>
                                <li>To comply with applicable laws and regulations</li>
                                <li>To protect our rights and prevent fraud</li>
                                <li>In response to legal requests from authorities</li>
                            </ul>

                            <h2>5. Data Security</h2>
                            <p>We implement appropriate technical and organizational measures to ensure a level of security appropriate to the risk, including:</p>
                            <ul>
                                <li><strong>Encryption:</strong> All passwords are hashed using Argon2ID</li>
                                <li><strong>Secure Connections:</strong> All data transmission uses HTTPS</li>
                                <li><strong>Access Controls:</strong> Limited access to personal data on a need-to-know basis</li>
                                <li><strong>Regular Audits:</strong> Security monitoring and regular vulnerability assessments</li>
                                <li><strong>Data Minimization:</strong> We only collect and retain necessary data</li>
                            </ul>

                            <h2>6. Data Retention</h2>
                            <p>We retain your personal data for as long as necessary to provide our services and comply with legal obligations:</p>
                            <ul>
                                <li><strong>Active Accounts:</strong> Data retained while your account is active</li>
                                <li><strong>Inactive Accounts:</strong> Data retained for 2 years after account deactivation</li>
                                <li><strong>Legal Requirements:</strong> Some data may be retained longer if required by law</li>
                                <li><strong>Activity Logs:</strong> Security logs retained for 1 year</li>
                            </ul>

                            <h2>7. Your Rights</h2>
                            <p>Under GDPR and other privacy laws, you have the following rights:</p>

                            <h3>Access Rights</h3>
                            <ul>
                                <li><strong>Right to Access:</strong> Request a copy of your personal data</li>
                                <li><strong>Right to Rectification:</strong> Correct inaccurate or incomplete data</li>
                                <li><strong>Right to Erasure:</strong> Request deletion of your personal data</li>
                            </ul>

                            <h3>Control Rights</h3>
                            <ul>
                                <li><strong>Right to Restriction:</strong> Limit how we process your data</li>
                                <li><strong>Right to Object:</strong> Object to processing based on legitimate interests</li>
                                <li><strong>Right to Portability:</strong> Receive your data in a structured format</li>
                            </ul>

                            <h3>Communication Rights</h3>
                            <ul>
                                <li><strong>Right to Withdraw Consent:</strong> Withdraw consent for processing</li>
                                <li><strong>Right to Complain:</strong> Lodge a complaint with supervisory authorities</li>
                            </ul>

                            <h2>8. Cookies and Tracking</h2>
                            <p>We use cookies and similar technologies to enhance your experience:</p>

                            <h3>Essential Cookies</h3>
                            <ul>
                                <li>Session management and authentication</li>
                                <li>Security and fraud prevention</li>
                                <li>Remembering your preferences</li>
                            </ul>

                            <h3>Analytics Cookies</h3>
                            <ul>
                                <li>Understanding how you use our service</li>
                                <li>Improving website performance</li>
                                <li>Anonymous usage statistics</li>
                            </ul>

                            <p>You can control cookie settings through your browser preferences.</p>

                            <h2>9. Third-Party Services</h2>
                            <p>Our service may contain links to third-party websites or integrate with third-party services:</p>
                            <ul>
                                <li><strong>Social Features:</strong> Friend connections and sharing</li>
                                <li><strong>External Links:</strong> Links to gaming communities or resources</li>
                                <li><strong>APIs:</strong> Integration with gaming platforms (future feature)</li>
                            </ul>

                            <p>We are not responsible for the privacy practices of third-party services.</p>

                            <h2>10. Children's Privacy</h2>
                            <p>Our service is designed for young gamers aged 13 and above. We do not knowingly collect personal information from children under 13. If we become aware that we have collected personal information from a child under 13, we will take steps to delete such information.</p>

                            <h2>11. International Data Transfers</h2>
                            <p>Your data may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place to protect your data during international transfers, including:</p>
                            <ul>
                                <li>Adequacy decisions by relevant authorities</li>
                                <li>Standard contractual clauses</li>
                                <li>Binding corporate rules</li>
                                <li>Certification schemes</li>
                            </ul>

                            <h2>12. Changes to This Policy</h2>
                            <p>We may update this privacy policy from time to time. We will notify you of any changes by:</p>
                            <ul>
                                <li>Posting the new policy on this page</li>
                                <li>Sending you an email notification</li>
                                <li>Displaying a notice on our website</li>
                            </ul>

                            <p>Your continued use of our service after changes constitutes acceptance of the updated policy.</p>

                            <h2>13. Contact Us</h2>
                            <p>If you have any questions about this privacy policy or our data practices, please contact us:</p>

                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5>GamePlan Scheduler Privacy Team</h5>
                                    <p class="mb-1"><strong>Email:</strong> privacy@gameplan-scheduler.com</p>
                                    <p class="mb-1"><strong>Address:</strong> [Your Business Address]</p>
                                    <p class="mb-0"><strong>Response Time:</strong> We aim to respond within 30 days</p>
                                </div>
                            </div>

                            <h2>14. Data Protection Officer</h2>
                            <p>For GDPR-related inquiries, you can contact our Data Protection Officer:</p>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="mb-1"><strong>Name:</strong> [DPO Name]</p>
                                    <p class="mb-0"><strong>Email:</strong> dpo@gameplan-scheduler.com</p>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="text-center text-muted">
                                <p class="mb-0">This privacy policy was last updated on <?php echo date('F j, Y'); ?>.</p>
                                <p>If you have any concerns about your privacy, please don't hesitate to contact us.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 GamePlan Scheduler by Harsha Kanaparthi. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/script.js"></script>
</body>
</html>