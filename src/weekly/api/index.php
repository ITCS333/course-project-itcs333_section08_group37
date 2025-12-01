<?php
/**
 * Weekly Course Breakdown API
 * Handles CRUD operations for weeks and comments
 * Using PDO + MySQL
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// Headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include DB connection (افترضنا ملف Database.php موجود)
require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Request method + body
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

// Resource type
$resource = $_GET['resource'] ?? 'weeks';

// ============================================================================
// WEEKS CRUD
// ============================================================================

function getAllWeeks($db) {
    $search = $_GET['search'] ?? null;
    $sort   = $_GET['sort'] ?? 'start_date';
    $order  = strtolower($_GET['order'] ?? 'asc');

    $allowedSort = ['title','start_date','created_at'];
    if (!in_array($sort, $allowedSort)) $sort = 'start_date';
    if (!in_array($order, ['asc','desc'])) $order = 'asc';

    $sql = "SELECT week_id, title, start_date, description, links, created_at FROM weeks";
    $params = [];

    if ($search) {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $params = ["%$search%", "%$search%"];
    }

    $sql .= " ORDER BY $sort $order";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['links'] = json_decode($row['links'], true);
    }

    sendResponse(['success'=>true,'data'=>$rows]);
}

function getWeekById($db,$weekId) {
    if (!$weekId) return sendError("week_id required",400);

    $stmt = $db->prepare("SELECT week_id,title,start_date,description,links,created_at FROM weeks WHERE week_id=?");
    $stmt->execute([$weekId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $row['links'] = json_decode($row['links'], true);
        sendResponse(['success'=>true,'data'=>$row]);
    } else {
        sendError("Week not found",404);
    }
}

function createWeek($db,$data) {
    if (empty($data['week_id']) || empty($data['title']) || empty($data['start_date']) || empty($data['description'])) {
        return sendError("Missing required fields",400);
    }

    $weekId = sanitizeInput($data['week_id']);
    $title  = sanitizeInput($data['title']);
    $desc   = sanitizeInput($data['description']);
    $date   = $data['start_date'];

    if (!validateDate($date)) return sendError("Invalid date format",400);

    // Check duplicate
    $check = $db->prepare("SELECT id FROM weeks WHERE week_id=?");
    $check->execute([$weekId]);
    if ($check->fetch()) return sendError("Week already exists",409);

    $links = isset($data['links']) && is_array($data['links']) ? json_encode($data['links']) : json_encode([]);

    $stmt = $db->prepare("INSERT INTO weeks (week_id,title,start_date,description,links) VALUES (?,?,?,?,?)");
    if ($stmt->execute([$weekId,$title,$date,$desc,$links])) {
        sendResponse(['success'=>true,'data'=>$data],201);
    } else {
        sendError("Failed to create week",500);
    }
}

function updateWeek($db,$data) {
    if (empty($data['week_id'])) return sendError("week_id required",400);
    $weekId = $data['week_id'];

    $check = $db->prepare("SELECT id FROM weeks WHERE week_id=?");
    $check->execute([$weekId]);
    if (!$check->fetch()) return sendError("Week not found",404);

    $fields = [];
    $values = [];

    if (!empty($data['title'])) { $fields[]="title=?"; $values[]=$data['title']; }
    if (!empty($data['start_date'])) {
        if (!validateDate($data['start_date'])) return sendError("Invalid date",400);
        $fields[]="start_date=?"; $values[]=$data['start_date'];
    }
    if (!empty($data['description'])) { $fields[]="description=?"; $values[]=$data['description']; }
    if (!empty($data['links'])) { $fields[]="links=?"; $values[]=json_encode($data['links']); }

    if (!$fields) return sendError("No fields to update",400);

    $sql = "UPDATE weeks SET ".implode(",",$fields).", updated_at=CURRENT_TIMESTAMP WHERE week_id=?";
    $values[]=$weekId;

    $stmt = $db->prepare($sql);
    if ($stmt->execute($values)) {
        sendResponse(['success'=>true,'message'=>"Week updated"]);
    } else {
        sendError("Update failed",500);
    }
}

function deleteWeek($db,$weekId) {
    if (!$weekId) return sendError("week_id required",400);

    $check = $db->prepare("SELECT id FROM weeks WHERE week_id=?");
    $check->execute([$weekId]);
    if (!$check->fetch()) return sendError("Week not found",404);

    $db->prepare("DELETE FROM comments WHERE week_id=?")->execute([$weekId]);
    $stmt = $db->prepare("DELETE FROM weeks WHERE week_id=?");
    if ($stmt->execute([$weekId])) {
        sendResponse(['success'=>true,'message'=>"Week + comments deleted"]);
    } else {
        sendError("Delete failed",500);
    }
}

// ============================================================================
// COMMENTS CRUD
// ============================================================================

function getCommentsByWeek($db,$weekId) {
    if (!$weekId) return sendError("week_id required",400);

    $stmt = $db->prepare("SELECT id,week_id,author,text,created_at FROM comments WHERE week_id=? ORDER BY created_at ASC");
    $stmt->execute([$weekId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$rows]);
}

function createComment($db,$data) {
    if (empty($data['week_id']) || empty($data['author']) || empty($data['text'])) {
        return sendError("Missing fields",400);
    }

    $stmt = $db->prepare("INSERT INTO comments (week_id,author,text) VALUES (?,?,?)");
    if ($stmt->execute([$data['week_id'],sanitizeInput($data['author']),sanitizeInput($data['text'])])) {
        $id = $db->lastInsertId();
        sendResponse(['success'=>true,'data'=>['id'=>$id]+$data],201);
    } else {
        sendError("Failed to add comment",500);
    }
}

function deleteComment($db,$id) {
    if (!$id) return sendError("id required",400);

    $check = $db->prepare("SELECT id FROM comments WHERE id=?");
    $check->execute([$id]);
    if (!$check->fetch()) return sendError("Comment not found",404);

    $stmt = $db->prepare("DELETE FROM comments WHERE id=?");
    if ($stmt->execute([$id])) {
        sendResponse(['success'=>true,'message'=>"Comment deleted"]);
    } else {
        sendError("Delete failed",500);
    }
}

// ============================================================================
// ROUTER
// ============================================================================

try {
    if ($resource === 'weeks') {
        if ($method === 'GET') {
            if (isset($_GET['week_id'])) getWeekById($db,$_GET['week_id']);
            else getAllWeeks($db);
        } elseif ($method === 'POST') {
            createWeek($db,$input);
        } elseif ($method === 'PUT') {
            updateWeek($db,$input);
        } elseif ($method === 'DELETE') {
            $weekId = $_GET['week_id'] ?? ($input['week_id'] ?? null);
            deleteWeek($db,$weekId);
        } else {
            sendError("Method not allowed",405);
        }
    } elseif ($resource === 'comments') {
        if ($method === 'GET') {
            getCommentsByWeek($db,$_GET['week_id'] ?? null);
        } elseif ($method === 'POST') {
            createComment($db,$input);
        } elseif ($method === 'DELETE') {
            $id = $_GET['id'] ?? ($input['id'] ?? null);
            deleteComment($
