<?php
// add_event.php - Advanced Event Creation
// Author: Harsha Kanaparthi
// Date: 30-09-2025

require_once 'functions.php';
checkSessionTimeout();

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = getUserId();
$schedules = getAvailableSchedules($userId);
$friends = getFriends($userId);
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $eventTitle = $_POST['event_title'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $description = $_POST['description'] ?? '';
    $reminder = $_POST['reminder'] ?? 'none';
    $externalLink = $_POST['external_link'] ?? '';
    $scheduleId = $_POST['schedule_id'] ?? null;
    $sharedWith = $_POST['shared_with'] ?? '';
    
    $error = addEvent($userId, $eventTitle, $date, $time, $description, $reminder, $externalLink, $scheduleId, $sharedWith);
    if (!$error) {
        setMessage('success', 'Event created successfully!');
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
                    <h1 class="h2 mb-1">Create New Event</h1>
                    <p class="text-muted">Organize gaming events, tournaments, and gatherings</p>
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
                        <i class="fas fa-calendar-alt me-2"></i>Event Details
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo safeEcho($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return validateEventForm()">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="event_title" class="form-label fw-semibold">Event Title *</label>
                                <input type="text" 
                                       id="event_title" 
                                       name="event_title" 
                                       class="form-control form-control-advanced real-time-validate" 
                                       required 
                                       maxlength="100"
                                       placeholder="Enter event title (e.g., Fortnite Tournament, Minecraft Build Competition)"
                                       value="<?php echo safeEcho($_POST['event_title'] ?? ''); ?>">
                                <div class="form-text">A clear and descriptive title for your event</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="reminder" class="form-label fw-semibold">Reminder</label>
                                <select id="reminder" 
                                        name="reminder" 
                                        class="form-select form-control-advanced">
                                    <option value="none">No Reminder</option>
                                    <option value="15_minutes">15 Minutes Before</option>
                                    <option value="1_hour">1 Hour Before</option>
                                    <option value="1_day">1 Day Before</option>
                                    <option value="1_week">1 Week Before</option>
                                </select>
                                <div class="form-text">Get notified before the event</div>
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
                                <div class="form-text">When is your event happening?</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="time" class="form-label fw-semibold">Time *</label>
                                <input type="time" 
                                       id="time" 
                                       name="time" 
                                       class="form-control form-control-advanced" 
                                       required
                                       value="<?php echo safeEcho($_POST['time'] ?? '20:00'); ?>">
                                <div class="form-text">What time does your event start?</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Event Description</label>
                            <textarea id="description" 
                                      name="description" 
                                      class="form-control form-control-advanced" 
                                      rows="5"
                                      maxlength="500"
                                      placeholder="Describe your event in detail (rules, prizes, requirements, etc.)"><?php echo safeEcho($_POST['description'] ?? ''); ?></textarea>
                            <div class="form-text">Maximum 500 characters. Be clear about event details.</div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted" id="charCount">0/500 characters</small>
                                <small class="text-muted">Markdown supported</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="schedule_id" class="form-label fw-semibold">Link to Schedule (Optional)</label>
                                <select id="schedule_id" 
                                        name="schedule_id" 
                                        class="form-select form-control-advanced">
                                    <option value="">No Schedule Link</option>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <option value="<?php echo $schedule['schedule_id']; ?>">
                                            <?php echo safeEcho($schedule['schedule_title']); ?> (<?php echo $schedule['date']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Link this event to an existing schedule</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="external_link" class="form-label fw-semibold">External Link</label>
                                <input type="url" 
                                       id="external_link" 
                                       name="external_link" 
                                       class="form-control form-control-advanced" 
                                       placeholder="https://example.com/tournament-info"
                                       value="<?php echo safeEcho($_POST['external_link'] ?? ''); ?>">
                                <div class="form-text">Link to tournament page, Discord, etc.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="shared_with" class="form-label fw-semibold">Share With Participants</label>
                            <textarea id="shared_with" 
                                      name="shared_with" 
                                      class="form-control form-control-advanced" 
                                      rows="3"
                                      placeholder="Enter usernames separated by commas (e.g., player1, player2, player3)"><?php echo safeEcho($_POST['shared_with'] ?? ''); ?></textarea>
                            <div class="form-text">Who should see and participate in this event?</div>
                        </div>

                        <!-- Event Type Selection -->
                        <div class="advanced-card bg-secondary mb-4">
                            <div class="card-body">
                                <h6 class="mb-3">
                                    <i class="fas fa-tags me-2"></i>Event Type
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-3 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="event_type" id="tournament" value="tournament" checked>
                                            <label class="form-check-label" for="tournament">
                                                <i class="fas fa-trophy me-1"></i>Tournament
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="event_type" id="practice" value="practice">
                                            <label class="form-check-label" for="practice">
                                                <i class="fas fa-dumbbell me-1"></i>Practice
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="event_type" id="social" value="social">
                                            <label class="form-check-label" for="social">
                                                <i class="fas fa-users me-1"></i>Social
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="event_type" id="other" value="other">
                                            <label class="form-check-label" for="other">
                                                <i class="fas fa-star me-1"></i>Other
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-secondary me-md-2">
                                <i class="fas fa-undo me-1"></i>Reset Form
                            </button>
                            <button type="submit" class="btn btn-primary btn-advanced">
                                <i class="fas fa-calendar-plus me-2"></i>Create Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Event Preview -->
            <div class="advanced-card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-eye me-2"></i>Event Preview
                    </h6>
                </div>
                <div class="card-body">
                    <div id="eventPreview" class="text-muted">
                        <p class="mb-2">Your event will appear here as you fill out the form...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validateEventForm() {
    const eventTitle = document.getElementById('event_title').value.trim();
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;

    if (!eventTitle) {
        alert('Please enter an event title.');
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

    // Validate URL if provided
    const externalLink = document.getElementById('external_link').value;
    if (externalLink && !isValidUrl(externalLink)) {
        alert('Please enter a valid URL for the external link.');
        return false;
    }

    return true;
}

function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

function updatePreview() {
    const eventTitle = document.getElementById('event_title').value || 'Event Title';
    const date = document.getElementById('date').value || 'YYYY-MM-DD';
    const time = document.getElementById('time').value || 'HH:MM';
    const description = document.getElementById('description').value || 'No description provided.';
    const reminder = document.getElementById('reminder').value;
    const externalLink = document.getElementById('external_link').value;
    
    const formattedDate = date !== 'YYYY-MM-DD' ? new Date(date).toLocaleDateString('en-US', { 
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
    }) : 'Date not set';
    
    const formattedTime = time !== 'HH:MM' ? new Date('2000-01-01T' + time).toLocaleTimeString('en-US', { 
        hour: 'numeric', minute: 'numeric', hour12: true 
    }) : 'Time not set';

    let reminderText = '';
    if (reminder !== 'none') {
        reminderText = `<p class="mb-1">
            <i class="fas fa-bell me-1 text-warning"></i>
            Reminder: ${reminder.replace('_', ' ')} before
        </p>`;
    }

    let linkText = '';
    if (externalLink) {
        linkText = `<p class="mb-1">
            <i class="fas fa-link me-1 text-info"></i>
            <a href="${externalLink}" target="_blank">Event Details</a>
        </p>`;
    }

    const preview = `
        <div class="calendar-item event">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>${eventTitle}
                </h6>
                <span class="badge bg-success">Event</span>
            </div>
            <p class="mb-1">
                <i class="fas fa-clock me-1 text-muted"></i>${formattedDate} at ${formattedTime}
            </p>
            ${reminderText}
            ${linkText}
            <p class="mb-0 text-muted small">${description}</p>
        </div>
    `;
    
    document.getElementById('eventPreview').innerHTML = preview;
}

function updateCharCount() {
    const textarea = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    const count = textarea.value.length;
    charCount.textContent = `${count}/500 characters`;
    
    if (count > 500) {
        charCount.classList.add('text-danger');
    } else {
        charCount.classList.remove('text-danger');
    }
}

// Update preview and character count when form fields change
document.addEventListener('DOMContentLoaded', function() {
    const formFields = ['event_title', 'date', 'time', 'description', 'reminder', 'external_link'];
    formFields.forEach(field => {
        document.getElementById(field).addEventListener('input', updatePreview);
    });
    
    document.getElementById('description').addEventListener('input', updateCharCount);
    
    // Initial preview and character count
    updatePreview();
    updateCharCount();
});
</script>

<?php include 'footer.php'; ?>