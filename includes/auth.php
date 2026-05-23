<?php
/**
 * Authentication Functions
 * VMS - Pipe Manufacturing Company
 */

/**
 * Login user
 */
function loginUser($email, $password) {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT id, email, password, first_name, last_name, role_id, department_id,
               is_active, login_attempts, locked_until
        FROM users
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    // Check if account is active
    if (!$user['is_active']) {
        return ['success' => false, 'error' => 'Account is deactivated.'];
    }

    // Check if account is locked
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
        return ['success' => false, 'error' => "Account locked for {$remaining} minutes."];
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Increment login attempts
        $attempts = $user['login_attempts'] + 1;
        $lockUntil = null;

        if ($attempts >= 5) {
            $lockUntil = date('Y-m-d H:i:s', time() + 1800); // 30 minutes
        }

        $stmt = $db->prepare("
            UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?
        ");
        $stmt->execute([$attempts, $lockUntil, $user['id']]);

        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    // Reset login attempts
    $stmt = $db->prepare("
        UPDATE users
        SET login_attempts = 0, locked_until = NULL, last_login = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);

    // Regenerate session ID
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_role'] = $user['role_id'];
    $_SESSION['department_id'] = $user['department_id'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    // Log activity
    logActivity('LOGIN', 'User logged in successfully', $user['id']);

    return ['success' => true, 'user' => $user];
}

/**
 * Logout user
 */
function logoutUser() {
    // Log activity before destroying session
    if (isset($_SESSION['user_id'])) {
        logActivity('LOGOUT', 'User logged out', $_SESSION['user_id']);
    }

    // Destroy session
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        return false;
    }

    // Session timeout (30 minutes)
    if (time() - $_SESSION['login_time'] > 1800) {
        logoutUser();
        return false;
    }

    $_SESSION['login_time'] = time();
    return true;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please login to continue.';
        header('Location: ' . APP_URL . '/login.php');
        exit();
    }
}

/**
 * Create user
 */
function createUser($data) {
    $db = getDB();

    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already exists.'];
    }

    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    $stmt = $db->prepare("
        INSERT INTO users (
            first_name, last_name, email, password, phone, role_id, department_id,
            employee_id, is_active, created_at, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?)
    ");

    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $hashedPassword,
        $data['phone'] ?? null,
        $data['role_id'],
        $data['department_id'] ?? null,
        $data['employee_id'] ?? null,
        $_SESSION['user_id'] ?? null
    ]);

    $userId = $db->lastInsertId();

    logActivity('CREATE_USER', "Created user ID: $userId", $_SESSION['user_id'] ?? null);

    return ['success' => true, 'user_id' => $userId];
}

/**
 * Update user
 */
function updateUser($userId, $data) {
    $db = getDB();

    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'error' => 'User not found.'];
    }

    $updateFields = [];
    $updateValues = [];

    $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'role_id', 'department_id', 'employee_id', 'is_active'];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $updateValues[] = $data[$field];
        }
    }

    if (empty($updateFields)) {
        return ['success' => false, 'error' => 'No fields to update.'];
    }

    $updateFields[] = 'updated_at = NOW()';
    $updateFields[] = 'updated_by = ?';
    $updateValues[] = $_SESSION['user_id'] ?? null;
    $updateValues[] = $userId;

    $stmt = $db->prepare("
        UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?
    ");
    $stmt->execute($updateValues);

    logActivity('UPDATE_USER', "Updated user ID: $userId", $_SESSION['user_id'] ?? null);

    return ['success' => true];
}

/**
 * Change password
 */
function changePassword($userId, $currentPassword, $newPassword) {
    $db = getDB();

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'User not found.'];
    }

    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'error' => 'Current password is incorrect.'];
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);

    logActivity('CHANGE_PASSWORD', "Password changed for user ID: $userId", $userId);

    return ['success' => true];
}

/**
 * Reset password (admin)
 */
function resetPassword($userId, $newPassword) {
    $db = getDB();

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $db->prepare("
        UPDATE users
        SET password = ?, login_attempts = 0, locked_until = NULL, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$hashedPassword, $userId]);

    logActivity('RESET_PASSWORD', "Password reset for user ID: $userId", $_SESSION['user_id'] ?? null);

    return ['success' => true];
}

/**
 * Get all users
 */
function getUsers($filters = []) {
    $db = getDB();

    $sql = "
        SELECT u.*, r.name as role_name, d.name as department_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE 1=1
    ";

    $params = [];

    if (isset($filters['role_id'])) {
        $sql .= " AND u.role_id = ?";
        $params[] = $filters['role_id'];
    }

    if (isset($filters['department_id'])) {
        $sql .= " AND u.department_id = ?";
        $params[] = $filters['department_id'];
    }

    if (isset($filters['is_active'])) {
        $sql .= " AND u.is_active = ?";
        $params[] = $filters['is_active'];
    }

    if (isset($filters['search'])) {
        $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    $sql .= " ORDER BY u.first_name, u.last_name";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Get user by ID
 */
function getUser($userId) {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT u.*, r.name as role_name, d.name as department_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);

    return $stmt->fetch();
}

/**
 * Check if current user has a permission
 */
function hasPermission($permission) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT permissions FROM roles WHERE id = ?");
    $stmt->execute([$_SESSION['user_role']]);
    $perms = $stmt->fetchColumn();

    if (empty($perms)) {
        return false;
    }

    // permissions may be stored as JSON array or comma-separated string
    $decoded = json_decode($perms, true);
    if (is_array($decoded)) {
        return in_array($permission, $decoded, true);
    }

    $arr = array_map('trim', explode(',', $perms));
    return in_array($permission, $arr, true);
}

/**
 * Require a permission or abort with 403
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        http_response_code(403);
        die('Forbidden: insufficient permissions.');
    }
}
