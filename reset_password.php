<?php
/**
 * Reset Password Page
 * VMS - Pipe Manufacturing Company
 * Handles password reset after token validation
 */

require_once 'config/config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('/index.php');
}

$error = '';
$message = '';
$step = 'validate'; // validate, reset, or success
$user = null;

// Get token from URL
$token = sanitize($_GET['token'] ?? '');

if (empty($token)) {
    $error = 'Invalid or missing reset token.';
    $step = 'error';
} else {
    // Validate token
    $db = getDB();
    $stmt = $db->prepare("
        SELECT id, first_name, email FROM users 
        WHERE password_reset_token = ? 
        AND password_reset_expires > NOW()
        AND is_active = 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'This password reset link has expired or is invalid. Please request a new one.';
        $step = 'error';
    }
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed. Please try again.';
    } elseif (empty($_POST['password']) || empty($_POST['password_confirm'])) {
        $error = 'Password fields are required.';
    } elseif ($_POST['password'] !== $_POST['password_confirm']) {
        $error = 'Passwords do not match.';
    } elseif (strlen($_POST['password']) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        // Hash password and update
        $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE users 
            SET password = ?, password_reset_token = NULL, password_reset_expires = NULL
            WHERE id = ? AND password_reset_token = ?
        ");
        $result = $stmt->execute([$hashedPassword, $user['id'], $token]);

        if ($result) {
            logActivity('PASSWORD_RESET_SUCCESS', 'Password successfully reset', $user['id']);
            $step = 'success';
            $message = 'Password reset successfully! You can now log in with your new password.';
        } else {
            $error = 'An error occurred while resetting your password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - PipeVMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }
        .pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="min-h-screen flex bg-slate-50">
    <!-- Left Panel - Branding -->
    <div class="hidden lg:flex lg:flex-1 gradient-bg pattern items-center justify-center">
        <div class="max-w-md text-center text-white p-8">
            <svg class="w-24 h-24 mx-auto opacity-90 mb-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            <h1 class="text-4xl font-bold mb-2">PipeVMS</h1>
            <p class="text-lg opacity-90 mb-4">Visitor Management System</p>
            <p class="text-sm opacity-75">Precision Pipe Manufacturing Co.</p>
        </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="flex-1 flex items-center justify-center p-8">
        <div class="w-full max-w-md">
            <!-- Mobile Logo -->
            <div class="lg:hidden text-center mb-8">
                <svg class="w-16 h-16 mx-auto text-primary-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <h1 class="text-2xl font-bold text-slate-800 mt-4">PipeVMS</h1>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-slate-800">Reset Password</h2>
                    <?php if ($step === 'validate' || $step === 'reset'): ?>
                        <p class="text-slate-500 text-sm mt-2">Enter your new password below</p>
                    <?php endif; ?>
                </div>

                <!-- Error Message -->
                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-red-700">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Success Message -->
                <?php if ($step === 'success'): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-green-700">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <a href="login.php" class="inline-block py-3 px-6 bg-primary-500 text-white font-medium rounded-lg hover:bg-primary-600 transition-colors">
                        Go to Login
                    </a>
                </div>
                <?php elseif ($step === 'error'): ?>
                <div class="text-center">
                    <a href="forgot_password.php" class="inline-block py-3 px-6 bg-primary-500 text-white font-medium rounded-lg hover:bg-primary-600 transition-colors">
                        Request New Reset Link
                    </a>
                </div>
                <?php else: ?>
                <!-- Password Reset Form -->
                <form method="POST" action="" class="space-y-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">New Password</label>
                        <input type="password" 
                               name="password" 
                               placeholder="Min 8 characters"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               required>
                        <p class="text-xs text-slate-500 mt-1">At least 8 characters</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Confirm Password</label>
                        <input type="password" 
                               name="password_confirm" 
                               placeholder="Confirm password"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               required>
                    </div>

                    <button type="submit" 
                            class="w-full py-3 px-4 bg-primary-500 text-white font-medium rounded-lg hover:bg-primary-600 focus:ring-4 focus:ring-primary-200 transition-colors">
                        Reset Password
                    </button>
                </form>
                <?php endif; ?>

                <!-- Back Link -->
                <?php if ($step !== 'success'): ?>
                <div class="text-center mt-6">
                    <a href="login.php" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                        ← Back to Login
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
