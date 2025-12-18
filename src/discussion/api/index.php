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

// =================== Helper Functions ===================
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitizeInput($data) {
    if (!is_string($data)) return $data;
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// =================== Topics CRUD ===================
function getAllTopics($db) {
    $stmt = $db->prepare("SELECT topic_id AS id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at FROM topics ORDER BY created_at ASC");
    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $topics]);
}

function getTopicById($db, $topicId) {
    if (!$topicId) sendResponse(['success'=>false,'error'=>'Topic ID is required'],400);
    $stmt = $db->prepare("SELECT topic_id AS id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at FROM topics WHERE topic_id=? LIMIT 1");
    $stmt->execute([$topicId]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$topic) sendResponse(['success'=>false,'error'=>'Topic not found'],404);
    sendResponse(['success'=>true,'data'=>$topic]);
}

function createTopic($db, $data) {
    $required = ['topic_id','subject','message','author'];
    foreach($required as $f){
        if(empty($data[$f])) sendResponse(['success'=>false,'error'=>"$f is required"],400);
    }
    $topicId = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = sanitizeInput($data['author']);

    $check = $db->prepare("SELECT topic_id FROM topics WHERE topic_id=? LIMIT 1");
    $check->execute([$topicId]);
    if($check->fetch()) sendResponse(['success'=>false,'error'=>'Topic already exists'],409);

    $stmt = $db->prepare("INSERT INTO topics (topic_id, subject, message, author) VALUES (?,?,?,?)");
    $ok = $stmt->execute([$topicId,$subject,$message,$author]);
    if(!$ok) sendResponse(['success'=>false,'error'=>'Failed to create topic'],500);

    sendResponse([
        'success'=>true,
        'data'=>[
            'id'=>$topicId,
            'subject'=>$subject,
            'message'=>$message,
            'author'=>$author
        ]
    ],201);
}

// =================== Router ===================
$resource = $_GET['resource'] ?? null;
if (!$resource || !in_array($resource,['topics'],true)) sendResponse(['success'=>false,'error'=>'Invalid resource'],400);

switch($resource){
    case 'topics':
        switch($method){
            case 'GET':
                $id = $_GET['id'] ?? null;
                if($id) getTopicById($db,$id);
                else getAllTopics($db);
                break;
            case 'POST':
                createTopic($db,$requestData);
                break;
            default:
                sendResponse(['success'=>false,'error'=>'Method not allowed'],405);
        }
        break;
}
?>
