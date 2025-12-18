<?php
session_start();
/**
 * Weekly Course Breakdown API
 *
 * RESTful API for weekly course content and comments.
 * Uses PDO + MySQL.
 *
 * Tables:
 *  - weeks(id, week_id, title, start_date, description, links, created_at, updated_at)
 *  - comments(id, week_id, author, text, created_at)
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// Headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include DB connection (adjust the path if needed)
require_once __DIR__ . '/../common/db.php';


// Request method + body + resource
$method = $_SERVER['REQUEST_METHOD'];
$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true) ?? [];

$resource = $_GET['resource'] ?? 'weeks';

// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

function getAllWeeks($db) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $sort   = $_GET['sort'] ?? 'start_date';
    $order  = strtolower($_GET['order'] ?? 'asc');

    $allowedSort = ['title', 'start_date', 'created_at'];
    if (!isValidSortField($sort, $allowedSort)) {
        $sort = 'start_date';
    }
    if (!in_array($order, ['asc', 'desc'], true)) {
        $order = 'asc';
    }

    $sql = "SELECT week_id, title, start_date, description, links, created_at 
            FROM weeks";
    $params = [];

    if ($search) {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $like = "%{$search}%";
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY {$sort} {$order}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['links'] = $row['links']
            ? json_decode($row['links'], true)
            : [];
    }

    sendResponse([
        'success' => true,
        'data'    => $rows
    ]);
}

function getWeekById($db, $weekId) {
    if (!$weekId) {
        sendError("week_id is required", 400);
        return;
    }

    $stmt = $db->prepare(
        "SELECT week_id, title, start_date, description, links, created_at
         FROM weeks
         WHERE week_id = ?"
    );
    $stmt->execute([$weekId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        sendError("Week not found", 404);
        return;
    }

    $row['links'] = $row['links']
        ? json_decode($row['links'], true)
        : [];

    sendResponse([
        'success' => true,
        'data'    => $row
    ]);
}

function createWeek($db, $data) {
    $required = ['week_id', 'title', 'start_date', 'description'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendError("Missing required field: {$field}", 400);
            return;
        }
    }

    $weekId = sanitizeInput($data['week_id']);
    $title  = sanitizeInput($data['title']);
    $desc   = sanitizeInput($data['description']);
    $date   = $data['start_date'];

    if (!validateDate($date)) {
        sendError("Invalid date format, expected YYYY-MM-DD", 400);
        return;
    }

    // Check duplicate
    $check = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
    $check->execute([$weekId]);
    if ($check->fetch()) {
        sendError("Week already exists", 409);
        return;
    }

    $linksArray = [];
    if (isset($data['links']) && is_array($data['links'])) {
        $linksArray = $data['links'];
    }
    $linksJson = json_encode($linksArray);

    $stmt = $db->prepare(
        "INSERT INTO weeks (week_id, title, start_date, description, links)
         VALUES (?, ?, ?, ?, ?)"
    );

    $ok = $stmt->execute([$weekId, $title, $date, $desc, $linksJson]);
    if (!$ok) {
        sendError("Failed to create week", 500);
        return;
    }

    sendResponse([
        'success' => true,
        'data'    => [
            'week_id'     => $weekId,
            'title'       => $title,
            'start_date'  => $date,
            'description' => $desc,
            'links'       => $linksArray
        ]
    ], 201);
}

function updateWeek($db, $data) {
    if (empty($data['week_id'])) {
        sendError("week_id is required", 400);
        return;
    }
    $weekId = $data['week_id'];

    // Check exists
    $check = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
    $check->execute([$weekId]);
    if (!$check->fetch()) {
        sendError("Week not found", 404);
        return;
    }

    $fields = [];
    $values = [];

    if (!empty($data['title'])) {
        $fields[] = "title = ?";
        $values[] = sanitizeInput($data['title']);
    }

    if (!empty($data['start_date'])) {
        if (!validateDate($data['start_date'])) {
            sendError("Invalid date format", 400);
            return;
        }
        $fields[] = "start_date = ?";
        $values[] = $data['start_date'];
    }

    if (!empty($data['description'])) {
        $fields[] = "description = ?";
        $values[] = sanitizeInput($data['description']);
    }

    if (array_key_exists('links', $data)) {
        if (is_array($data['links'])) {
            $fields[] = "links = ?";
            $values[] = json_encode($data['links']);
        }
    }

    if (empty($fields)) {
        sendError("No fields to update", 400);
        return;
    }

    $fields[] = "updated_at = CURRENT_TIMESTAMP";

    $sql = "UPDATE weeks SET " . implode(", ", $fields) . " WHERE week_id = ?";
    $values[] = $weekId;

    $stmt = $db->prepare($sql);
    $ok = $stmt->execute($values);

    if (!$ok) {
        sendError("Failed to update week", 500);
        return;
    }

    sendResponse([
        'success' => true,
        'message' => "Week updated successfully"
    ]);
}

function deleteWeek($db, $weekId) {
    if (!$weekId) {
        sendError("week_id is required", 400);
        return;
    }

    // Check exists
    $check = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
    $check->execute([$weekId]);
    if (!$check->fetch()) {
        sendError("Week not found", 404);
        return;
    }

    // Delete comments first
    $delComments = $db->prepare("DELETE FROM comments WHERE week_id = ?");
    $delComments->execute([$weekId]);

    // Delete week
    $delWeek = $db->prepare("DELETE FROM weeks WHERE week_id = ?");
    $ok = $delWeek->execute([$weekId]);

    if (!$ok) {
        sendError("Failed to delete week", 500);
        return;
    }

    sendResponse([
        'success' => true,
        'message' => "Week and related comments deleted"
    ]);
}

// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

function getCommentsByWeek($db, $weekId) {
    if (!$weekId) {
        sendError("week_id is required", 400);
        return;
    }

    $stmt = $db->prepare(
        "SELECT id, week_id, author, text, created_at
         FROM comments
         WHERE week_id = ?
         ORDER BY created_at ASC"
    );
    $stmt->execute([$weekId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data'    => $rows
    ]);
}

function createComment($db, $data) {
    $required = ['week_id', 'author', 'text'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendError("Missing required field: {$field}", 400);
            return;
        }
    }

    $weekId = sanitizeInput($data['week_id']);
    $author = sanitizeInput($data['author']);
    $text   = trim($data['text']);

    if ($text === '') {
        sendError("Comment text cannot be empty", 400);
        return;
    }

    // Check week exists
    $check = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
    $check->execute([$weekId]);
    if (!$check->fetch()) {
        sendError("Week not found for this comment", 404);
        return;
    }

    $stmt = $db->prepare(
        "INSERT INTO comments (week_id, author, text)
         VALUES (?, ?, ?)"
    );
    $ok = $stmt->execute([$weekId, $author, $text]);

    if (!$ok) {
        sendError("Failed to create comment", 500);
        return;
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
    if (!$commentId) {
        sendError("id is required", 400);
        return;
    }

    $check = $db->prepare("SELECT id FROM comments WHERE id = ?");
    $check->execute([$commentId]);
    if (!$check->fetch()) {
        sendError("Comment not found", 404);
        return;
    }

    $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    $ok = $stmt->execute([$commentId]);

    if (!$ok) {
        sendError("Failed to delete comment", 500);
        return;
    }

    sendResponse([
        'success' => true,
        'message' => "Comment deleted"
    ]);
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    if ($resource === 'weeks') {
        if ($method === 'GET') {
            $weekId = $_GET['week_id'] ?? null;
            if ($weekId) {
                getWeekById($db, $weekId);
            } else {
                getAllWeeks($db);
            }
        } elseif ($method === 'POST') {
            createWeek($db, $input);
        } elseif ($method === 'PUT') {
            updateWeek($db, $input);
        } elseif ($method === 'DELETE') {
            $weekId = $_GET['week_id'] ?? ($input['week_id'] ?? null);
            deleteWeek($db, $weekId);
        } else {
            sendError("Method not allowed", 405);
        }
    } elseif ($resource === 'comments') {
        if ($method === 'GET') {
            $weekId = $_GET['week_id'] ?? null;
            getCommentsByWeek($db, $weekId);
        } elseif ($method === 'POST') {
            createComment($db, $input);
        } elseif ($method === 'DELETE') {
            $commentId = $_GET['id'] ?? ($input['id'] ?? null);
            deleteComment($db, $commentId);
        } else {
            sendError("Method not allowed", 405);
        }
    } else {
        sendError("Invalid resource. Use 'weeks' or 'comments'.", 400);
    }
} catch (PDOException $e) {
    // Optional: error_log($e->getMessage());
    sendError("Database error occurred", 500);
} catch (Exception $e) {
    // Optional: error_log($e->getMessage());
    sendError("An unexpected error occurred", 500);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $statusCode = 400) {
    $payload = [
        'success' => false,
        'error'   => $message
    ];
    sendResponse($payload, $statusCode);
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $data;
}

function isValidSortField($field, $allowedFields) {
    return in_array($field, $allowedFields, true);
}
