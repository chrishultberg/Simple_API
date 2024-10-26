<?php

function handleErrors($errors) {
    if (!empty($errors)) {
        return json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    }
    return null;
}

/**
 * Get the current directory of the script.
 *
 * @return string The absolute path of the current directory.
 */
function getCurrentDirectory() {
    // Get the protocol (http or https)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    
    // Get the host (e.g., www.example.com)
    $host = $_SERVER['HTTP_HOST'];
    
    // Get the current directory path
    $scriptName = $_SERVER['SCRIPT_NAME']; // e.g., /attendance/api.php
    $relativePath = dirname($scriptName); // e.g., /attendance

    // Construct the absolute URL
    $url = $protocol . '://' . $host . $relativePath;
    
    return $url;
}

// Function to get a single parameter based on request method (Case-Insensitive)
function getParam($paramName, $requestMethod) {
    $paramNameLower = strtolower($paramName);

    // Adjust to use $_REQUEST to check both $_GET and $_POST
    $params = array_change_key_case($_REQUEST, CASE_LOWER);

    // Return null if the parameter is not set or is an empty string
    return isset($params[$paramNameLower]) && $params[$paramNameLower] !== '' ? $params[$paramNameLower] : null;
}

// Function to get required and optional parameters based on request method
function getParams($requiredParams, $optionalParams, $requestMethod) {
    $params = [];
    $missingParams = [];

    foreach ($requiredParams as $paramName) {
        $paramValue = getParam($paramName, $requestMethod);
        if ($paramValue === null) {
            $missingParams[] = $paramName;
        }
        $params[$paramName] = $paramValue;
    }

    foreach ($optionalParams as $paramName) {
        $params[$paramName] = getParam($paramName, $requestMethod);
    }

    return ['params' => $params, 'missing' => $missingParams];
}

function assignLeader($pdo, $term_id) {
    try {
        // Step 1: Get all leaders for the provided term_id
        $stmt = $pdo->prepare("
            SELECT l.id AS leader_id 
            FROM leaders l 
            WHERE l.term_id = :term_id
        ");
        $stmt->execute(['term_id' => $term_id]);
        $leaders = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        if (empty($leaders)) {
            // No leaders available for the term
            echo json_encode(['status' => 'error', 'message' => 'No leaders found for term_id: ' . $term_id]);
            return null;
        }

        // Step 2: Get the count of students assigned to each leader
        $stmt = $pdo->prepare("
            SELECT leader_id, COUNT(id) AS student_count
            FROM students
            WHERE term_id = :term_id AND leader_id IS NOT NULL
            GROUP BY leader_id
        ");
        $stmt->execute(['term_id' => $term_id]);
        $studentCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Initialize an array to keep track of student counts per leader
        $leaderStudentCount = array_fill_keys($leaders, 0);

        // Populate the student counts for each leader
        foreach ($studentCounts as $count) {
            // Ensure only valid leader_id keys are used
            if (!empty($count['leader_id'])) {
                $leaderStudentCount[$count['leader_id']] = $count['student_count'];
            }
        }

        // Debugging output for student counts
        //echo json_encode(['status' => 'debug', 'message' => 'Leader Student Counts: ', 'data' => $leaderStudentCount]);

        // Step 3: Find the leader with the least number of students, using lowest leader_id as a tiebreaker
        $minCount = min($leaderStudentCount);

        $bestLeaders = array_filter($leaderStudentCount, function($count) use ($minCount) {
            return $count == $minCount;
        });

        if (empty($bestLeaders)) {
            echo json_encode(['status' => 'error', 'message' => 'No valid leader found after filtering.']);
            return null;
        }

        $bestLeaderId = min(array_keys($bestLeaders));

        // Debugging output for the selected leader
        //echo json_encode(['status' => 'debug', 'message' => 'Best Leader ID Selected: ' . $bestLeaderId]);

        return $bestLeaderId;

    } catch (PDOException $e) {
        // Handle error appropriately
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        return null;
    }
}

?>
