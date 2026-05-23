<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once 'auth.php';

class MemoAPI {
    private $conn;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auth = new AuthAPI();
    }
    
    public function getAllMemos() {
        $query = "SELECT m.*, u.first_name, u.last_name 
                  FROM memos m 
                  JOIN users u ON m.author_id = u.id 
                  ORDER BY m.is_urgent DESC, m.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $memos = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $memos[] = $row;
        }
        
        echo json_encode($memos);
    }
    
    public function createMemo() {
        $user = $this->auth->validateSession();
        if (!$user || !in_array($user['role'], ['hr', 'director', 'admin'])) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->title) || !isset($data->content)) {
            http_response_code(400);
            echo json_encode(array("message" => "Title and content required"));
            return;
        }
        
        $query = "INSERT INTO memos (title, content, author_id, category, is_urgent) 
                  VALUES (:title, :content, :author_id, :category, :is_urgent)";
        
        $stmt = $this->conn->prepare($query);
        
        $category = isset($data->category) ? $data->category : 'memo';
        $is_urgent = isset($data->is_urgent) ? $data->is_urgent : false;
        
        $stmt->bindParam(":title", $data->title);
        $stmt->bindParam(":content", $data->content);
        $stmt->bindParam(":author_id", $user['id']);
        $stmt->bindParam(":category", $category);
        $stmt->bindParam(":is_urgent", $is_urgent);
        
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Memo created successfully", "id" => $this->conn->lastInsertId()));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to create memo"));
        }
    }
}

$memo = new MemoAPI();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $memo->getAllMemos();
        break;
    case 'POST':
        $memo->createMemo();
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>