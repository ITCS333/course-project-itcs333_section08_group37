<?php
session_start();
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
// TODO: Set CORS headers to allow cross-origin requests
// TODO: Handle preflight OPTIONS request

// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// TODO: Include the database connection class
// TODO: Create database connection
// TODO: Set PDO to throw exceptions on errors

// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
// TODO: Get the request body for POST and PUT requests
// TODO: Parse query parameters

// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

function getAllAssignments($db) {
    // TODO: Start building the SQL query
    // TODO: Check if 'search' query parameter exists in $_GET
    // TODO: Check if 'sort' and 'order' query parameters exist
    // TODO: Prepare the SQL statement using $db->prepare()
    // TODO: Bind parameters if search is used
    // TODO: Execute the prepared statement
    // TODO: Fetch all results as associative array
    // TODO: For each assignment, decode the 'files' field from JSON to array
    // TODO: Return JSON response
}

function getAssignmentById($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    // TODO: Prepare SQL query to select assignment by id
    // TODO: Bind the :id parameter
    // TODO: Execute the statement
    // TODO: Fetch the result as associative array
    // TODO: Check if assignment was found
    // TODO: Decode the 'files' field from JSON to array
    // TODO: Return success response with assignment data
}

function createAssignment($db, $data) {
    // TODO: Validate required fields
    // TODO: Sanitize input data
    // TODO: Validate due_date format
    // TODO: Generate a unique assignment ID
    // TODO: Handle the 'files' field
    // TODO: Prepare INSERT query
    // TODO: Bind all parameters
    // TODO: Execute the statement
    // TODO: Check if insert was successful
    // TODO: If insert failed, return 500 error
}

function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
    // TODO: Store assignment ID in variable
    // TODO: Check if assignment exists
    // TODO: Build UPDATE query dynamically based on provided fields
    // TODO: Check which fields are provided and add to SET clause
    // TODO: If no fields to update (besides updated_at), return 400 error
    // TODO: Complete the UPDATE query
    // TODO: Prepare the statement
    // TODO: Bind all parameters dynamically
    // TODO: Execute the statement
    // TODO: Check if update was successful
    // TODO: If no rows affected, return appropriate message
}

function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    // TODO: Check if assignment exists
    // TODO: Delete associated comments first (due to foreign key constraint)
    // TODO: Prepare DELETE query for assignment
    // TODO: Bind the :id parameter
    // TODO: Execute the statement
    // TODO: Check if delete was successful
    // TODO: If delete failed, return 500 error
}

// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    // TODO: Prepare SQL query to select all comments for the assignment
    // TODO: Bind the :assignment_id parameter
    // TODO: Execute the statement
    // TODO: Fetch all results as associative array
    // TODO: Return success response with comments data
}

function createComment($db, $data) {
    // TODO: Validate required fields
    // TODO: Sanitize input data
    // TODO: Validate that text is not empty after trimming
    // TODO: Verify that the assignment exists
    // TODO: Prepare INSERT query for comment
    // TODO: Bind all parameters
    // TODO: Execute the statement
    // TODO: Get the ID of the inserted comment
    // TODO: Return success response with created comment data
}

function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
    // TODO: Check if comment exists
    // TODO: Prepare DELETE query
    // TODO: Bind the :id parameter
    // TODO: Execute the statement
    // TODO: Check if delete was successful
    // TODO: If delete failed, return 500 error
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
    // TODO: Route based on HTTP method and resource type
    if ($method === 'GET') {
        // TODO: Handle GET requests
        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
        } else {
            // TODO: Invalid resource, return 400 error
        }
    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)
        if ($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
        } else {
            // TODO: Invalid resource, return 400 error
        }
    } elseif ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
        } else {
            // TODO: PUT not supported for other resources
        }
    } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests
        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
        } else {
            // TODO: Invalid resource, return 400 error
        }
    } else {
        // TODO: Method not supported
    }
} catch (PDOException $e) {
    // TODO: Handle database errors
} catch (Exception $e) {
    // TODO: Handle general errors
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // TODO: Ensure data is an array
    // TODO: Echo JSON encoded data
    // TODO: Exit to prevent further execution
}

function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
    // TODO: Remove HTML and PHP tags
    // TODO: Convert special characters to HTML entities
    // TODO: Return the sanitized data
}

function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    // TODO: Return true if valid, false otherwise
}

function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
    // TODO: Return the result
}
?>
