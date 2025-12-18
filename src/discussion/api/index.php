<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../common/db.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$rawBody = file_get_contents('php://input');
$requestData = [];
if (!empty($rawBody)) {
    $requestData = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(['success' => false, 'error' => 'Invalid JSON body'], 400);
    }
}

// ============================================================================
// TOPICS FUNCTIONS
// ============================================================================

function getAllTopics($db) {
    global $db; // ضروري للاختبارات
    $sql = "SELECT topic_id AS id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d') AS date FROM topics";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'data' => $results]);
}

function getTopicById($db, $topicId) {
    global $db;
    if (empty($topicId)) {
        sendResponse(['success' => false, 'error' => 'Topic ID is required'], 400);
    }

    $sql = "SELECT topic_id AS id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d') AS date FROM topics WHERE topic_id = :topic_id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $stmt->execute();

    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($topic) {
        sendResponse(['success' => true, 'data' => $topic]);
    } else {
        sendResponse(['success' => false, 'error' => 'Topic not found'], 404);
    }
}

function createTopic($db, $data) {
    global $db;
    $requiredFields = ['topic_id', 'subject', 'message', 'author'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse(['success' => false, 'error' => "{$field} is required"], 400);
        }
    }

    $topicId = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = sanitizeInput($data['author']);

    $checkStmt = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :topic_id LIMIT 1");
    $checkStmt->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $checkStmt->execute();
    if ($checkStmt->fetch()) {
        sendResponse(['success' => false, 'error' => 'Topic already exists'], 409);
    }

    $insertSql = "INSERT INTO topics (topic_id, subject, message, author) VALUES (:topic_id, :subject, :message, :author)";
    $stmt = $db->prepare($insertSql);
    $stmt->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
    $stmt->bindValue(':message', $message, PDO::PARAM_STR);
    $stmt->bindValue(':author', $author, PDO::PARAM_STR);

    if ($stmt->execute()) {
        sendResponse(['success' => true, 'topic_id' => $topicId], 201);
    } else {
        sendResponse(['success' => false, 'error' => 'Failed to create topic'], 500);
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitizeInput($data) {
    if (!is_string($data)) return $data;
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// ============================================================================
// MAIN ROUTER
// ============================================================================

$resource = $_GET['resource'] ?? null;
if (!$resource || !in_array($resource, ['topics'], true)) {
    sendResponse(['success' => false, 'error' => 'Invalid resource'], 400);
}

switch ($resource) {
    case 'topics':
        switch ($method) {
            case 'GET':
                $topicId = $_GET['id'] ?? null;
                if ($topicId) getTopicById($db, $topicId);
                else getAllTopics($db);
                break;
            case 'POST':
                createTopic($db, $requestData);
                break;
            default:
                sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }
        break;
}
?>
