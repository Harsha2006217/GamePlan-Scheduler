<?php
require_once 'functions.php';
require_once 'template_functions.php';

session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$templates = getUserTemplates($user_id);
$games = getGames();

// Get any success/error messages from session
$success_msg = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : '';
$error_msg = isset($_SESSION['error_msg']) ? $_SESSION['error_msg'] : '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Templates - GamePlan</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main class="container">
        <h1>Schedule Templates</h1>
        
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        
        <div class="action-bar">
            <button class="btn btn-primary" onclick="location.href='template_form.php'">
                Create New Template
            </button>
        </div>
        
        <?php if (empty($templates)): ?>
            <div class="empty-state">
                <p>You haven't created any schedule templates yet.</p>
                <p>Templates help you create recurring game sessions easily!</p>
            </div>
        <?php else: ?>
            <div class="templates-grid">
                <?php foreach ($templates as $template): ?>
                    <div class="template-card">
                        <div class="template-header">
                            <img src="<?php echo htmlspecialchars($template['game_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($template['game_name']); ?>"
                                 class="game-image">
                            <h3><?php echo htmlspecialchars($template['name']); ?></h3>
                        </div>
                        
                        <div class="template-info">
                            <p><strong>Game:</strong> <?php echo htmlspecialchars($template['game_name']); ?></p>
                            <p><strong>Time:</strong> <?php echo htmlspecialchars(date('g:i A', strtotime($template['time']))); ?></p>
                            <p><strong>Duration:</strong> <?php echo htmlspecialchars($template['duration']); ?> minutes</p>
                            <p><strong>Pattern:</strong> <?php echo htmlspecialchars(ucfirst($template['recurring_pattern'])); ?></p>
                            
                            <?php if ($template['recurring_pattern'] === 'weekly' || $template['recurring_pattern'] === 'biweekly'): ?>
                                <p><strong>Days:</strong> 
                                    <?php 
                                    $days = array_map('ucfirst', explode(',', $template['weekdays']));
                                    echo htmlspecialchars(implode(', ', $days));
                                    ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($template['recurring_pattern'] === 'monthly'): ?>
                                <p><strong>Day of Month:</strong> <?php echo htmlspecialchars($template['monthly_day']); ?></p>
                            <?php endif; ?>
                            
                            <p><strong>Friends Invited:</strong> <?php echo htmlspecialchars($template['invited_friends']); ?></p>
                            <p><strong>Schedules Generated:</strong> <?php echo htmlspecialchars($template['schedules_generated']); ?></p>
                        </div>
                        
                        <div class="template-actions">
                            <a href="template_form.php?id=<?php echo urlencode($template['template_id']); ?>" 
                               class="btn btn-secondary">Edit</a>
                            <a href="generate_schedules.php?id=<?php echo urlencode($template['template_id']); ?>" 
                               class="btn btn-primary">Generate Schedules</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'footer.php'; ?>
    <script src="../js/script.js"></script>
</body>
</html>