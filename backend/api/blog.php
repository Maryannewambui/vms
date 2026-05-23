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

class BlogAPI {
    private $conn;
    private $auth;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->auth = new AuthAPI();
    }
    
    public function getAllBlogs() {
        $query = "SELECT b.*, u.first_name, u.last_name,
                  (SELECT COUNT(*) FROM blog_likes bl WHERE bl.blog_id = b.id) as likes_count,
                  (SELECT COUNT(*) FROM blog_comments bc WHERE bc.blog_id = b.id) as comments_count
                  FROM blog_posts b 
                  JOIN users u ON b.author_id = u.id 
                  WHERE b.is_published = 1 
                  ORDER BY b.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $blogs = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $blogs[] = $row;
        }
        
        echo json_encode($blogs);
    }
    
    public function createBlog() {
        $user = $this->auth->validateSession();
        if (!$user) {
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
        
        $query = "INSERT INTO blog_posts (title, content, author_id, is_published) 
                  VALUES (:title, :content, :author_id, :is_published)";
        
        $stmt = $this->conn->prepare($query);
        
        $is_published = isset($data->is_published) ? $data->is_published : true;
        
        $stmt->bindParam(":title", $data->title);
        $stmt->bindParam(":content", $data->content);
        $stmt->bindParam(":author_id", $user['id']);
        $stmt->bindParam(":is_published", $is_published);
        
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Blog created successfully", "id" => $this->conn->lastInsertId()));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to create blog"));
        }
    }
    
    public function likeBlog($id) {
        $user = $this->auth->validateSession();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        // Check if already liked
        $check_query = "SELECT id FROM blog_likes WHERE blog_id = :blog_id AND user_id = :user_id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(":blog_id", $id);
        $check_stmt->bindParam(":user_id", $user['id']);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Unlike
            $delete_query = "DELETE FROM blog_likes WHERE blog_id = :blog_id AND user_id = :user_id";
            $delete_stmt = $this->conn->prepare($delete_query);
            $delete_stmt->bindParam(":blog_id", $id);
            $delete_stmt->bindParam(":user_id", $user['id']);
            $delete_stmt->execute();
            
            echo json_encode(array("message" => "Blog unliked", "action" => "unlike"));
        } else {
            // Like
            $like_query = "INSERT INTO blog_likes (blog_id, user_id) VALUES (:blog_id, :user_id)";
            $like_stmt = $this->conn->prepare($like_query);
            $like_stmt->bindParam(":blog_id", $id);
            $like_stmt->bindParam(":user_id", $user['id']);
            $like_stmt->execute();
            
            echo json_encode(array("message" => "Blog liked", "action" => "like"));
        }
    }
    
    public function addComment($id) {
        $user = $this->auth->validateSession();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        if (!isset($data->comment)) {
            http_response_code(400);
            echo json_encode(array("message" => "Comment text required"));
            return;
        }
        
        $query = "INSERT INTO blog_comments (blog_id, user_id, comment) 
                  VALUES (:blog_id, :user_id, :comment)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":blog_id", $id);
        $stmt->bindParam(":user_id", $user['id']);
        $stmt->bindParam(":comment", $data->comment);
        
        if ($stmt->execute()) {
            echo json_encode(array("message" => "Comment added successfully", "id" => $this->conn->lastInsertId()));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to add comment"));
        }
    }
    
    public function getComments($id) {
        $query = "SELECT bc.*, u.first_name, u.last_name 
                  FROM blog_comments bc 
                  JOIN users u ON bc.user_id = u.id 
                  WHERE bc.blog_id = :blog_id 
                  ORDER BY bc.created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":blog_id", $id);
        $stmt->execute();
        
        $comments = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $comments[] = $row;
        }
        
        echo json_encode($comments);
    }
}

$blog = new BlogAPI();
$method = $_SERVER['REQUEST_METHOD'];
$path_parts = explode('/', $_SERVER['REQUEST_URI']);
$id = isset($path_parts[3]) ? $path_parts[3] : null;
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        if ($action == 'comments' && $id) {
            $blog->getComments($id);
        } else {
            $blog->getAllBlogs();
        }
        break;
    case 'POST':
        if ($action == 'like' && $id) {
            $blog->likeBlog($id);
        } elseif ($action == 'comment' && $id) {
            $blog->addComment($id);
        } else {
            $blog->createBlog();
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>