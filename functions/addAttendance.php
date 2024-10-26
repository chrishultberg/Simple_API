<?php

// functions/addAttendance.php

function addAttendance($pdo, $student_id, $course_id, $term_id = null, $date = null, $time = null) {
    // Set the timezone at the start
    date_default_timezone_set('US/Central');

    // Get the current date and time
    $currentDateTime = new DateTime('now');
    $currentDate = $currentDateTime->format('Y-m-d'); // YYYY-MM-DD format

    if ($date) {
        // Parse the provided date
        $providedDateTime = DateTime::createFromFormat('Y-m-d', $date);
        if ($providedDateTime === false) {
            return json_encode(['status' => 'error', 'message' => 'Invalid date format. Use YYYY-MM-DD.']);
        }

        // Set the provided date with the current time if no time is provided
        if ($time === null) {
            $providedDateTime->setTime(
                $currentDateTime->format('H'),
                $currentDateTime->format('i'),
                $currentDateTime->format('s')
            );
        }
        $currentDateTime = $providedDateTime;
        $currentDate = $currentDateTime->format('Y-m-d'); // YYYY-MM-DD format
    }

    // Adjust date if time is close to midnight
    if ($currentDateTime->format('H') < 1 && $currentDateTime->format('i') < 20) { // Adjusting if very close to midnight
        $currentDateTime->modify('-1 day');
        $currentDate = $currentDateTime->format('Y-m-d'); // Adjusted date
    }

    // Format the date and time
    $dateFormatted = $currentDateTime->format('m-d-Y'); // MM-DD-YYYY
    $timeFormatted = $currentDateTime->format('h:iA'); // 12-hour format with AM/PM

    try {
        $errors = [];

        // Check if the student exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        if (!$stmt->fetchColumn()) {
            $errors[] = "Student ID: $student_id does not exist";
        }

        // Determine term ID if not provided
        if (empty($term_id)) {
            $currentTerm = getCurrentTerm($pdo, true, false);
            if (is_array($currentTerm) && isset($currentTerm['term'])) {
                $term_id = $currentTerm['term']; // This should be the short_name
            } elseif (isset($currentTerm)) {
                $term_id = $currentTerm;
            } else {
                $errors[] = "No current term found.";
            }
        }

        // Fetch the term's ID from the database using the short_name
        if (!empty($term_id)) {
            $stmt = $pdo->prepare("SELECT id, short_name FROM terms WHERE short_name = :term_name LIMIT 1");
            $stmt->execute(['term_name' => $term_id]);
            $term = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($term) {
                $term_id = $term['id'];
                $termShortName = $term['short_name'];
            } else {
                $errors[] = "Invalid term ID";
            }
        }

        // Validate the course ID
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE courseID = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$course) {
            $errors[] = "Course ID: $course_id does not exist";
        } else {
            $course_id_int = $course['id'];
        }

        // Check if attendance already exists for the student on the same date and course
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ? AND date = ? AND course_id = ?");
        $stmt->execute([$student_id, $currentDate, $course_id_int]);
        if ($stmt->fetchColumn()) {
            $errors[] = "The student's attendance record has already been recorded for the date specified";
        }

        // Return errors if any
        if (!empty($errors)) {
            return json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
        }

        // Insert the attendance record
        $stmt = $pdo->prepare("INSERT INTO attendance (student_id, term_id, date, course_id, time) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $term_id, $currentDate, $course_id_int, $currentDateTime->format('H:i:s')]);

        // Return success response with formatted date and time, and short_name instead of term ID
        return json_encode([
            'status' => 'success',
            'message' => 'Student attendance recorded successfully.',
            'student_id' => $student_id,
            'term_id' => $termShortName,
            'course_id' => $course_id,
            'date' => $dateFormatted,
            'time' => $timeFormatted,
        ]);
    } catch (PDOException $e) {
        return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

?>
