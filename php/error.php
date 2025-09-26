<?php
// GamePlan Scheduler - Custom Error Pages
// Professional error handling with user-friendly messages

$errorCode = (int)($_GET['code'] ?? 404);
$errorMessages = [
    400 => [
        'title' => 'Bad Request',
        'message' => 'The server could not understand your request. Please check your input and try again.',
        'icon' => 'fas fa-exclamation-triangle',
        'color' => 'warning'
    ],
    401 => [
        'title' => 'Unauthorized',
        'message' => 'You need to be logged in to access this page.',
        'icon' => 'fas fa-lock',
        'color' => 'danger',
        'action' => '<a href="login.php" class="btn btn-primary">Login</a>'
    ],
    403 => [
        'title' => 'Forbidden',
        'message' => 'You don\'t have permission to access this resource.',
        'icon' => 'fas fa-ban',
        'color' => 'danger'
    ],
    404 => [
        'title' => 'Page Not Found',
        'message' => 'The page you\'re looking for doesn\'t exist or has been moved.',
        'icon' => 'fas fa-search',
        'color' => 'secondary'
    ],
    500 => [
        'title' => 'Internal Server Error',
        'message' => 'Something went wrong on our end. Please try again later.',
        'icon' => 'fas fa-server',
        'color' => 'danger'
    ]
];

$error = $errorMessages[$errorCode] ?? $errorMessages[404];

// Log error for debugging (in production, use proper logging)
if ($errorCode >= 500) {
    error_log("HTTP $errorCode Error: " . $_SERVER['REQUEST_URI'] . " - " . $_SERVER['HTTP_USER_AGENT']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GamePlan Scheduler - <?php echo htmlspecialchars($error['title']); ?></title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta http-equiv="refresh" content="30;url=index.php"> <!-- Auto-redirect after 30 seconds -->
</head>
<body class="bg-dark text-white d-flex justify-content-center align-items-center vh-100">
    <div class="text-center">
        <div class="mb-4">
            <i class="<?php echo $error['icon']; ?> fa-4x text-<?php echo $error['color']; ?> mb-3"></i>
            <h1 class="display-1 text-<?php echo $error['color']; ?>"><?php echo $errorCode; ?></h1>
        </div>

        <h2 class="card-title mb-3"><?php echo htmlspecialchars($error['title']); ?></h2>
        <p class="card-text text-muted mb-4"><?php echo htmlspecialchars($error['message']); ?></p>

        <?php if (isset($error['action'])): ?>
            <?php echo $error['action']; ?>
        <?php else: ?>
            <div class="d-grid gap-2">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Go Home
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Go Back
                </button>
            </div>
        <?php endif; ?>

        <hr class="my-4">

        <div class="text-muted small">
            <p class="mb-1">Error <?php echo $errorCode; ?> occurred at <?php echo date('Y-m-d H:i:s'); ?></p>
            <p class="mb-0">You will be redirected to the home page in 30 seconds.</p>
        </div>
    </div>

    <!-- Additional help for common errors -->
    <?php if ($errorCode === 404): ?>
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Popular Pages</h6>
                <div class="row text-center">
                    <div class="col-6">
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-home fa-2x text-primary mb-2"></i>
                            <p class="small">Dashboard</p>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-sign-in-alt fa-2x text-success mb-2"></i>
                            <p class="small">Login</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Countdown timer for auto-redirect
        let countdown = 30;
        const countdownElement = document.querySelector('p:contains("30 seconds")');

        if (countdownElement) {
            const timer = setInterval(() => {
                countdown--;
                countdownElement.textContent = `You will be redirected to the home page in ${countdown} seconds.`;

                if (countdown <= 0) {
                    clearInterval(timer);
                    window.location.href = 'index.php';
                }
            }, 1000);
        }

        // Add some visual feedback
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.card');
            card.style.animation = 'fadeInUp 0.6s ease-out';
        });
    </script>
</body>
</html>