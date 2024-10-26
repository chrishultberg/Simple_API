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

    case 'backupdatabase':
    
        $result = getParams([], ['format'], $requestMethod);
        if (empty($result['missing'])) {
            include 'functions/Database.php';
            $format = !empty($result['params']['format']) ? $result['params']['format'] : 'json';
            $response = backupDatabase($pdo, $format);
        } else {
            $response = 'Missing parameters: ' . implode(', ', $result['missing']);
        }
        echo $response;
        break;

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

    case 'addstudent':
        $result = getParams(['student_id', 'first_name', 'last_name'], ['email', 'phone', 'leader_id', 'term_id'], $requestMethod);
        if (empty($result['missing'])) {
            include 'functions/addEntity.php';

            $email = !empty($result['params']['email']) ? $result['params']['email'] : null;
            $phone = !empty($result['params']['phone']) ? $result['params']['phone'] : null;
            $leader_id = !empty($result['params']['leader_id']) ? $result['params']['leader_id'] : null;
            $term_id = !empty($result['params']['term_id']) ? $result['params']['term_id'] : null;

            $entityData = [
                'student_id' => $result['params']['student_id'],
                'first_name' => $result['params']['first_name'],
                'last_name' => $result['params']['last_name'],
                'email' => $email,
                'phone' => $phone,
                'leader_id' => $leader_id,
                'term_id' => $term_id
            ];

            $response = addEntity($pdo, 'student', $entityData);
        } else {
            $response = 'Missing parameters: ' . implode(', ', $result['missing']);
        }
        echo $response;
        break;
    
    case 'removestudent':
        
        $result = getParams(['id', 'name'], [], $requestMethod);
        
        include 'functions/removeStudent.php';

        if (!empty($result['params']['id'])) {
            $response = removeStudentById($pdo, $result['params']['id']);
        } elseif (!empty($result['params']['name'])) {
            $response = removeStudentByName($pdo, $result['params']['name']);
        } else {
            $response = json_encode(['error' => 'Missing parameters: id or name required']);
        }

        echo $response;
        break;

    case 'addattendance':
        // Set the timezone at the start
        date_default_timezone_set('US/Central');

        // Retrieve parameters from either POST or GET request
        $result = getParams(['student_id', 'course_id'], ['term_id', 'date', 'time'], $requestMethod);

        if (empty($result['missing'])) {
            include 'functions/addAttendance.php';

            // Determine the date and time from POST or GET, defaulting to current values if not provided
            $date_from_request = $_POST['date'] ?? $_GET['date'] ?? null;
            $time_from_request = $_POST['time'] ?? $_GET['time'] ?? null;

            // If no date is provided, use the current date, adjusting for date transitions close to midnight
            if ($date_from_request === null) {
                $date_from_request = date('m-d-Y'); // Default to current date
            }

            // Format date to ensure correct conversion in addAttendance function
            $date_from_request = DateTime::createFromFormat('m-d-Y', $date_from_request);
            if ($date_from_request === false) {
                $response = json_encode(['status' => 'error', 'message' => 'Invalid date format. Use MM-DD-YYYY.']);
                echo $response;
                exit;
            }

            // Call the addAttendance function
            $response = addAttendance(
                $pdo,
                $result['params']['student_id'],
                $result['params']['course_id'],
                $result['params']['term_id'] ?? null,
                $date_from_request->format('Y-m-d'), // Use YYYY-MM-DD format for database
                $time_from_request
            );
        } else {
            $response = json_encode(['status' => 'error', 'message' => 'Missing parameters: ' . implode(', ', $result['missing'])]);
        }
        echo $response;
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Invalid action!']);
        break;
}

?>
