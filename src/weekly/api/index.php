<?php
/**
 * Weekly Course Breakdown API
 * 
 * Simple REST-like API using PDO.
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// JSON + CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight for browsers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include DB class
// تأكد إن المسار صحيح حسب مشروعك
require_once _DIR_ . '/../config/Database.php';

// Get PDO connection
$database = new Database();
$db = $database->getConnection();

// HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Request body (for POST / PUT / DELETE with JSON)
$rawInput  = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);
if (!is_array($inputData)) {
    $inputData = [];
}

// Resource from query string: weeks | comments
$resource = isset($_GET['resource']) ? $_GET['resource'] : 'weeks';


// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

function getAllWeeks($db) {
    // search / sort / order from query
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort   = isset($_GET['sort'])   ? $_GET['sort']        : 'start_date';
    $order  = isset($_GET['order'])  ? strtolower($_GET['order']) : 'asc';

    // validate sort & order
    $allowedSortFields = ['title', 'start_date', 'created_at'];
    if (!isValidSortField($sort, $allowedSortFields)) {
        $sort = 'start_date';
    }

    if (!in_array($order, ['asc', 'desc'], true)) {
        $order = 'asc';
    }

    // Base query
    $sql = "SELECT week_id, title, start_date, description, links, created_at 
            FROM weeks";

    $params = [];

    if ($search !== '') {
        $sql .= " WHERE title LIKE :term OR description LIKE :term";
        $params[':term'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // decode links JSON
    foreach ($rows as &$row) {
        $row['links'] = $row['links'] ? json_decode($row['links'], true) : [];
    }

    sendResponse([
        'success' => true,
        'data'    => $rows
    ]);
}

function getWeekById($db, $weekId) {
    if (empty($weekId)) {
        sendError('week_id is required', 400);
    }

    $sql = "SELECT week_id, title, start_date, description, links, created_at
            FROM weeks
            WHERE week_id = ?";

    $stmt = $db->prepare($sql);
    $stmt->execute([$weekId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        sendError('Week not found', 404);
    }

    $row['links'] = $row['links'] ? json_decode($row['links'], true) : [];

    sendResponse([
        'success' => true,
        'data'    => $row
    ]);
}

function createWeek($db, $data) {
    // required fields
    if (
        empty($data['week_id']) ||
        empty($data['title']) ||
        empty($data['start_date']) ||
        empty($data['description'])
    ) {
        sendError('week_id, title, start_date and description are required', 400);
    }

    $weekId     = sanitizeInput($data['week_id']);
    $title      = sanitizeInput($data['title']);
    $description= sanitizeInput($data['description']);
    $startDate  = $data['start_date'];

    if (!validateDate($startDate)) {
        sendError('start_date must be in YYYY-MM-DD format', 400);
    }

    // check duplicate week_id
    $checkSql = "SELECT week_id FROM weeks WHERE week_id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$weekId]);
    if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendError('week_id already exists', 409);
    }

    // links
    $linksArr  = (isset($data['links']) && is_array($data['links'])) ? $data['links'] : [];
    $linksJson = json_encode($linksArr);

    $insertSql = "INSERT INTO weeks (week_id, title, start_date, description, links)
                  VALUES (?, ?, ?, ?, ?)";

    $stmt = $db->prepare($insertSql);
    $ok   = $stmt->execute([$weekId, $title, $startDate, $description, $linksJson]);

    if (!$ok) {
        sendError('Failed to create week', 500);
    }

    sendResponse([
        'success' => true,
        'data'    => [
            'week_id'    => $weekId,
            'title'      => $title,
            'start_date' => $startDate,
            'description'=> $description,
            'links'      => $linksArr
        ]
    ], 201);
}

function updateWeek($db, $data) {
    if (empty($data['week_id'])) {
        sendError('week_id is required for update', 400);
    }

    $weekId = sanitizeInput($data['week_id']);

    // check existing
    $checkSql = "SELECT week_id FROM weeks WHERE week_id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$weekId]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendError('Week not found', 404);
    }

    $setParts = [];
    $values   = [];

    if (isset($data['title'])) {
        $setParts[] = "title = ?";
        $values[]   = sanitizeInput($data['title']);
    }

    if (isset($data['start_date'])) {
        $startDate = $data['start_date'];
        if (!validateDate($startDate)) {
            sendError('start_date must be in YYYY-MM-DD format', 400);
        }
        $setParts[] = "start_date = ?";
        $values[]   = $startDate;
    }

    if (isset($data['description'])) {
        $setParts[] = "description = ?";
        $values[]   = sanitizeInput($data['description']);
    }

    if (isset($data['links'])) {
        if (is_array($data['links'])) {
            $linksJson = json_encode($data['links']);
        } else {
            $linksJson = json_encode([]);
        }
        $setParts[] = "links = ?";
        $values[]   = $linksJson;
    }

    if (empty($setParts)) {
        sendError('No fields to update', 400);
    }

    $setParts[] = "updated_at = CURRENT_TIMESTAMP";

    $sql = "UPDATE weeks SET " . implode(', ', $setParts) . " WHERE week_id = ?";

    $values[] = $weekId;

    $stmt = $db->prepare($sql);
    $ok   = $stmt->execute($values);

    if (!$ok) {
        sendError('Failed to update week', 500);
    }

    // return updated row
    getWeekById($db, $weekId);
}

function deleteWeek($db, $weekId) {
    if (empty($weekId)) {
        sendError('week_id is required for delete', 400);
    }

    // check exists
    $checkSql = "SELECT week_id FROM weeks WHERE week_id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$weekId]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendError('Week not found', 404);
    }

    // delete comments first
    $delCommentsSql = "DELETE FROM comments WHERE week_id = ?";
    $delCommentsStmt = $db->prepare($delCommentsSql);
    $delCommentsStmt->execute([$weekId]);

    // delete week
    $delWeekSql = "DELETE FROM weeks WHERE week_id = ?";
    $delWeekStmt = $db->prepare($delWeekSql);
    $ok = $delWeekStmt->execute([$weekId]);

    if (!$ok) {
        sendError('Failed to delete week', 500);
    }

    sendResponse([
        'success' => true,
        'message' => 'Week and related comments deleted'
    ]);
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

function getCommentsByWeek($db, $weekId) {
    if (empty($weekId)) {
        sendError('week_id is required', 400);
    }

    $sql = "SELECT id, week_id, author, text, created_at
            FROM comments
            WHERE week_id = ?
            ORDER BY created_at ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$weekId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data'    => $rows
    ]);
}

function createComment($db, $data) {
    if (empty($data['week_id']) || empty($data['author']) || empty($data['text'])) {
        sendError('week_id, author and text are required', 400);
    }

    $weekId = sanitizeInput($data['week_id']);
    $author = sanitizeInput($data['author']);
    $text   = trim($data['text']);

    if ($text === '') {
        sendError('Comment text cannot be empty', 400);
    }

    // make sure week exists
    $checkSql = "SELECT week_id FROM weeks WHERE week_id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$weekId]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendError('Week not found for this comment', 404);
    }

    $insertSql = "INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)";
    $stmt = $db->prepare($insertSql);
    $ok   = $stmt->execute([$weekId, $author, $text]);

    if (!$ok) {
        sendError('Failed to create comment', 500);
    }

    $id = $db->lastInsertId();

    sendResponse([
        'success' => true,
        'data'    => [
            'id'      => (int)$id,
            'week_id' => $weekId,
            'author'  => $author,
            'text'    => $text
        ]
    ], 201);
}

function deleteComment($db, $commentId) {
    if (empty($commentId)) {
        sendError('id is required for delete', 400);
    }

    $checkSql = "SELECT id FROM comments WHERE id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$commentId]);
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendError('Comment not found', 404);
    }

    $delSql = "DELETE FROM comments WHERE id = ?";
    $stmt = $db->prepare($delSql);
    $ok   = $stmt->execute([$commentId]);

    if (!$ok) {
        sendError('Failed to delete comment', 500);
    }

    sendResponse([
        'success' => true,
        'message' => 'Comment deleted'
    ]);
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {

    if ($resource === 'weeks') {

        if ($method === 'GET') {
            if (isset($_GET['week_id'])) {
                getWeekById($db, $_GET['week_id']);
            } else {
                getAllWeeks($db);
            }

        } elseif ($method === 'POST') {
            createWeek($db, $inputData);

        } elseif ($method === 'PUT') {
            updateWeek($db, $inputData);

        } elseif ($method === 'DELETE') {
            $weekId = isset($_GET['week_id'])
                ? $_GET['week_id']
                : (isset($inputData['week_id']) ? $inputData['week_id'] : null);
            deleteWeek($db, $weekId);

        } else {
            sendError('Method not allowed', 405);
        }

    } elseif ($resource === 'comments') {

        if ($method === 'GET') {
            $weekId = isset($_GET['week_id']) ? $_GET['week_id'] : null;
            getCommentsByWeek($db, $weekId);

        } elseif ($method === 'POST') {
            createComment($db, $inputData);

        } elseif ($method === 'DELETE') {
            $commentId = isset($_GET['id'])
                ? $_GET['id']
                : (isset($inputData['id']) ? $inputData['id'] : null);
            deleteComment($db, $commentId);

        } else {
            sendError('Method not allowed', 405);
        }

    } else {
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }

} catch (PDOException $e) {
    // ممكن تسجّل الخطأ في اللوج لو حاب
    // error_log($e->getMessage());
    sendError('Database error occurred', 500);

} catch (Exception $e) {
    // error_log($e->getMessage());
    sendError('Server error occurred', 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function sendError($message, $statusCode = 400) {
    $error = [
        'success' => false,
        'error'   => $message
    ];
    sendResponse($error, $statusCode);
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function isValidSortField($field, $allowedFields) {
    return in_array($field, $allowedFields, true);
}

?>
