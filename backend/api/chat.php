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
include_once 'auth.php';

class ChatAPI {
    private $conn;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auth = new AuthAPI();
    }
    
    public function getMessages($group_id) {
        $user = $this->auth->validateSession();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        // Check if user is member of the group
        $member_query = "SELECT id FROM chat_group_members WHERE group_id = :group_id AND user_id = :user_id";
        $member_stmt = $this->conn->prepare($member_query);
        $member_stmt->bindParam(":group_id", $group_id);
        $member_stmt->bindParam(":user_id", $user['id']);
        $member_stmt->execute();
        
        if ($member_stmt->rowCount() == 0) {
            http_response_code(403);
            echo json_encode(array("message" => "Access denied"));
            return;
        }
        
        $query = "SELECT cm.*, u.first_name, u.last_name 
                  FROM chat_messages cm 
                  JOIN users u ON cm.user_id = u.id 
                  WHERE cm.group_id = :group_id 
                  ORDER BY cm.created_at ASC 
                  LIMIT 100";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":group_id", $group_id);
        $stmt->execute();
        
        $messages = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $messages[] = $row;
        }
        
        echo json_encode($messages);
    }
    
    public function sendMessage() {
        $user = $this->auth->validateSession();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->group_id) || !isset($data->message)) {
            http_response_code(400);
            echo json_encode(array("message" => "Group ID and message required"));
            return;
        }
        
        // Check if user is member of the group
        $member_query = "SELECT id FROM chat_group_members WHERE group_id = :group_id AND user_id = :user_id";
        $member_stmt = $this->conn->prepare($member_query);
        $member_stmt->bindParam(":group_id", $data->group_id);
        $member_stmt->bindParam(":user_id", $user['id']);
        $member_stmt->execute();
        
        if ($member_stmt->rowCount() == 0) {
            http_response_code(403);
            echo json_encode(array("message" => "Access denied"));
            return;
        }
        
        $query = "INSERT INTO chat_messages (group_id, user_id, message) 
                  VALUES (:group_id, :user_id, :message)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":group_id", $data->group_id);
        $stmt->bindParam(":user_id", $user['id']);
        $stmt->bindParam(":message", $data->message);
        
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Message sent successfully", "id" => $this->conn->lastInsertId()));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to send message"));
        }
    }
    
    public function getGroups() {
        $user = $this->auth->validateSession();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        $query = "SELECT cg.*, 
                  (SELECT COUNT(*) FROM chat_group_members cgm WHERE cgm.group_id = cg.id) as member_count
                  FROM chat_groups cg
                  JOIN chat_group_members cgm ON cg.id = cgm.group_id
                  WHERE cgm.user_id = :user_id
                  ORDER BY cg.name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user['id']);
        $stmt->execute();
        
        $groups = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $groups[] = $row;
        }
        
        echo json_encode($groups);
    }
}

$chat = new ChatAPI();
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$group_id = isset($_GET['group_id']) ? $_GET['group_id'] : null;

switch ($method) {
    case 'GET':
        if ($action == 'messages' && $group_id) {
            $chat->getMessages($group_id);
        } elseif ($action == 'groups') {
            $chat->getGroups();
        }
        break;
    case 'POST':
        if ($action == 'send') {
            $chat->sendMessage();
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>