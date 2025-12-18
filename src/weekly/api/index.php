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

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$resource = $_GET['resource'] ?? 'weeks';

// =================== Weeks CRUD ===================

function getAllWeeks($db) {
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'start_date';
    $order = strtolower($_GET['order'] ?? 'asc');

    $allowedSort = ['title', 'start_date', 'created_at'];
    if (!in_array($sort, $allowedSort)) $sort = 'start_date';
    if (!in_array($order, ['asc','desc'])) $order = 'asc';

    $sql = "SELECT week_id, title, start_date, description, links, created_at FROM weeks";
    $params = [];
    if($search) {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($weeks as &$w) {
        $w['links'] = $w['links'] ? json_decode($w['links'], true) : [];
    }
    sendResponse(['success'=>true,'data'=>$weeks]);
}

function getWeekById($db, $weekId) {
    if(!$weekId) sendError("week_id is required",400);
    $stmt = $db->prepare("SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id=?");
    $stmt->execute([$weekId]);
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$week) sendError("Week not found",404);
    $week['links'] = $week['links'] ? json_decode($week['links'], true) : [];
    sendResponse(['success'=>true,'data'=>$week]);
}

function createWeek($db,$data){
    $required = ['week_id','title','start_date','description'];
    foreach($required as $f) if(empty($data[$f])) sendError("Missing required field: $f",400);

    $weekId = sanitizeInput($data['week_id']);
    $title = sanitizeInput($data['title']);
    $desc = sanitizeInput($data['description']);
    $date = $data['start_date'];
    if(!validateDate($date)) sendError("Invalid date format",400);

    // check duplicate
    $check = $db->prepare("SELECT id FROM weeks WHERE week_id=?");
    $check->execute([$weekId]);
    if($check->fetch()) sendError("Week already exists",409);

    $links = isset($data['links']) && is_array($data['links']) ? $data['links'] : [];
    $stmt = $db->prepare("INSERT INTO weeks (week_id,title,start_date,description,links) VALUES (?,?,?,?,?)");
    if(!$stmt->execute([$weekId,$title,$date,$desc,json_encode($links)])) sendError("Failed to create week",500);
    sendResponse(['success'=>true,'data'=>['week_id'=>$weekId,'title'=>$title,'start_date'=>$date,'description'=>$desc,'links'=>$links]],201);
}

function updateWeek($db,$data){
    if(empty($data['week_id'])) sendError("week_id is required",400);
    $weekId = $data['week_id'];
    $check = $db->prepare("SELECT id FROM weeks WHERE week_id=?");
    $check->execute([$weekId]);
    if(!$check->fetch()) sendError("Week not found",404);

    $fields=[]; $values=[];
    if(!empty($data['title'])) {$fields[]='title=?'; $values[]=sanitizeInput($data['title']);}
    if(!empty($data['start_date'])) { if(!validateDate($data['start_date'])) sendError("Invalid date",400); $fields[]='start_date=?'; $values[]=$data['start_date'];}
    if(!empty($data['description'])) {$fields[]='description=?'; $values[]=sanitizeInput($data['description']);}
    if(array_key_exists('links',$data) && is_array($data['links'])) {$fields[]='links=?'; $values[]=json_encode($data['links']);}
    if(empty($fields)) sendError("No fields to update",400);
    $fields[]='updated_at=CURRENT_TIMESTAMP';
    $sql="UPDATE weeks SET ".implode(",",$fields)." WHERE week_id=?";
    $values[]=$weekId;
    $stmt = $db->prepare($sql);
    if(!$stmt->execute($values)) sendError("Failed to update week",500);
    sendResponse(['success'=>true,'message'=>"Week updated successfully"]);
}

