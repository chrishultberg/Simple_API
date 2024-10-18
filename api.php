<?php
// Set headers to prevent caching
//header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include config file which holds our TO, CC, BCC email addresses
require_once 'config.php';

// Include our Database connection information
require_once 'db.php';

// Include our functions that will get our Parameters
require_once 'functions/functions.php';

// Determine the request method
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Null Coalescing Operator (??): This operator returns the value on its left if it's not null; 
// otherwise, it returns the value on its right. 

// By using '' as the default value, you ensure that strtolower always receives a string.
$action = strtolower(getParam('action', $requestMethod) ?? '');

// Handle API actions
switch ($action) {

case 'report':

        // getParams will use the first set of brackets as REQUIRED and the second set of brackets are optional parameters
        $result = getParams([], ['term_id', 'student_id', 'date', 'absent', 'format'], $requestMethod);
        if (empty($result['missing'])) {
            include 'functions/generateReport.php';

            // Set default format to JSON if not specified
            $format = $result['params']['format'] ?? 'json';

            $response = generateReport(
                $pdo,
                $result['params']['term_id'] ?? null,
                $result['params']['student_id'] ?? null,
                $result['params']['date'] ?? null,
                $result['params']['absent'] ?? null,
                $format
            );

            if ($format == 'html' && is_string($response)) {
                // Output the HTML directly
                echo $response;
            } else {
                // Output JSON response
                echo json_encode($response);
            }
        } else {
            $response = 'Missing parameters: ' . implode(', ', $result['missing']);
            echo json_encode(['status' => 'error', 'message' => $response]);
        }
        break;


    default:
        http_response_code(404);
        echo json_encode(['error' => 'Invalid action!']);
        break;
}

?>
