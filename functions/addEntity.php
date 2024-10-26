<?php

function addEntity($pdo, $entityType, $entityData) {
    $table = $entityType === 'leader' ? 'leaders' : 'students';

    $requiredFields = [
        'student' => ['student_id', 'first_name', 'last_name'],
        'leader' => ['first_name', 'last_name']
    ];

    $optionalFields = [
        'student' => ['email', 'phone', 'leader_id', 'term_id'],
        'leader' => ['email', 'phone', 'term_id']
    ];

    if (!in_array($entityType, ['student', 'leader'])) {
        return json_encode(['status' => 'error', 'message' => 'Invalid entity type']);
    }

    $errors = [];

    // Validate required fields
    foreach ($requiredFields[$entityType] as $field) {
        if (empty($entityData[$field])) {
            $errors[] = ucfirst($entityType) . " $field is required";
        }
    }

    // If term_id is not provided, get the current term_id
    if (empty($entityData['term_id'])) {
        $entityData['term_id'] = getCurrentTermId($pdo);
        if (!$entityData['term_id']) {
            $errors[] = "Unable to determine the current term (current date: " . date('Y-m-d') . ").";
        }
    }

    // Check if the term_id exists in the terms table
    if (!empty($entityData['term_id'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM terms WHERE id = :term_id");
        $stmt->execute(['term_id' => $entityData['term_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['count'] == 0) {
            $errors[] = "Term with ID " . htmlspecialchars($entityData['term_id']) . " does not exist";
        }
    }

    // Check if the leader_id exists in the leaders table when adding a student
    // If no "leader_id" is provided and "assignLeader" is true, automatically get the leader with the least students in their group
    if ($entityType === 'student' && empty($entityData['leader_id']) && $entityData['assignLeader']) {
        $entityData['leader_id'] = assignLeader($pdo, $entityData['term_id']);
        if (is_null($entityData['leader_id'])) {
            // Fetch the term details for more informative error message
            $stmt = $pdo->prepare("SELECT short_name, long_name FROM terms WHERE id = :term_id");
            $stmt->execute(['term_id' => $entityData['term_id']]);
            $term = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($term) {
                $termInfo = $term['short_name'] . ' - ' . $term['long_name'];
            } else {
                $termInfo = 'Unknown Term';
            }

            $errors[] = htmlspecialchars($entityData['student_id']) . " - No leader for term ID: " . htmlspecialchars($entityData['term_id']) . " (" . htmlspecialchars($termInfo) . ")";
        }
    }

    // Return all errors if there are any
    $errorResponse = handleErrors($errors);
    if ($errorResponse) {
        return $errorResponse;
    }

    // Extract parameters from $entityData, using null for optional fields if not provided
    $student_id = $entityData['student_id'];
    $first_name = $entityData['first_name'];
    $last_name = $entityData['last_name'];
    $email = !empty($entityData['email']) ? $entityData['email'] : null;
    $phone = !empty($entityData['phone']) ? $entityData['phone'] : null;
    $term_id = !empty($entityData['term_id']) ? $entityData['term_id'] : null;

    if ($entityType === 'student') {
        $leader_id = !empty($entityData['leader_id']) ? $entityData['leader_id'] : null;
    }

    // Check if the entity already exists by student_id
    $checkSql = "SELECT COUNT(*) as count FROM $table WHERE student_id = :student_id AND term_id = :term_id";
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute(['student_id' => $student_id, 'term_id' => $term_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row['count'] > 0) {
        return json_encode(['status' => 'error', 'message' => ucfirst($entityType) . ' with this Student ID: ' . htmlspecialchars($entityData['student_id']) . ' already exists for the selected term']);
    }

    // Check if the leader already exists for the given term
    if ($entityType === 'leader') {
        if (is_null($term_id)) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM leaders 
                WHERE first_name = :first_name AND last_name = :last_name AND term_id IS NULL
            ");
            $stmt->execute(['first_name' => $first_name, 'last_name' => $last_name]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row['count'] > 0) {
                return json_encode(['status' => 'error', 'message' => 'Leader already exists without a term']);
            }
        } else {
            $stmt = $pdo->prepare("
                SELECT l.term_id, t.short_name, t.long_name 
                FROM leaders l 
                JOIN terms t ON l.term_id = t.id 
                WHERE l.first_name = :first_name AND l.last_name = :last_name AND l.term_id = :term_id
            ");
            $stmt->execute(['first_name' => $first_name, 'last_name' => $last_name, 'term_id' => $term_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $term_info = $row['short_name'] ? $row['short_name'] : $row['long_name'];
                return json_encode(['status' => 'error', 'message' => 'Leader already exists for the selected term: ' . htmlspecialchars($term_info)]);
            }
        }
    }

    try {
        // Prepare SQL statement for insertion
        $insertSql = $entityType === 'leader' ?

            "INSERT INTO $table (student_id, first_name, last_name, email, phone, term_id) VALUES (:student_id, :first_name, :last_name, :email, :phone, :term_id)" :
            "INSERT INTO $table (student_id, first_name, last_name, email, phone, leader_id, term_id) VALUES (:student_id, :first_name, :last_name, :email, :phone, :leader_id, :term_id)";

        $stmt = $pdo->prepare($insertSql);

        // Bind parameters
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':term_id', $term_id, PDO::PARAM_INT);

        if ($entityType === 'student') {

            $stmt->bindParam(':leader_id', $leader_id, PDO::PARAM_INT);

        }

        // Execute the insertion statement

        $stmt->execute();

        // Check if the insertion was successful
        if ($stmt->rowCount() > 0) {

            return json_encode(['status' => 'success', 'message' => ucfirst($entityType) . ' ID: ' . $student_id . ' added successfully']);

        } else {

            return json_encode(['status' => 'error', 'message' => 'Failed to add ' . $entityType]);

        }

    } catch (PDOException $e) {

        return json_encode(['status' => 'error', 'message' => $e->getMessage()]);

    }

}

?>
