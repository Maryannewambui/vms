<?php
/**
 * Database Configuration
 * VMS - Pipe Manufacturing Company
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'vms_pipe_manufacturing');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'PipeVMS');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/vms');
define('COMPANY_NAME', 'Precision Pipe Manufacturing Co.');

// Security settings
define('HASH_ALGO', 'sha256');
define('SESSION_NAME', 'VMS_SESSION');
define('CSRF_TOKEN_NAME', 'csrf_token');

// Date/Time settings
define('TIMEZONE', 'UTC');
date_default_timezone_set(TIMEZONE);

// Upload settings
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Pagination
define('RECORDS_PER_PAGE', 20);

/**
 * Database Connection Class
 */
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please contact administrator.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Get database connection
 */
function getDB() {
    return Database::getInstance()->getConnection();
}
