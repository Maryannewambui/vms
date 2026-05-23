<?php
/**
 * Forgot Password Page
 * VMS - Pipe Manufacturing Company
 */

require_once 'config/config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('/index.php');
}

$message = '';
$error = '';
$step = 'email'; // email or reset

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'request') {
        // Step 1: Request password reset
        $email = sanitize($_POST['email'] ?? '');

        if (empty($email)) {
            $error = 'Please enter your email address.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, first_name FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate reset token
                $resetToken = bin2hex(random_bytes(32));
                $resetExpires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                $stmt = $db->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
                $stmt->execute([$resetToken, $resetExpires, $user['id']]);

                // In production, send email with reset link
                // For now, show the reset link (development only)
                $resetLink = APP_URL . '/reset_password.php?token=' . $resetToken;
                $message = "Password reset link has been generated. In production, this would be sent to your email.<br><br>";
                $message .= "<strong>For testing:</strong> <a href='{$resetLink}' target='_blank'>Click here to reset password</a>";

                logActivity('PASSWORD_RESET_REQUEST', "Password reset requested for: $email", $user['id']);
            } else {
                // Don't reveal if email exists (security best practice)
                $message = "If an account exists with this email, a password reset link has been sent.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PipeVMS</title>
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
                    <h2 class="text-2xl font-bold text-slate-800">Forgot Password?</h2>
                    <p class="text-slate-500 text-sm mt-2">Enter your email address to receive a password reset link</p>
                </div>

                <?php if ($message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-green-700">
                            <?= $message ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm text-red-700"><?= $error ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$message): ?>
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Email Address</label>
                        <input type="email" 
                               name="email" 
                               placeholder="your@email.com"
                               class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                               required>
                    </div>

                    <button type="submit" 
                            name="action" 
                            value="request"
                            class="w-full py-3 px-4 bg-primary-500 text-white font-medium rounded-lg hover:bg-primary-600 focus:ring-4 focus:ring-primary-200 transition-colors">
                        Send Reset Link
                    </button>
                </form>
                <?php endif; ?>

                <div class="text-center mt-6">
                    <a href="login.php" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                        ← Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
