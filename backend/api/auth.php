<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

class AuthAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function register() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->company_id) || !isset($data->first_name) || 
            !isset($data->last_name) || !isset($data->email) || 
            !isset($data->password) || !isset($data->department)) {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
            return;
        }
        
        // Check if company_id or email already exists
        $check_query = "SELECT id FROM users WHERE company_id = :company_id OR email = :email";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(":company_id", $data->company_id);
        $check_stmt->bindParam(":email", $data->email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(array("message" => "Company ID or Email already exists"));
            return;
        }
        
        $query = "INSERT INTO users (company_id, first_name, last_name, email, password_hash, department) 
                  VALUES (:company_id, :first_name, :last_name, :email, :password_hash, :department)";
        
        $stmt = $this->conn->prepare($query);
        
        $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
        
        $stmt->bindParam(":company_id", $data->company_id);
        $stmt->bindParam(":first_name", $data->first_name);
        $stmt->bindParam(":last_name", $data->last_name);
        $stmt->bindParam(":email", $data->email);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":department", $data->department);
        
        if ($stmt->execute()) {
            $user_id = $this->conn->lastInsertId();
            
            // Add user to general chat group
            $chat_query = "INSERT INTO chat_group_members (group_id, user_id) VALUES (1, :user_id)";
            $chat_stmt = $this->conn->prepare($chat_query);
            $chat_stmt->bindParam(":user_id", $user_id);
            $chat_stmt->execute();
            
            http_response_code(201);
            echo json_encode(array("message" => "User registered successfully", "user_id" => $user_id));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to register user"));
        }
    }
    
    public function login() {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->company_id) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode(array("message" => "Company ID and password required"));
            return;
        }
        
        $query = "SELECT id, company_id, first_name, last_name, email, password_hash, department, role 
                  FROM users WHERE company_id = :company_id AND is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":company_id", $data->company_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($data->password, $row['password_hash'])) {
                // Generate session token
                $session_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Store session
                $session_query = "INSERT INTO user_sessions (user_id, session_token, expires_at) 
                                  VALUES (:user_id, :session_token, :expires_at)";
                $session_stmt = $this->conn->prepare($session_query);
                $session_stmt->bindParam(":user_id", $row['id']);
                $session_stmt->bindParam(":session_token", $session_token);
                $session_stmt->bindParam(":expires_at", $expires_at);
                $session_stmt->execute();
                
                // Update last login
                $login_update = "UPDATE users SET last_login = NOW() WHERE id = :user_id";
                $login_stmt = $this->conn->prepare($login_update);
                $login_stmt->bindParam(":user_id", $row['id']);
                $login_stmt->execute();
                
                unset($row['password_hash']);
                
                http_response_code(200);
                echo json_encode(array(
                    "message" => "Login successful",
                    "user" => $row,
                    "session_token" => $session_token
                ));
            } else {
                http_response_code(401);
                echo json_encode(array("message" => "Invalid credentials"));
            }
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Invalid credentials"));
        }
    }
    
    public function validateSession() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            return false;
        }
        
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        
        $query = "SELECT u.id, u.company_id, u.first_name, u.last_name, u.email, u.department, u.role 
                  FROM users u 
                  JOIN user_sessions s ON u.id = s.user_id 
                  WHERE s.session_token = :token AND s.expires_at > NOW() AND u.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }
}

$auth = new AuthAPI();
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'POST':
        switch ($path) {
            case 'register':
                $auth->register();
                break;
            case 'login':
                $auth->login();
                break;
            default:
                http_response_code(404);
                echo json_encode(array("message" => "Endpoint not found"));
                break;
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>