function deleteWeek($db,$weekId){
    if(!$weekId) sendError("week_id is required",400);
    $check = $db->prepare("SELECT id FROM weeks WHERE week_id=?");
    $check->execute([$weekId]);
    if(!$check->fetch()) sendError("Week not found",404);

    // delete comments first
    $db->prepare("DELETE FROM comments WHERE week_id=?")->execute([$weekId]);
    $stmt = $db->prepare("DELETE FROM weeks WHERE week_id=?");
    if(!$stmt->execute([$weekId])) sendError("Failed to delete week",500);
    sendResponse(['success'=>true,'message'=>"Week and related comments deleted"]);
}

// =================== Comments CRUD ===================

function getCommentsByWeek($db,$weekId){
    if(!$weekId) sendError("week_id required",400);
    $stmt = $db->prepare("SELECT id, week_id, author, text, created_at FROM comments WHERE week_id=? ORDER BY created_at ASC");
    $stmt->execute([$weekId]);
    sendResponse(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment($db,$data){
    $required=['week_id','author','text'];
    foreach($required as $f) if(empty($data[$f])) sendError("Missing required field: $f",400);
    $weekId = sanitizeInput($data['week_id']);
    $author = sanitizeInput($data['author']);
    $text = trim($data['text']);
    if($text==='') sendError("Comment text cannot be empty",400);

    // check week exists
    $check = $db->prepare("SELECT id FROM weeks WHERE week_id=?");
    $check->execute([$weekId]);
    if(!$check->fetch()) sendError("Week not found for this comment",404);

    $stmt=$db->prepare("INSERT INTO comments (week_id,author,text) VALUES (?,?,?)");
    if(!$stmt->execute([$weekId,$author,$text])) sendError("Failed to create comment",500);
    $id = $db->lastInsertId();
    sendResponse(['success'=>true,'data'=>['id'=>(int)$id,'week_id'=>$weekId,'author'=>$author,'text'=>$text]],201);
}

function deleteComment($db,$commentId){
    if(!$commentId) sendError("id required",400);
    $check = $db->prepare("SELECT id FROM comments WHERE id=?");
    $check->execute([$commentId]);
    if(!$check->fetch()) sendError("Comment not found",404);
    $stmt=$db->prepare("DELETE FROM comments WHERE id=?");
    if(!$stmt->execute([$commentId])) sendError("Failed to delete comment",500);
    sendResponse(['success'=>true,'message'=>"Comment deleted"]);
}

// =================== Router ===================

try{
    if($resource==='weeks'){
        if($method==='GET'){
            $weekId = $_GET['week_id'] ?? null;
            if($weekId) getWeekById($db,$weekId);
            else getAllWeeks($db);
        }elseif($method==='POST') createWeek($db,$input);
        elseif($method==='PUT') updateWeek($db,$input);
        elseif($method==='DELETE'){
            $weekId = $_GET['week_id'] ?? ($input['week_id']??null);
            deleteWeek($db,$weekId);
        }else sendError("Method not allowed",405);
    }elseif($resource==='comments'){
        if($method==='GET') $weekId=$_GET['week_id']??null; getCommentsByWeek($db,$weekId);
        elseif($method==='POST') createComment($db,$input);
        elseif($method==='DELETE'){
            $commentId=$_GET['id']??($input['id']??null);
            deleteComment($db,$commentId);
        }else sendError("Method not allowed",405);
    }else sendError("Invalid resource. Use 'weeks' or 'comments'.",400);
}catch(PDOException $e){ sendError("Database error occurred",500);}
catch(Exception $e){ sendError("An unexpected error occurred",500); }

// =================== Helpers ===================

function sendResponse($data,$statusCode=200){ http_response_code($statusCode); echo json_encode($data,JSON_UNESCAPED_UNICODE); exit;}
function sendError($msg,$statusCode=400){ sendResponse(['success'=>false,'error'=>$msg],$statusCode);}
function sanitizeInput($data){ return htmlspecialchars(strip_tags(trim($data)),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function validateDate($d){ $dt=DateTime::createFromFormat('Y-m-d',$d); return $dt && $dt->format('Y-m-d')===$d; }
?>
