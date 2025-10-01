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
$template_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$template_id) {
    $_SESSION['error_msg'] = "Template ID is required";
    header('Location: templates.php');
    exit;
}

// Get template details
$template = getTemplateDetails($template_id, $user_id);
if (!$template) {
    $_SESSION['error_msg'] = "Template not found or access denied";
    header('Location: templates.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $schedules = generateSchedulesFromTemplate($template_id, $start_date, $end_date);
    
    if ($schedules === false) {
        $_SESSION['error_msg'] = "Failed to generate schedules";
    } else {
        $_SESSION['success_msg'] = count($schedules) . " schedules generated successfully";
        header('Location: templates.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Schedules - GamePlan</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main class="container">
        <h1>Generate Schedules from Template</h1>
        
        <div class="template-info">
            <h2><?php echo htmlspecialchars($template['name']); ?></h2>
            <p><strong>Game:</strong> <?php echo htmlspecialchars($template['game_name']); ?></p>
            <p><strong>Time:</strong> <?php echo htmlspecialchars(date('g:i A', strtotime($template['time']))); ?></p>
            <p><strong>Pattern:</strong> <?php echo htmlspecialchars(ucfirst($template['recurring_pattern'])); ?></p>
        </div>
        
        <form method="post" class="form-medium">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required
                       min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" required
                       min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="preview-section" style="display: none;">
                <h3>Preview of Generated Dates</h3>
                <div id="dates-preview" class="dates-list"></div>
                <p class="preview-count">Total schedules to generate: <span id="dates-count">0</span></p>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" id="preview-btn">Preview Dates</button>
                <button type="submit" class="btn btn-primary">Generate Schedules</button>
                <a href="templates.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
    
    <?php include 'footer.php'; ?>
    <script>
        const template = <?php echo json_encode($template); ?>;
        
        function calculateDates(startDate, endDate) {
            const dates = [];
            const current = new Date(startDate);
            const end = new Date(endDate);
            
            while (current <= end) {
                let shouldGenerate = false;
                const weekday = current.toLocaleDateString('en-US', { weekday: 'lowercase' });
                const dayOfMonth = current.getDate();
                const weekNumber = getWeekNumber(current);
                
                switch (template.recurring_pattern) {
                    case 'daily':
                        shouldGenerate = true;
                        break;
                        
                    case 'weekly':
                        shouldGenerate = template.weekdays.split(',').includes(weekday);
                        break;
                        
                    case 'biweekly':
                        shouldGenerate = template.weekdays.split(',').includes(weekday) && 
                                       weekNumber % 2 === 0;
                        break;
                        
                    case 'monthly':
                        shouldGenerate = dayOfMonth === parseInt(template.monthly_day);
                        break;
                }
                
                if (shouldGenerate) {
                    dates.push(new Date(current));
                }
                
                current.setDate(current.getDate() + 1);
            }
            
            return dates;
        }
        
        function getWeekNumber(date) {
            const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
            const dayNum = d.getUTCDay() || 7;
            d.setUTCDate(d.getUTCDate() + 4 - dayNum);
            const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
            return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
        }
        
        function formatDate(date) {
            return date.toLocaleDateString('en-US', { 
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        function updatePreview() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (!startDate || !endDate) return;
            
            const dates = calculateDates(startDate, endDate);
            const previewSection = document.querySelector('.preview-section');
            const previewList = document.getElementById('dates-preview');
            const countElement = document.getElementById('dates-count');
            
            previewList.innerHTML = dates
                .map(date => `<div class="date-item">${formatDate(date)}</div>`)
                .join('');
            
            countElement.textContent = dates.length;
            previewSection.style.display = 'block';
        }
        
        document.getElementById('preview-btn').addEventListener('click', updatePreview);
        
        // Validate date range
        document.querySelector('form').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (endDate < startDate) {
                e.preventDefault();
                alert('End date must be after start date');
            }
            
            const maxRange = new Date();
            maxRange.setMonth(maxRange.getMonth() + 6);
            
            if (endDate > maxRange) {
                e.preventDefault();
                alert('Cannot generate schedules more than 6 months in advance');
            }
        });
    </script>
</body>
</html>