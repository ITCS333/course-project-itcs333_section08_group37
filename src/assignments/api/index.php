<?php
session_start();

/* REQUIRED: use $_SESSION to store user data */
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;        // dummy user for assignment
    $_SESSION['role'] = 'student';   // user role
}

/**
 * Assignment Management API
 */

/* ============================================================================
   HEADERS AND CORS CONFIGURATION
============================================================================ */

// TODO: Set Content-Type header to application/json
header("Content-Type: application/json");

// TODO: Set CORS headers to allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* ============================================================================
   DATABASE CONNECTION
============================================================================ */

try {
    // TODO: Include the database connection class
    require_once __DIR__ . '/../common/db.php';

    // TODO: Create database connection
    $db = (new Database())->getConnection();

    // TODO: Set PDO to throw exceptions on errors
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    sendResponse(["error" => "Database connection failed"], 500);
}

/* ============================================================================
   REQUEST PARSING
============================================================================ */

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$data = json_decode(file_get_contents("php://input"), true);

// TODO: Parse query parameters
$resource = $_GET['resource'] ?? null;

/* ============================================================================
   ASSIGNMENT CRUD FUNCTIONS
============================================================================ */

function getAllAssignments($db) {
    $sql = "SELECT * FROM assignments";
    $params = [];

    if (!empty($_GET['search'])) {
        $sql .= " WHERE title LIKE :search";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $sort  = $_GET['sort']  ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';

    $stmt = $db->prepare($sql . " ORDER BY $sort $order");
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['files'] = json_decode($r['files'], true);
    }

    sendResponse($rows);
}

function getAssignmentById($db, $assignmentId) {
    if (!$assignmentId) sendResponse(["error"=>"ID required"],400);

    $stmt = $db->prepare("SELECT * FROM assignments WHERE id=:id");
    $stmt->bindParam(":id",$assignmentId);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) sendResponse(["error"=>"Not found"],404);

    $row['files'] = json_decode($row['files'],true);
    sendResponse($row);
}

function createAssignment($db, $data) {
    if (empty($data['title']) || empty($data['due_date'])) {
        sendResponse(["error"=>"Missing fields"],400);
    }

    if (!validateDate($data['due_date'])) {
        sendResponse(["error"=>"Invalid date"],400);
    }

    $stmt = $db->prepare("
        INSERT INTO assignments (title, description, due_date, files)
        VALUES (:t,:d,:dd,:f)
    ");

    $stmt->execute([
        ':t'  => sanitizeInput($data['title']),
        ':d'  => sanitizeInput($data['description'] ?? ''),
        ':dd' => $data['due_date'],
        ':f'  => json_encode($data['files'] ?? [])
    ]);

    sendResponse(["message"=>"Assignment created"],201);
}

function updateAssignment($db, $data) {
    if (empty($data['id'])) sendResponse(["error"=>"ID required"],400);

    $check = $db->prepare("SELECT id FROM assignments WHERE id=:id");
    $check->execute([':id'=>$data['id']]);
    if (!$check->fetch()) sendResponse(["error"=>"Not found"],404);

    $fields = [];
    $params = [':id'=>$data['id']];

    foreach (['title','description','due_date','files'] as $f) {
        if (isset($data[$f])) {
            $fields[] = "$f=:$f";
            $params[":$f"] = ($f === 'files')
                ? json_encode($data[$f])
                : sanitizeInput($data[$f]);
        }
    }

    if (!$fields) sendResponse(["error"=>"Nothing to update"],400);

    $sql = "UPDATE assignments SET ".implode(',',$fields)." WHERE id=:id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    sendResponse(["message"=>"Updated"]);
}

function deleteAssignment($db, $assignmentId) {
    if (!$assignmentId) sendResponse(["error"=>"ID required"],400);

    $db->prepare("DELETE FROM comments WHERE assignment_id=:id")
       ->execute([':id'=>$assignmentId]);

    $stmt = $db->prepare("DELETE FROM assignments WHERE id=:id");
    $stmt->bindParam(":id",$assignmentId);
    $stmt->execute();

    sendResponse(["message"=>"Deleted"]);
}

/* ============================================================================
   COMMENTS
============================================================================ */

function getCommentsByAssignment($db, $assignmentId) {
    if (!$assignmentId) sendResponse(["error"=>"Assignment ID required"],400);

    $stmt = $db->prepare("SELECT * FROM comments WHERE assignment_id=:id");
    $stmt->bindParam(":id",$assignmentId);
    $stmt->execute();

    sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function createComment($db, $data) {
    if (empty($data['assignment_id']) || empty(trim($data['text']))) {
        sendResponse(["error"=>"Missing fields"],400);
    }

    $stmt = $db->prepare("
        INSERT INTO comments (assignment_id, author, text)
        VALUES (:a,:u,:t)
    ");

    $stmt->execute([
        ':a'=>$data['assignment_id'],
        ':u'=>sanitizeInput($data['author'] ?? 'Anonymous'),
        ':t'=>sanitizeInput($data['text'])
    ]);

    sendResponse(["message"=>"Comment created"],201);
}

function deleteComment($db, $commentId) {
    if (!$commentId) sendResponse(["error"=>"ID required"],400);

    $stmt = $db->prepare("DELETE FROM comments WHERE id=:id");
    $stmt->bindParam(":id",$commentId);
    $stmt->execute();

    sendResponse(["message"=>"Comment deleted"]);
}

/* ============================================================================
   ROUTER
============================================================================ */

try {
    if ($method === 'GET') {
        if ($resource === 'assignments') {
            isset($_GET['id'])
                ? getAssignmentById($db,$_GET['id'])
                : getAllAssignments($db);
        } elseif ($resource === 'comments') {
            getCommentsByAssignment($db,$_GET['assignment_id'] ?? null);
        } else {
            sendResponse(["error"=>"Invalid resource"],400);
        }
    } elseif ($method === 'POST') {
        if ($resource === 'assignments') createAssignment($db,$data);
        elseif ($resource === 'comments') createComment($db,$data);
    } elseif ($method === 'PUT') {
        if ($resource === 'assignments') updateAssignment($db,$data);
    } elseif ($method === 'DELETE') {
        if ($resource === 'assignments') deleteAssignment($db,$_GET['id'] ?? null);
        elseif ($resource === 'comments') deleteComment($db,$_GET['id'] ?? null);
    }
} catch (PDOException $e) {
    sendResponse(["error"=>"Database error"],500);
}

/* ============================================================================
   HELPERS
============================================================================ */

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateDate($date) {
    return DateTime::createFromFormat('Y-m-d',$date) !== false;
}
