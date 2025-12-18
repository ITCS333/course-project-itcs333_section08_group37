<?php
/**
 * Discussion Board API
 * 
 * This is a RESTful API that handles all CRUD operations for the discussion board.
 * It manages both discussion topics and their replies.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: topics
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - topic_id (VARCHAR(50), UNIQUE) - The topic's unique identifier (e.g., "topic_1234567890")
 *   - subject (VARCHAR(255)) - The topic subject/title
 *   - message (TEXT) - The main topic message
 *   - author (VARCHAR(100)) - The author's name
 *   - created_at (TIMESTAMP) - When the topic was created
 * 
 * Table: replies
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - reply_id (VARCHAR(50), UNIQUE) - The reply's unique identifier (e.g., "reply_1234567890")
 *   - topic_id (VARCHAR(50)) - Foreign key to topics.topic_id
 *   - text (TEXT) - The reply message
 *   - author (VARCHAR(100)) - The reply author's name
 *   - created_at (TIMESTAMP) - When the reply was created
 * 
 * API Endpoints:
 * 
 * Topics:
 *   GET    /api/discussion.php?resource=topics              - Get all topics (with optional search)
 *   GET    /api/discussion.php?resource=topics&id={id}      - Get single topic
 *   POST   /api/discussion.php?resource=topics              - Create new topic
 *   PUT    /api/discussion.php?resource=topics              - Update a topic
 *   DELETE /api/discussion.php?resource=topics&id={id}      - Delete a topic
 * 
 * Replies:
 *   GET    /api/discussion.php?resource=replies&topic_id={id} - Get all replies for a topic
 *   POST   /api/discussion.php?resource=replies              - Create new reply
 *   DELETE /api/discussion.php?resource=replies&id={id}      - Delete a reply
 * 
 * Response Format: JSON
 */

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

/**
 * Function: Get all topics or search for specific topics
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by subject, message, or author
 *   - sort: Optional field to sort by (subject, author, created_at)
 *   - order: Optional sort order (asc or desc, default: desc)
 */
function getAllTopics($db) {
    $sql = "SELECT topic_id AS id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d') AS date FROM topics";
    $params = [];
    $clauses = [];

    if (!empty($_GET['search'])) {
        $clauses[] = "(subject LIKE :search OR message LIKE :search OR author LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    if ($clauses) {
        $sql .= ' WHERE ' . implode(' AND ', $clauses);
    }

    $allowedSorts = ['subject', 'author', 'created_at'];
    $sort = $_GET['sort'] ?? 'created_at';
    if (!in_array($sort, $allowedSorts, true)) {
        $sort = 'created_at';
    }

    $order = strtolower($_GET['order'] ?? 'desc');
    $order = $order === 'asc' ? 'ASC' : 'DESC';

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'data' => $results]);
}

/**
 * Function: Get a single topic by topic_id
 * Method: GET
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function getTopicById($db, $topicId) {
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


/**
 * Function: Create a new topic
 * Method: POST
 * 
 * Required JSON Body:
 *   - topic_id: Unique identifier (e.g., "topic_1234567890")
 *   - subject: Topic subject/title
 *   - message: Main topic message
 *   - author: Author's name
 */
function createTopic($db, $data) {
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


/**
 * Function: Update an existing topic
 * Method: PUT
 * 
 * Required JSON Body:
 *   - topic_id: The topic's unique identifier
 *   - subject: Updated subject (optional)
 *   - message: Updated message (optional)
 */
function updateTopic($db, $data) {
    $topicId = $data['topic_id'] ?? null;

    if (empty($topicId)) {
        sendResponse(['success' => false, 'error' => 'topic_id is required'], 400);
    }

    $existsStmt = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :topic_id LIMIT 1");
    $existsStmt->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $existsStmt->execute();

    if (!$existsStmt->fetch()) {
        sendResponse(['success' => false, 'error' => 'Topic not found'], 404);
    }

    $updates = [];
    $params = [':topic_id' => $topicId];

    if (!empty($data['subject'])) {
        $updates[] = 'subject = :subject';
        $params[':subject'] = sanitizeInput($data['subject']);
    }

    if (!empty($data['message'])) {
        $updates[] = 'message = :message';
        $params[':message'] = sanitizeInput($data['message']);
    }

    if (!$updates) {
        sendResponse(['success' => false, 'error' => 'No fields provided for update'], 400);
    }

    $sql = 'UPDATE topics SET ' . implode(', ', $updates) . ' WHERE topic_id = :topic_id';
    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }

    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Topic updated']);
    } else {
        sendResponse(['success' => true, 'message' => 'No changes made']);
    }
}


/**
 * Function: Delete a topic
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function deleteTopic($db, $topicId) {
    if (empty($topicId)) {
        sendResponse(['success' => false, 'error' => 'Topic ID is required'], 400);
    }

    $existsStmt = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :topic_id LIMIT 1");
    $existsStmt->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $existsStmt->execute();

    if (!$existsStmt->fetch()) {
        sendResponse(['success' => false, 'error' => 'Topic not found'], 404);
    }

    $deleteReplies = $db->prepare("DELETE FROM replies WHERE topic_id = :topic_id");
    $deleteReplies->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $deleteReplies->execute();

    $deleteTopic = $db->prepare("DELETE FROM topics WHERE topic_id = :topic_id");
    $deleteTopic->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $deleteTopic->execute();

    if ($deleteTopic->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Topic deleted']);
    } else {
        sendResponse(['success' => false, 'error' => 'Failed to delete topic'], 500);
    }
}


// ============================================================================
// REPLIES FUNCTIONS
// ============================================================================

/**
 * Function: Get all replies for a specific topic
 * Method: GET
 * 
 * Query Parameters:
 *   - topic_id: The topic's unique identifier
 */
