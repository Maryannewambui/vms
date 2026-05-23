<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once 'auth.php';

class UserAPI {
    private $conn;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auth = new AuthAPI();
    }
    
    public function getProfile() {
        $user = $this->auth->validateSession();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        echo json_encode($user);
    }
    
    public function updateProfile() {
        $user = $this->auth->validateSession();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, 
                  email = :email, phone = :phone WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":first_name", $data->first_name);
        $stmt->bindParam(":last_name", $data->last_name);
        $stmt->bindParam(":email", $data->email);
        $stmt->bindParam(":phone", $data->phone);
        $stmt->bindParam(":id", $user['id']);
        
        if ($stmt->execute()) {
            echo json_encode(array("message" => "Profile updated successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to update profile"));
        }
    }
    
    public function getRecentActivity() {
        $user = $this->auth->validateSession();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        $query = "SELECT DISTINCT page_name, MAX(visited_at) as last_visited 
                  FROM user_activity 
                  WHERE user_id = :user_id 
                  GROUP BY page_name 
                  ORDER BY last_visited DESC 
                  LIMIT 5";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user['id']);
        $stmt->execute();
        
        $activities = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = $row;
        }
        
        echo json_encode($activities);
    }
    
    public function recordActivity() {
        $user = $this->auth->validateSession();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->page_name)) {
            http_response_code(400);
            echo json_encode(array("message" => "Page name required"));
            return;
        }
        
        $query = "INSERT INTO user_activity (user_id, page_name) VALUES (:user_id, :page_name)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user['id']);
        $stmt->bindParam(":page_name", $data->page_name);
        $stmt->execute();
        
        echo json_encode(array("message" => "Activity recorded"));
    }
}

$userAPI = new UserAPI();
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        if ($action == 'profile') {
            $userAPI->getProfile();
        } elseif ($action == 'activity') {
            $userAPI->getRecentActivity();
        }
        break;
    case 'POST':
        if ($action == 'activity') {
            $userAPI->recordActivity();
        }
        break;
    case 'PUT':
        if ($action == 'profile') {
            $userAPI->updateProfile();
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>