<?php

function getStudentNameById($pdo, $student_id = null) {

    // Make sure that an ID is provided
    if (!$student_id) {
        return json_encode(['error' => 'Missing parameter: ID']);
    }

    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $fullName = $student['first_name'] . ' ' . $student['last_name'];
            return json_encode(['StudentName' => $fullName]);
        } else {
            return json_encode(['error' => 'Student not found']);
        }
    } catch (PDOException $e) {
        return json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

?>