function getRepliesByTopicId($db, $topicId) {
    if (empty($topicId)) {
        sendResponse(['success' => false, 'error' => 'topic_id is required'], 400);
    }

    $sql = "SELECT reply_id AS id, topic_id, text, author, DATE_FORMAT(created_at, '%Y-%m-%d') AS date FROM replies WHERE topic_id = :topic_id ORDER BY created_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $stmt->execute();

    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $replies]);
}


/**
 * Function: Create a new reply
 * Method: POST
 * 
 * Required JSON Body:
 *   - reply_id: Unique identifier (e.g., "reply_1234567890")
 *   - topic_id: The parent topic's identifier
 *   - text: Reply message text
 *   - author: Author's name
 */
function createReply($db, $data) {
    $requiredFields = ['reply_id', 'topic_id', 'text', 'author'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse(['success' => false, 'error' => "{$field} is required"], 400);
        }
    }

    $replyId = sanitizeInput($data['reply_id']);
    $topicId = sanitizeInput($data['topic_id']);
    $text = sanitizeInput($data['text']);
    $author = sanitizeInput($data['author']);

    $topicCheck = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :topic_id LIMIT 1");
    $topicCheck->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $topicCheck->execute();

    if (!$topicCheck->fetch()) {
        sendResponse(['success' => false, 'error' => 'Parent topic not found'], 404);
    }

    $replyCheck = $db->prepare("SELECT reply_id FROM replies WHERE reply_id = :reply_id LIMIT 1");
    $replyCheck->bindValue(':reply_id', $replyId, PDO::PARAM_STR);
    $replyCheck->execute();

    if ($replyCheck->fetch()) {
        sendResponse(['success' => false, 'error' => 'Reply already exists'], 409);
    }

    $sql = "INSERT INTO replies (reply_id, topic_id, text, author) VALUES (:reply_id, :topic_id, :text, :author)";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':reply_id', $replyId, PDO::PARAM_STR);
    $stmt->bindValue(':topic_id', $topicId, PDO::PARAM_STR);
    $stmt->bindValue(':text', $text, PDO::PARAM_STR);
    $stmt->bindValue(':author', $author, PDO::PARAM_STR);

    if ($stmt->execute()) {
        sendResponse(['success' => true, 'reply_id' => $replyId], 201);
    } else {
        sendResponse(['success' => false, 'error' => 'Failed to create reply'], 500);
    }
}


/**
 * Function: Delete a reply
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The reply's unique identifier
 */
function deleteReply($db, $replyId) {
    if (empty($replyId)) {
        sendResponse(['success' => false, 'error' => 'Reply ID is required'], 400);
    }

    $existsStmt = $db->prepare("SELECT reply_id FROM replies WHERE reply_id = :reply_id LIMIT 1");
    $existsStmt->bindValue(':reply_id', $replyId, PDO::PARAM_STR);
    $existsStmt->execute();

    if (!$existsStmt->fetch()) {
        sendResponse(['success' => false, 'error' => 'Reply not found'], 404);
    }

    $deleteReply = $db->prepare("DELETE FROM replies WHERE reply_id = :reply_id");
    $deleteReply->bindValue(':reply_id', $replyId, PDO::PARAM_STR);
    $deleteReply->execute();

    if ($deleteReply->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Reply deleted']);
    } else {
        sendResponse(['success' => false, 'error' => 'Failed to delete reply'], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    $resource = $_GET['resource'] ?? null;

    if (!$resource || !isValidResource($resource)) {
        sendResponse(['success' => false, 'error' => 'Invalid resource'], 400);
    }

    switch ($resource) {
        case 'topics':
            switch ($method) {
                case 'GET':
                    $topicId = $_GET['id'] ?? null;
                    if ($topicId !== null) {
                        getTopicById($db, $topicId);
                    } else {
                        getAllTopics($db);
                    }
                    break;
                case 'POST':
                    createTopic($db, $requestData);
                    break;
                case 'PUT':
                    updateTopic($db, $requestData);
                    break;
                case 'DELETE':
                    $topicId = $_GET['id'] ?? ($requestData['id'] ?? ($requestData['topic_id'] ?? null));
                    deleteTopic($db, $topicId);
                    break;
                default:
                    header('Allow: GET, POST, PUT, DELETE, OPTIONS');
                    sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            }
            break;

        case 'replies':
            switch ($method) {
                case 'GET':
                    $topicId = $_GET['topic_id'] ?? ($_GET['id'] ?? null);
                    getRepliesByTopicId($db, $topicId);
                    break;
                case 'POST':
                    createReply($db, $requestData);
                    break;
                case 'DELETE':
                    $replyId = $_GET['id'] ?? ($requestData['id'] ?? ($requestData['reply_id'] ?? null));
                    deleteReply($db, $replyId);
                    break;
                default:
                    header('Allow: GET, POST, PUT, DELETE, OPTIONS');
                    sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            }
            break;

        default:
            sendResponse(['success' => false, 'error' => 'Unsupported resource'], 400);
    }
} catch (PDOException $e) {
    sendResponse(['success' => false, 'error' => 'Database error'], 500);
} catch (Exception $e) {
    sendResponse(['success' => false, 'error' => 'Server error'], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);

    $encoded = json_encode($data);
    if ($encoded === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to encode response']);
        exit;
    }

    echo $encoded;
    exit;
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    if (!is_string($data)) {
        return $data;
    }

    $clean = trim($data);
    $clean = strip_tags($clean);
    $clean = htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');

    return $clean;
}


/**
 * Helper function to validate resource name
 * 
 * @param string $resource - Resource name to validate
 * @return bool - True if valid, false otherwise
 */
function isValidResource($resource) {
    $allowed = ['topics', 'replies'];
    return in_array($resource, $allowed, true);
}

?>
