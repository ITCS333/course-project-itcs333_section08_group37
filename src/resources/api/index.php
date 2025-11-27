<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$resource_id = $_GET['resource_id'] ?? null;
$comment_id = $_GET['comment_id'] ?? null;

// -------------------- Helper functions --------------------
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES);
}

function validateRequiredFields($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    return ['valid' => count($missing) === 0, 'missing' => $missing];
}

// -------------------- Resource functions --------------------
function getAllResources($db) {
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = strtolower($_GET['order'] ?? 'desc');

    $allowedSort = ['title','created_at'];
    if (!in_array($sort,$allowedSort)) $sort = 'created_at';
    if (!in_array($order,['asc','desc'])) $order = 'desc';

    $sql = "SELECT id,title,description,link,created_at FROM resources";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = "%$search%";
    }
    $sql .= " ORDER BY $sort $order";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$results]);
}

function getResourceById($db,$resourceId) {
    if (!is_numeric($resourceId)) sendResponse(['success'=>false,'message'=>'Invalid resource ID'],400);
    $stmt = $db->prepare("SELECT id,title,description,link,created_at FROM resources WHERE id=?");
    $stmt->execute([$resourceId]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) sendResponse(['success'=>true,'data'=>$res]);
    else sendResponse(['success'=>false,'message'=>'Resource not found'],404);
}

function createResource($db,$data) {
    $validation = validateRequiredFields($data,['title','link']);
    if (!$validation['valid']) sendResponse(['success'=>false,'message'=>'Missing fields: '.implode(', ',$validation['missing'])],400);
    $title = sanitizeInput($data['title']);
    $link = sanitizeInput($data['link']);
    $description = sanitizeInput($data['description'] ?? '');
    if (!validateUrl($link)) sendResponse(['success'=>false,'message'=>'Invalid URL'],400);
    $stmt = $db->prepare("INSERT INTO resources (title,description,link) VALUES (?,?,?)");
    if ($stmt->execute([$title,$description,$link])) {
        $id = $db->lastInsertId();
        sendResponse(['success'=>true,'message'=>'Resource created','id'=>$id],201);
    } else sendResponse(['success'=>false,'message'=>'Failed to create resource'],500);
}

function updateResource($db,$data) {
    if (!isset($data['id']) || !is_numeric($data['id'])) sendResponse(['success'=>false,'message'=>'Resource ID required'],400);
    $id = $data['id'];
    $stmt = $db->prepare("SELECT id FROM resources WHERE id=?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) sendResponse(['success'=>false,'message'=>'Resource not found'],404);

    $fields=[]; $values=[];
    if (isset($data['title'])) { $fields[]='title=?'; $values[]=sanitizeInput($data['title']); }
    if (isset($data['description'])) { $fields[]='description=?'; $values[]=sanitizeInput($data['description']); }
    if (isset($data['link'])) { 
        $link=sanitizeInput($data['link']);
        if (!validateUrl($link)) sendResponse(['success'=>false,'message'=>'Invalid URL'],400);
        $fields[]='link=?'; $values[]=$link;
    }
    if (empty($fields)) sendResponse(['success'=>false,'message'=>'No fields to update'],400);
    $values[]=$id;
    $stmt = $db->prepare("UPDATE resources SET ".implode(', ',$fields)." WHERE id=?");
    if ($stmt->execute($values)) sendResponse(['success'=>true,'message'=>'Resource updated']);
    else sendResponse(['success'=>false,'message'=>'Update failed'],500);
}

function deleteResource($db,$resourceId) {
    if (!is_numeric($resourceId)) sendResponse(['success'=>false,'message'=>'Invalid resource ID'],400);
    $stmt = $db->prepare("SELECT id FROM resources WHERE id=?");
    $stmt->execute([$resourceId]);
    if (!$stmt->fetch()) sendResponse(['success'=>false,'message'=>'Resource not found'],404);
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("DELETE FROM comments WHERE resource_id=?"); $stmt->execute([$resourceId]);
        $stmt = $db->prepare("DELETE FROM resources WHERE id=?"); $stmt->execute([$resourceId]);
        $db->commit();
        sendResponse(['success'=>true,'message'=>'Resource deleted']);
    } catch (Exception $e) {
        $db->rollBack();
        sendResponse(['success'=>false,'message'=>'Failed to delete resource'],500);
    }
}

// -------------------- Comment functions --------------------
function getCommentsByResourceId($db,$resourceId) {
    if (!is_numeric($resourceId)) sendResponse(['success'=>false,'message'=>'Invalid resource ID'],400);
    $stmt = $db->prepare("SELECT id,resource_id,author,text,created_at FROM comments WHERE resource_id=? ORDER BY created_at ASC");
    $stmt->execute([$resourceId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$results]);
}

function createComment($db,$data) {
    $validation = validateRequiredFields($data,['resource_id','author','text']);
    if (!$validation['valid']) sendResponse(['success'=>false,'message'=>'Missing fields: '.implode(', ',$validation['missing'])],400);
    $res_id=$data['resource_id'];
    if (!is_numeric($res_id)) sendResponse(['success'=>false,'message'=>'Invalid resource ID'],400);
    $stmt = $db->prepare("SELECT id FROM resources WHERE id=?"); $stmt->execute([$res_id]);
    if (!$stmt->fetch()) sendResponse(['success'=>false,'message'=>'Resource not found'],404);
    $author=sanitizeInput($data['author']); $text=sanitizeInput($data['text']);
    $stmt = $db->prepare("INSERT INTO comments (resource_id,author,text) VALUES (?,?,?)");
    if ($stmt->execute([$res_id,$author,$text])) {
        $id=$db->lastInsertId();
        sendResponse(['success'=>true,'message'=>'Comment added','id'=>$id],201);
    } else sendResponse(['success'=>false,'message'=>'Failed to add comment'],500);
}

function deleteComment($db,$commentId) {
    if (!is_numeric($commentId)) sendResponse(['success'=>false,'message'=>'Invalid comment ID'],400);
    $stmt = $db->prepare("SELECT id FROM comments WHERE id=?"); $stmt->execute([$commentId]);
    if (!$stmt->fetch()) sendResponse(['success'=>false,'message'=>'Comment not found'],404);
    $stmt = $db->prepare("DELETE FROM comments WHERE id=?");
    if ($stmt->execute([$commentId])) sendResponse(['success'=>true,'message'=>'Comment deleted']);
    else sendResponse(['success'=>false,'message'=>'Delete failed'],500);
}

// -------------------- Main router --------------------
try {
    if ($method==='GET') {
        if ($action==='comments') getCommentsByResourceId($db,$resource_id);
        elseif ($id) getResourceById($db,$id);
        else getAllResources($db);
    } elseif ($method==='POST') {
        if ($action==='comment') createComment($db,$input);
        else createResource($db,$input);
    } elseif ($method==='PUT') {
        updateResource($db,$input);
    } elseif ($method==='DELETE') {
        if ($action==='delete_comment') deleteComment($db,$comment_id);
        else deleteResource($db,$id);
    } else {
        sendResponse(['success'=>false,'message'=>'Method not allowed'],405);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    sendResponse(['success'=>false,'message'=>'Database error'],500);
} catch (Exception $e) {
    error_log($e->getMessage());
    sendResponse(['success'=>false,'message'=>'Server error'],500);
}
?>
