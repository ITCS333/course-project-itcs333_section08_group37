<?php
session_start();
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

// TODO: Include the database connection class
require_once "Database.php";

// TODO: Create database connection
$db = (new Database())->getConnection();

// TODO: Set PDO to throw exceptions on errors
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
    // TODO: Start building the SQL query
    $sql = "SELECT * FROM assignments";
    $params = [];

    // TODO: Check if 'search' query parameter exists in $_GET
    if (!empty($_GET['search'])) {
        $sql .= " WHERE title LIKE :search";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    // TODO: Check if 'sort' and 'order' query parameters exist
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';

    // TODO: Prepare the SQL statement using $db->prepare()
    $stmt = $db->prepare($sql . " ORDER BY $sort $order");

    // TODO: Bind parameters if search is used
    // (bound via execute array)

    // TODO: Execute the prepared statement
    $stmt->execute($params);

    // TODO: Fetch all results as associative array
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: For each assignment, decode the 'files' field from JSON to array
    foreach ($rows as &$r) {
        $r['files'] = json_decode($r['files'], true);
    }

    // TODO: Return JSON response
    sendResponse($rows);
}

function getAssignmentById($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (!$assignmentId) sendResponse(["error"=>"ID required"],400);

    // TODO: Prepare SQL query to select assignment by id
    $stmt = $db->prepare("SELECT * FROM assignments WHERE id=:id");

    // TODO: Bind the :id parameter
    $stmt->bindParam(":id",$assignmentId);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Fetch the result as associative array
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if assignment was found
    if (!$row) sendResponse(["error"=>"Not found"],404);

    // TODO: Decode the 'files' field from JSON to array
    $row['files'] = json_decode($row['files'],true);

    // TODO: Return success response with assignment data
    sendResponse($row);
}

function createAssignment($db, $data) {
    // TODO: Validate required fields
    if (empty($data['title']) || empty($data['due_date'])) {
        sendResponse(["error"=>"Missing fields"],400);
    }

    // TODO: Sanitize input data
    $title = sanitizeInput($data['title']);
    $desc  = sanitizeInput($data['description'] ?? '');

    // TODO: Validate due_date format
    if (!validateDate($data['due_date'])) {
        sendResponse(["error"=>"Invalid date"],400);
    }

    // TODO: Generate a unique assignment ID
    // (AUTO_INCREMENT handled by DB)

    // TODO: Handle the 'files' field
    $files = json_encode($data['files'] ?? []);

    // TODO: Prepare INSERT query
    $stmt = $db->prepare("
        INSERT INTO assignments (title, description, due_date, files)
        VALUES (:t,:d,:dd,:f)
    ");

    // TODO: Bind all parameters
    $stmt->execute([
        ':t'=>$title,
        ':d'=>$desc,
        ':dd'=>$data['due_date'],
        ':f'=>$files
    ]);

    // TODO: Execute the statement
    // (executed above)

    // TODO: Check if insert was successful
    // TODO: If insert failed, return 500 error
    sendResponse(["message"=>"Assignment created"],201);
}

function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
    if (empty($data['id'])) sendResponse(["error"=>"ID required"],400);

    // TODO: Store assignment ID in variable
    $id = $data['id'];

    // TODO: Check if assignment exists
    $check = $db->prepare("SELECT id FROM assignments WHERE id=:id");
    $check->execute([':id'=>$id]);
    if (!$check->fetch()) sendResponse(["error"=>"Not found"],404);

    // TODO: Build UPDATE query dynamically based on provided fields
    $fields = [];
    $params = [':id'=>$id];

    // TODO: Check which fields are provided and add to SET clause
    foreach (['title','description','due_date','files'] as $f) {
        if (isset($data[$f])) {
            $fields[] = "$f=:$f";
            $params[":$f"] = ($f==='files')
                ? json_encode($data[$f])
                : sanitizeInput($data[$f]);
        }
    }

    // TODO: If no fields to update (besides updated_at), return 400 error
    if (!$fields) sendResponse(["error"=>"Nothing to update"],400);

    // TODO: Complete the UPDATE query
    $sql = "UPDATE assignments SET ".implode(',',$fields)." WHERE id=:id";

    // TODO: Prepare the statement
    $stmt = $db->prepare($sql);

    // TODO: Bind all parameters dynamically
    // (bound via execute)

    // TODO: Execute the statement
    $stmt->execute($params);

    // TODO: Check if update was successful
    // TODO: If no rows affected, return appropriate message
    sendResponse(["message"=>"Updated"]);
}

