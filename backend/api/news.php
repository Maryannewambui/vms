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

class NewsAPI {
    private $conn;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auth = new AuthAPI();
    }
    
    public function getAllNews() {
        $query = "SELECT n.*, u.first_name, u.last_name 
                  FROM news n 
                  JOIN users u ON n.author_id = u.id 
                  WHERE n.is_published = 1 
                  ORDER BY n.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $news = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $news[] = $row;
        }
        
        echo json_encode($news);
    }
    
    public function createNews() {
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
        
        $query = "INSERT INTO news (title, content, author_id, category, is_published) 
                  VALUES (:title, :content, :author_id, :category, :is_published)";
        
        $stmt = $this->conn->prepare($query);
        
        $category = isset($data->category) ? $data->category : 'news';
        $is_published = isset($data->is_published) ? $data->is_published : true;
        
        $stmt->bindParam(":title", $data->title);
        $stmt->bindParam(":content", $data->content);
        $stmt->bindParam(":author_id", $user['id']);
        $stmt->bindParam(":category", $category);
        $stmt->bindParam(":is_published", $is_published);
        
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "News created successfully", "id" => $this->conn->lastInsertId()));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to create news"));
        }
    }
    
    public function updateNews($id) {
        $user = $this->auth->validateSession();
        if (!$user || !in_array($user['role'], ['hr', 'director', 'admin'])) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        $query = "UPDATE news SET title = :title, content = :content, category = :category, 
                  is_published = :is_published WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":title", $data->title);
        $stmt->bindParam(":content", $data->content);
        $stmt->bindParam(":category", $data->category);
        $stmt->bindParam(":is_published", $data->is_published);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute()) {
            echo json_encode(array("message" => "News updated successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to update news"));
        }
    }
    
    public function deleteNews($id) {
        $user = $this->auth->validateSession();
        if (!$user || !in_array($user['role'], ['hr', 'director', 'admin'])) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        $query = "DELETE FROM news WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute()) {
            echo json_encode(array("message" => "News deleted successfully"));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to delete news"));
        }
    }
}

$news = new NewsAPI();
$method = $_SERVER['REQUEST_METHOD'];
$path_parts = explode('/', $_SERVER['REQUEST_URI']);
$id = isset($path_parts[3]) ? $path_parts[3] : null;

switch ($method) {
    case 'GET':
        $news->getAllNews();
        break;
    case 'POST':
        $news->createNews();
        break;
    case 'PUT':
        if ($id) {
            $news->updateNews($id);
        }
        break;
    case 'DELETE':
        if ($id) {
            $news->deleteNews($id);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>