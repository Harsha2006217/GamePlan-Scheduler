<?php
require_once 'functions.php';
requireLogin();
checkTimeout();
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;

if (!in_array($type, ['schedule', 'event']) || !is_numeric($id)) {
    setMessage('error', 'Invalid deletion request.');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $success = false;
    $message = '';
    
    switch ($type) {
        case 'schedule':
            $success = softDeleteSchedule($id);
            $message = $success ? 'Schedule deleted successfully!' : 'Failed to delete schedule.';
            break;
            
        case 'event':
            $success = softDeleteEvent($id);
            $message = $success ? 'Event deleted successfully!' : 'Failed to delete event.';
            break;
            
        default:
            $message = 'Invalid deletion type.';
            break;
    }
    
    setMessage($success ? 'success' : 'error', $message);
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete <?php echo ucfirst($type); ?> - GamePlan Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --danger-color: #dc3545;
            --dark-bg: #121212;
            --card-bg: #1e1e1e;
            --input-bg: #2c2c2c;
            --text-color: #ffffff;
            --header-bg: #1a1a2e;
        }
        
        body { 
            background: linear-gradient(135deg, #121212 0%, #1a1a2e 50%, #16213e 100%);
            color: var(--text-color); 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            font-size: 1.1rem;
            margin: 0; 
            padding: 0;
            min-height: 100vh;
        }
        
        header { 
            background: var(--header-bg); 
            padding: 15px 0; 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }
        
        .nav-link { 
            color: #ddd !important; 
            margin: 0 10px; 
            text-decoration: none; 
            font-size: 1rem; 
            transition: all 0.3s ease;
            border-radius: 6px;
            padding: 8px 16px !important;
        }
        
        .nav-link:hover { 
            color: var(--primary-color) !important; 
            background: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }
        
        .container { 
            max-width: 600px; 
            margin: 30px auto; 
            padding: 20px;
        }
        
        .section { 
            background: var(--card-bg); 
            border-radius: 12px; 
            padding: 40px; 
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, var(--danger-color), #c82333);
            border: none; 
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-danger:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220,53,69,0.4);
            background: linear-gradient(135deg, #c82333, var(--danger-color));
        }
        
        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            background: transparent;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
        }
        
        .warning-icon {
            font-size: 4rem;
            color: var(--danger-color);
            margin-bottom: 20px;
        }
        
        .warning-card {
            background: rgba(220,53,69,0.1);
            border: 1px solid rgba(220,53,69,0.3);
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
            font-size: 1rem;
        }
        
        .warning-list {
            text-align: left;
            margin: 15px 0;
            font-size: 1rem;
        }
        
        .warning-list li {
            margin-bottom: 8px;
            color: #ff6b6b;
        }
        
        footer { 
            background: var(--header-bg); 
            padding: 20px; 
            text-align: center; 
            color: #aaa; 
            font-size: 0.9em;
            margin-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        @media (max-width: 768px) { 
            .container { padding: 15px; }
            .section { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="bi bi-controller me-2"></i>GamePlan Scheduler
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house me-1"></i>Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person me-1"></i>Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="friends.php"><i class="bi bi-people me-1"></i>Friends</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_schedule.php"><i class="bi bi-calendar-plus me-1"></i>Add Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="add_event.php"><i class="bi bi-calendar-event me-1"></i>Add Event</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <div class="container">
        <div class="section">
            <div class="warning-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            
            <h1 class="h2 mb-3">Delete <?php echo ucfirst($type); ?></h1>
            <p class="lead text-muted mb-4">
                You are about to permanently delete this <?php echo $type; ?>. This action cannot be undone.
            </p>
            
            <div class="warning-card">
                <h4 class="text-danger mb-3">
                    <i class="bi bi-exclamation-octagon me-2"></i>Warning: Irreversible Action
                </h4>
                <p class="mb-3">Please review the following consequences before proceeding:</p>
                
                <ul class="warning-list">
                    <?php if ($type === 'schedule'): ?>
                        <li>This gaming schedule will be permanently removed</li>
                        <li>Any linked events will lose their schedule association</li>
                        <li>Friends will no longer see this scheduled session</li>
                        <li>All associated data will be permanently deleted</li>
                    <?php elseif ($type === 'event'): ?>
                        <li>This event will be permanently removed</li>
                        <li>All event details and descriptions will be lost</li>
                        <li>Shared friends will no longer have access to this event</li>
                        <li>Any reminders for this event will be cancelled</li>
                    <?php endif; ?>
                </ul>
                
                <p class="mb-0 text-warning">
                    <i class="bi bi-lightbulb me-2"></i>
                    <strong>Note:</strong> Consider editing instead of deleting if you want to make changes.
                </p>
            </div>
            
            <form method="POST" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                    <button type="submit" class="btn btn-danger btn-lg px-5" 
                            onclick="this.innerHTML='<i class=\'bi bi-arrow-repeat spin me-2\'></i>Deleting...';this.disabled=true;">
                        <i class="bi bi-trash me-2"></i>Yes, Delete Permanently
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary btn-lg px-5">
                        <i class="bi bi-arrow-left me-2"></i>Cancel and Go Back
                    </a>
                </div>
                
                <div class="form-text text-muted mt-3">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    This action requires confirmation for security purposes.
                </div>
            </form>
            
            <!-- Safety Tips -->
            <div class="mt-5 p-4 rounded" style="background: rgba(255,255,255,0.05);">
                <h5 class="mb-3"><i class="bi bi-shield-check me-2"></i>Data Safety Tips</h5>
                <div class="row text-start">
                    <div class="col-md-6">
                        <ul class="text-muted small">
                            <li>Regularly backup important gaming schedules</li>
                            <li>Export your calendar data periodically</li>
                            <li>Use descriptive names for easy recovery</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="text-muted small">
                            <li>Consider archiving instead of deleting</li>
                            <li>Review deletion impacts before proceeding</li>
                            <li>Keep a record of important events</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            © 2025 GamePlan Scheduler by Harsha Kanaparthi | 
            <a href="#" style="color: #aaa;">Privacy Policy</a> | 
            <a href="#" style="color: #aaa;">Contact Support</a>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add spinning animation for the delete button
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .spin {
                animation: spin 1s linear infinite;
            }
        `;
        document.head.appendChild(style);
        
        // Prevent accidental form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            let submitted = false;
            
            form.addEventListener('submit', function(e) {
                if (submitted) {
                    e.preventDefault();
                    return;
                }
                
                // Additional confirmation for extra safety
                if (!confirm('FINAL WARNING: This will permanently delete the <?php echo $type; ?>. Are you absolutely sure?')) {
                    e.preventDefault();
                    return;
                }
                
                submitted = true;
            });
            
            // Add keyboard shortcut prevention
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'Enter' || e.key === 'Return')) {
                    e.preventDefault();
                    alert('Please use the mouse to confirm deletion for security reasons.');
                }
            });
        });
    </script>
</body>
</html>