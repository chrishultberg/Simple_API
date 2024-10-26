<?php

// Function to get Student ID by Name
function getStudentIdByName($pdo, $studentName = null) {

    // Make sure that a studentName is provided
    if (!$studentName) {
        return json_encode(['error' => 'Missing parameter: studentName']);
    }

    try {
        // Split the studentName into first_name and last_name
        $parts = explode(' ', $studentName);
        $first_name = $parts[0];
        $last_name = isset($parts[1]) ? $parts[1] : '';

        // SQL query to retrieve student ID by name
        $sql = "SELECT student_id FROM students WHERE first_name = ? AND last_name = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$first_name, $last_name]);
        $studentId = $stmt->fetchColumn();

        // Return JSON response
        if ($studentId) {
            return json_encode(['student_id' => $studentId]);
        } else {
            return json_encode(['error' => 'Student not found']);
        }

    } catch (PDOException $e) {
        return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