function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (!$assignmentId) sendResponse(["error"=>"ID required"],400);

    // TODO: Check if assignment exists
    // TODO: Delete associated comments first (due to foreign key constraint)
    $db->prepare("DELETE FROM comments WHERE assignment_id=:id")
       ->execute([':id'=>$assignmentId]);

    // TODO: Prepare DELETE query for assignment
    $stmt = $db->prepare("DELETE FROM assignments WHERE id=:id");

    // TODO: Bind the :id parameter
    $stmt->bindParam(":id",$assignmentId);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Check if delete was successful
    // TODO: If delete failed, return 500 error
    sendResponse(["message"=>"Deleted"]);
}

/* ============================================================================
   COMMENT CRUD FUNCTIONS
============================================================================ */

function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (!$assignmentId) sendResponse(["error"=>"Assignment ID required"],400);

    // TODO: Prepare SQL query to select all comments for the assignment
    $stmt = $db->prepare("SELECT * FROM comments WHERE assignment_id=:id");

    // TODO: Bind the :assignment_id parameter
    $stmt->bindParam(":id",$assignmentId);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Fetch all results as associative array
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return success response with comments data
    sendResponse($rows);
}

function createComment($db, $data) {
    // TODO: Validate required fields
    if (empty($data['assignment_id']) || empty(trim($data['text']))) {
        sendResponse(["error"=>"Missing fields"],400);
    }

    // TODO: Sanitize input data
    $text = sanitizeInput($data['text']);
    $author = sanitizeInput($data['author'] ?? 'Anonymous');

    // TODO: Validate that text is not empty after trimming
    // (validated above)

    // TODO: Verify that the assignment exists
    $check = $db->prepare("SELECT id FROM assignments WHERE id=:id");
    $check->execute([':id'=>$data['assignment_id']]);
    if (!$check->fetch()) sendResponse(["error"=>"Assignment not found"],404);

    // TODO: Prepare INSERT query for comment
    $stmt = $db->prepare("
        INSERT INTO comments (assignment_id, author, text)
        VALUES (:a,:u,:t)
    ");

    // TODO: Bind all parameters
    $stmt->execute([
        ':a'=>$data['assignment_id'],
        ':u'=>$author,
        ':t'=>$text
    ]);

    // TODO: Execute the statement
    // TODO: Get the ID of the inserted comment
    // TODO: Return success response with created comment data
    sendResponse(["message"=>"Comment created"],201);
}

function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
    if (!$commentId) sendResponse(["error"=>"ID required"],400);

    // TODO: Check if comment exists
    // TODO: Prepare DELETE query
    $stmt = $db->prepare("DELETE FROM comments WHERE id=:id");

    // TODO: Bind the :id parameter
    $stmt->bindParam(":id",$commentId);

    // TODO: Execute the statement
    $stmt->execute();

    // TODO: Check if delete was successful
    // TODO: If delete failed, return 500 error
    sendResponse(["message"=>"Comment deleted"]);
}

/* ============================================================================
   ROUTER
============================================================================ */

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
    if ($method === 'GET') {
        // TODO: Handle GET requests
        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
            isset($_GET['id']) ? getAssignmentById($db,$_GET['id']) : getAllAssignments($db);
        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
            getCommentsByAssignment($db,$_GET['assignment_id'] ?? null);
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(["error"=>"Invalid resource"],400);
        }
    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)
        if ($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
            createAssignment($db,$data);
        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
            createComment($db,$data);
        }
    } elseif ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            updateAssignment($db,$data);
        }
    } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests
        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
            deleteAssignment($db,$_GET['id'] ?? null);
        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
            deleteComment($db,$_GET['id'] ?? null);
        }
    }
} catch (Exception $e) {
    sendResponse(["error"=>$e->getMessage()],500);
}

/* ============================================================================
   HELPERS
============================================================================ */

function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);

    // TODO: Ensure data is an array
    // TODO: Echo JSON encoded data
    echo json_encode($data);

    // TODO: Exit to prevent further execution
    exit;
}

function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
    // TODO: Remove HTML and PHP tags
    // TODO: Convert special characters to HTML entities
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    return DateTime::createFromFormat('Y-m-d',$date) !== false;
}
?>
