<?php

function removeStudent($pdo) {
  
    $student_id = $_GET['student_id'] ?? '';

    if (!$student_id) {
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        echo json_encode(['status' => 'success', 'message' => 'Student removed successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

?>

<?php

function removeStudentById($pdo, $student_id) {

    if (!$student_id) {
        return json_encode(['error' => 'Missing required parameters!']);
    }

    include 'functions/getStudentNameById.php';

    // Check if the student already exists
    $response = getStudentNameById($pdo, $student_id);
    $responseData = json_decode($response, true);

    if (isset($responseData['error'])) {
        return json_encode(['error' => 'Student ID: ' . $student_id . ' not found!']);
    } else {
        $studentName = $responseData['StudentName'];
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        echo json_encode(['status' => 'success', 'message' => 'Student: ' . $studentName . ' removed successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function removeStudentByName($pdo, $studentName) {

    if (!$studentName) {
        return json_encode(['error' => 'Missing required parameters!']);
    }

    include 'functions/getStudentIdByName.php';

    // Check if the student already exists
    $response = getStudentIdByName($pdo, $studentName);
    $responseData = json_decode($response, true);

    if (isset($responseData['error'])) {
        return json_encode(['error' => 'Student: ' . $studentName . ' not found!']);
    } else {
        $studentId = $responseData['student_id'];
    }

    try {
        // Delete the student by their ID
        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->execute([$studentId]);

        return json_encode(['status' => 'success', 'message' => 'Student ID: ' . $studentId . ' - ' . $studentName . ' removed successfully']);
    } catch (PDOException $e) {
        return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

?>
