<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../common/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? ($input['id'] ?? null);
$resource_id = $_GET['resource_id'] ?? ($input['resource_id'] ?? null);
$comment_id = $_GET['comment_id'] ?? ($input['comment_id'] ?? null);

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateUrl($url) {
    // يقبل روابط كاملة أو تبدأ بـ www
    return filter_var($url, FILTER_VALIDATE_URL) || preg_match('/^(www\.)[a-z0-9\-]+\.[a-z]{2,}/i', $url);
}

function sanitizeInput($data) {
    return trim(htmlspecialchars($data));
}

// =================== Resources CRUD ===================

function getAllResources($db) {
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = strtolower($_GET['order'] ?? 'desc');

    $allowedSort = ['title','created_at'];
    $allowedOrder = ['asc','desc'];
    if(!in_array($sort, $allowedSort)) $sort = 'created_at';
    if(!in_array($order, $allowedOrder)) $order = 'desc';

    $sql = "SELECT id, title, description, link, created_at FROM resources";
    if($search !== '') $sql .= " WHERE title LIKE :search OR description LIKE :search";
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    if($search !== '') $stmt->bindValue(':search', "%$search%");
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$resources]);
}

function getResourceById($db, $resourceId) {
    if(!is_numeric($resourceId)) sendResponse(['success'=>false,'message'=>'Invalid resource ID'],400);
    $stmt = $db->prepare("SELECT id, title, description, link, created_at FROM resources WHERE id=?");
    $stmt->execute([$resourceId]);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    if($resource) sendResponse(['success'=>true,'data'=>$resource]);
    sendResponse(['success'=>false,'message'=>'Resource not found'],404);
}

function createResource($db, $data) {
    if(empty($data['title']) || empty($data['link'])) sendResponse(['success'=>false,'message'=>'Title and Link are required'],400);
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description'] ?? '');
    $link = sanitizeInput($data['link']);
    if(!validateUrl($link)) sendResponse(['success'=>false,'message'=>'Invalid URL'],400);

    $stmt = $db->prepare("INSERT INTO resources (title, description, link) VALUES (?, ?, ?)");
    $success = $stmt->execute([$title,$description,$link]);
    if($success) sendResponse(['success'=>true,'message'=>'Resource created','id'=>$db->lastInsertId()],201);
    sendResponse(['success'=>false,'message'=>'Failed to create resource'],500);
}

function updateResource($db, $data, $id) {
    if(empty($id)) sendResponse(['success'=>false,'message'=>'Resource ID required'],400);

    $stmt = $db->prepare("SELECT * FROM resources WHERE id=?");
    $stmt->execute([$id]);
    if(!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['success'=>false,'message'=>'Resource not found'],404);

    $fields=[]; $values=[];
    if(isset($data['title'])) { $fields[]='title=?'; $values[]=sanitizeInput($data['title']); }
    if(isset($data['description'])) { $fields[]='description=?'; $values[]=sanitizeInput($data['description']); }
    if(isset($data['link'])) {
        if(!validateUrl($data['link'])) sendResponse(['success'=>false,'message'=>'Invalid URL'],400);
        $fields[]='link=?'; $values[]=sanitizeInput($data['link']);
    }
    if(count($fields)===0) sendResponse(['success'=>false,'message'=>'No fields to update'],400);

    $sql = "UPDATE resources SET ".implode(',',$fields)." WHERE id=?";
    $values[] = $id;
    $stmt = $db->prepare($sql);
    if($stmt->execute($values)) sendResponse(['success'=>true,'message'=>'Resource updated']);
    sendResponse(['success'=>false,'message'=>'Failed to update resource'],500);
}

function deleteResource($db, $resourceId) {
    if(!is_numeric($resourceId)) sendResponse(['success'=>false,'message'=>'Invalid ID'],400);
    $stmt = $db->prepare("SELECT * FROM resources WHERE id=?");
    $stmt->execute([$resourceId]);
    if(!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['success'=>false,'message'=>'Resource not found'],404);

    try {
        $db->beginTransaction();
        $stmt = $db->prepare("DELETE FROM comments WHERE resource_id=?");
        $stmt->execute([$resourceId]);
        $stmt = $db->prepare("DELETE FROM resources WHERE id=?");
        $stmt->execute([$resourceId]);
        $db->commit();
        sendResponse(['success'=>true,'message'=>'Resource and its comments deleted']);
    } catch(Exception $e) {
        $db->rollBack();
        sendResponse(['success'=>false,'message'=>'Failed to delete resource'],500);
    }
}

// =================== Comments CRUD ===================

function getCommentsByResourceId($db,$resourceId){
    if(!is_numeric($resourceId)) sendResponse(['success'=>false,'message'=>'Invalid resource ID'],400);
    $stmt = $db->prepare("SELECT id, resource_id, author, text, created_at FROM comments WHERE resource_id=? ORDER BY created_at ASC");
    $stmt->execute([$resourceId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$comments]);
}

function createComment($db,$data){
    if(empty($data['resource_id']) || empty($data['author']) || empty($data['text']))
        sendResponse(['success'=>false,'message'=>'All fields are required'],400);
    $resource_id = $data['resource_id'];
    if(!is_numeric($resource_id)) sendResponse(['success'=>false,'message'=>'Invalid resource ID'],400);
    
    $stmt = $db->prepare("SELECT * FROM resources WHERE id=?");
    $stmt->execute([$resource_id]);
    if(!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['success'=>false,'message'=>'Resource not found'],404);

    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    $stmt = $db->prepare("INSERT INTO comments (resource_id, author, text) VALUES (?,?,?)");
    if($stmt->execute([$resource_id,$author,$text]))
        sendResponse(['success'=>true,'message'=>'Comment added','id'=>$db->lastInsertId()],201);
    sendResponse(['success'=>false,'message'=>'Failed to add comment'],500);
}

function deleteComment($db,$commentId){
    if(!is_numeric($commentId)) sendResponse(['success'=>false,'message'=>'Invalid comment ID'],400);
    $stmt = $db->prepare("SELECT * FROM comments WHERE id=?");
    $stmt->execute([$commentId]);
    if(!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['success'=>false,'message'=>'Comment not found'],404);
    $stmt = $db->prepare("DELETE FROM comments WHERE id=?");
    if($stmt->execute([$commentId])) sendResponse(['success'=>true,'message'=>'Comment deleted']);
    sendResponse(['success'=>false,'message'=>'Failed to delete comment'],500);
}

// =================== Router ===================

try {
    switch($method){
        case 'GET':
            if($action==='comments' && $resource_id) getCommentsByResourceId($db,$resource_id);
            elseif($id) getResourceById($db,$id);
            else getAllResources($db);
            break;
        case 'POST':
            if($action==='comment') createComment($db,$input);
            else createResource($db,$input);
            break;
        case 'PUT':
            updateResource($db,$input,$id);
            break;
        case 'DELETE':
            if($action==='delete_comment') deleteComment($db,$comment_id);
            else deleteResource($db,$id);
            break;
        default:
            sendResponse(['success'=>false,'message'=>'Method not allowed'],405);
    }
} catch(PDOException $e){
    error_log($e->getMessage());
    sendResponse(['success'=>false,'message'=>'Database error'],500);
} catch(Exception $e){
    error_log($e->getMessage());
    sendResponse(['success'=>false,'message'=>'Server error'],500);
}
?>
