<?php
// add_schedule.php - Advanced Schedule Creation
// Author: Harsha Kanaparthi
// Date: 30-09-2025

require_once 'functions.php';
checkSessionTimeout();

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$friends = getFriends($userId);
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gameTitle = $_POST['game_title'] ?? '';
    $scheduleTitle = $_POST['schedule_title'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = $_POST['description'] ?? '';
    $friendsList = $_POST['friends_list'] ?? '';
    $sharedWith = $_POST['shared_with'] ?? '';
    
    $error = addSchedule($userId, $gameTitle, $scheduleTitle, $date, $time, $description, $friendsList, $sharedWith);
    if (!$error) {
        setMessage('success', 'Schedule created successfully!');
        header("Location: index.php");
        exit;
    }
}
?>
<?php include 'header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-1">Create New Schedule</h1>
                    <p class="text-muted">Plan your gaming sessions and share with friends</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="advanced-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>Schedule Details
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo safeEcho($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return validateScheduleForm()">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="game_title" class="form-label fw-semibold">Game Title *</label>
                                <input type="text" 
                                       id="game_title" 
                                       name="game_title" 
                                       class="form-control form-control-advanced real-time-validate" 
                                       required 
                                       maxlength="100"
                                       placeholder="Enter game name (e.g., Fortnite, Call of Duty)"
                                       value="<?php echo safeEcho($_POST['game_title'] ?? ''); ?>"
                                       list="game-suggestions">
                                <datalist id="game-suggestions">
                                    <option value="Fortnite">
                                    <option value="Minecraft">
                                    <option value="League of Legends">
                                    <option value="Valorant">
                                    <option value="Call of Duty: Warzone">
                                    <option value="Apex Legends">
                                    <option value="Rocket League">
                                    <option value="Among Us">
                                    <option value="Genshin Impact">
                                    <option value="Roblox">
                                </datalist>
                                <div class="form-text">Start typing to see game suggestions</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="schedule_title" class="form-label fw-semibold">Schedule Title *</label>
                                <input type="text" 
                                       id="schedule_title" 
                                       name="schedule_title" 
                                       class="form-control form-control-advanced real-time-validate" 
                                       required 
                                       maxlength="100"
                                       placeholder="Give this schedule a title (e.g., Weekend Gaming, Tournament Practice)"
                                       value="<?php echo safeEcho($_POST['schedule_title'] ?? ''); ?>">
                                <div class="form-text">A descriptive title for your schedule</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date" class="form-label fw-semibold">Date *</label>
                                <input type="date" 
                                       id="date" 
                                       name="date" 
                                       class="form-control form-control-advanced" 
                                       required 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo safeEcho($_POST['date'] ?? ''); ?>">
                                <div class="form-text">Select the date for your gaming session</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="time" class="form-label fw-semibold">Time *</label>
                                <input type="time" 
                                       id="time" 
                                       name="time" 
                                       class="form-control form-control-advanced" 
                                       required
                                       value="<?php echo safeEcho($_POST['time'] ?? '19:00'); ?>">
                                <div class="form-text">When does your gaming session start?</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea id="description" 
                                      name="description" 
                                      class="form-control form-control-advanced" 
                                      rows="4"
                                      placeholder="Describe your gaming session (objectives, rules, etc.)"><?php echo safeEcho($_POST['description'] ?? ''); ?></textarea>
                            <div class="form-text">What are you planning to do in this session?</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="friends_list" class="form-label fw-semibold">Invite Friends</label>
                                <select id="friends_list" 
                                        name="friends_list" 
                                        class="form-select form-control-advanced" 
                                        multiple
                                        style="height: 120px;">
                                    <?php foreach ($friends as $friend): ?>
                                        <option value="<?php echo safeEcho($friend['username']); ?>">
                                            <?php echo safeEcho($friend['username']); ?> (<?php echo $friend['status']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple friends</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="shared_with" class="form-label fw-semibold">Share With (Usernames)</label>
                                <textarea id="shared_with" 
                                          name="shared_with" 
                                          class="form-control form-control-advanced" 
                                          rows="4"
                                          placeholder="Enter usernames separated by commas (e.g., player1, player2, player3)"><?php echo safeEcho($_POST['shared_with'] ?? ''); ?></textarea>
                                <div class="form-text">Additional usernames to share this schedule with</div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="advanced-card bg-secondary mb-4">
                            <div class="card-body py-3">
                                <h6 class="mb-3">
                                    <i class="fas fa-bolt me-2"></i>Quick Schedule Templates
                                </h6>
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-sm btn-outline-light" onclick="applyTemplate('weekend')">
                                            Weekend Gaming
                                        </button>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-sm btn-outline-light" onclick="applyTemplate('tournament')">
                                            Tournament Practice
                                        </button>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-sm btn-outline-light" onclick="applyTemplate('casual')">
                                            Casual Session
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-secondary me-md-2">
                                <i class="fas fa-undo me-1"></i>Reset Form
                            </button>
                            <button type="submit" class="btn btn-primary btn-advanced">
                                <i class="fas fa-calendar-plus me-2"></i>Create Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Schedule Preview -->
            <div class="advanced-card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-eye me-2"></i>Schedule Preview
                    </h6>
                </div>
                <div class="card-body">
                    <div id="schedulePreview" class="text-muted">
                        <p class="mb-2">Your schedule will appear here as you fill out the form...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validateScheduleForm() {
    const gameTitle = document.getElementById('game_title').value.trim();
    const scheduleTitle = document.getElementById('schedule_title').value.trim();
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;

    if (!gameTitle) {
        alert('Please enter a game title.');
        return false;
    }

    if (!scheduleTitle) {
        alert('Please enter a schedule title.');
        return false;
    }

    if (!date) {
        alert('Please select a date.');
        return false;
    }

    if (!time) {
        alert('Please select a time.');
        return false;
    }

    // Validate date is not in the past
    const selectedDate = new Date(date + 'T' + time);
    if (selectedDate < new Date()) {
        alert('Please select a date and time in the future.');
        return false;
    }

    return true;
}

function applyTemplate(template) {
    const templates = {
        'weekend': {
            game_title: 'Fortnite',
            schedule_title: 'Weekend Gaming Session',
            description: 'Casual weekend gaming session. Let\'s have fun and play some games together!',
            friends_list: ['Select all online friends']
        },
        'tournament': {
            game_title: 'Valorant',
            schedule_title: 'Tournament Practice',
            description: 'Serious practice session for upcoming tournament. Focus on strategy and teamwork.',
            friends_list: ['Select competitive friends']
        },
        'casual': {
            game_title: 'Minecraft',
            schedule_title: 'Casual Building Session',
            description: 'Relaxed building and exploration in our Minecraft world.',
            friends_list: ['Select all friends']
        }
    };

    const selected = templates[template];
    if (selected) {
        document.getElementById('game_title').value = selected.game_title;
        document.getElementById('schedule_title').value = selected.schedule_title;
        document.getElementById('description').value = selected.description;
        
        updatePreview();
    }
}

function updatePreview() {
    const gameTitle = document.getElementById('game_title').value || 'Game Title';
    const scheduleTitle = document.getElementById('schedule_title').value || 'Schedule Title';
    const date = document.getElementById('date').value || 'YYYY-MM-DD';
    const time = document.getElementById('time').value || 'HH:MM';
    const description = document.getElementById('description').value || 'No description provided.';
    
    const formattedDate = date !== 'YYYY-MM-DD' ? new Date(date).toLocaleDateString('en-US', { 
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
    }) : 'Date not set';
    
    const formattedTime = time !== 'HH:MM' ? new Date('2000-01-01T' + time).toLocaleTimeString('en-US', { 
        hour: 'numeric', minute: 'numeric', hour12: true 
    }) : 'Time not set';

    const preview = `
        <div class="calendar-item schedule">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="mb-0">
                    <i class="fas fa-calendar-day me-2"></i>${scheduleTitle}
                </h6>
                <span class="badge bg-primary">Schedule</span>
            </div>
            <p class="mb-1">
                <i class="fas fa-gamepad me-1 text-muted"></i>${gameTitle}
            </p>
            <p class="mb-1">
                <i class="fas fa-clock me-1 text-muted"></i>${formattedDate} at ${formattedTime}
            </p>
            <p class="mb-0 text-muted small">${description}</p>
        </div>
    `;
    
    document.getElementById('schedulePreview').innerHTML = preview;
}

// Update preview when form fields change
document.addEventListener('DOMContentLoaded', function() {
    const formFields = ['game_title', 'schedule_title', 'date', 'time', 'description'];
    formFields.forEach(field => {
        document.getElementById(field).addEventListener('input', updatePreview);
    });
    
    // Initial preview
    updatePreview();
});
</script>

<?php include 'footer.php'; ?>