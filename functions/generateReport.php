<?php

function generateReport($pdo, $term_id, $student_id, $date, $absent, $format) {
    // Ensure absent is a boolean; default to false if not explicitly true
    $absent = isset($absent) ? filter_var($absent, FILTER_VALIDATE_BOOLEAN) : false;

    // Fetch courses for the specified term that end with "MLC-0000-1"
    $query = "SELECT c.students
              FROM courses c
              JOIN terms t ON c.term = t.id
              WHERE c.term = ? AND c.courseID LIKE '%MLC-0000-1'";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute([$term_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($courses)) {
        return json_encode(['status' => 'error', 'message' => 'No courses found for the specified term.']);
    }

    // Collect all students from the selected courses
    $enrolledStudents = [];
    foreach ($courses as $course) {
        $students = json_decode($course['students'], true); // Decode the JSON student list
        if ($students) {
            $enrolledStudents = array_merge($enrolledStudents, $students);
        }
    }

    // If no students are found, return an error
    if (empty($enrolledStudents)) {
        return json_encode(['status' => 'error', 'message' => 'No students found for the selected courses.']);
    }

    // Remove duplicate student IDs
    $enrolledStudents = array_unique($enrolledStudents);

    // Fetch attendance records for the specified date and term
    $attendanceQuery = "SELECT a.student_id
                        FROM attendance a
                        WHERE a.date = ? AND a.term_id = ?";
                        
    $attendanceStmt = $pdo->prepare($attendanceQuery);
    $attendanceStmt->execute([$date, $term_id]);
    $attendanceRecords = $attendanceStmt->fetchAll(PDO::FETCH_COLUMN);

    // Compare enrolled students with attendance records
    if ($absent) {
        // Return students who are enrolled but do not have an attendance record for the specified date
        $absentStudents = array_diff($enrolledStudents, $attendanceRecords);
        $attendances = array_values($absentStudents);
    } else {
        // Return students who are enrolled and have an attendance record for the specified date
        $presentStudents = array_intersect($enrolledStudents, $attendanceRecords);
        $attendances = array_values($presentStudents);
    }

    try {
        // Fetch term short_name if a term_id is provided
        $termShortName = '';
        if ($term_id) {
            $termQuery = "SELECT short_name FROM terms WHERE id = ?";
            $termStmt = $pdo->prepare($termQuery);
            $termStmt->execute([$term_id]);
            $term = $termStmt->fetch(PDO::FETCH_ASSOC);
            $termShortName = $term['short_name'] ?? '';
        }

        // Format the report date
        $formattedDate = ($date) ? (new DateTime($date))->format('m-d-Y') : 'Multiple Dates';

        // Return the report in the requested format (default is JSON)
        if ($format == 'html') {
            $htmlReport = generateHtmlReport($pdo, $attendances, $formattedDate, $termShortName, $absent);
            return $htmlReport; // Output HTML directly
        } else if ($format == 'email') {
            require_once 'sendEmail.php';
            return sendEmailReport($pdo, generateHtmlReport($pdo, $attendances, $formattedDate, $termShortName, $absent), $absent);
        } else {
            return ['status' => 'success', 'data' => $attendances];
        }

    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function generateHtmlReport($pdo, $attendances, $date, $termShortName, $absent) {
    $html = '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . ($absent ? 'Absent' : 'Attendance') . ' Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f0f0f0;
                text-align: center;
                padding: 20px;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                padding: 20px;
            }
            h1 {
                color: #333;
                margin-bottom: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }
            th {
                background-color: #f0f0f0;
            }
            td {
                background-color: #fff;
            }
            .report-info {
                text-align: center;
                margin-bottom: 20px;
                font-size: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>' . ($absent ? 'Absent' : 'Attendance') . ' Report</h1>';
    
    if ($termShortName) {
        $html .= '<h2>Term: ' . htmlspecialchars($termShortName) . '</h2>';
    }

    $html .= '<div class="report-info">
                <p>' . ($absent ? 'Absent Students' : 'Attendance') . ' for ' . htmlspecialchars($date) . '</p>
              </div>';

    $html .= '<table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Group Leader</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($attendances as $student_id) {
        // Fetch student details and their leader information for each student_id
        $studentQuery = "SELECT s.first_name, s.last_name, 
                                COALESCE(l.first_name, 'No Leader') AS leader_first_name, 
                                COALESCE(l.last_name, '') AS leader_last_name 
                         FROM students s
                         LEFT JOIN leaders l ON s.leader_id = l.id
                         WHERE s.student_id = ?";
        $studentStmt = $pdo->prepare($studentQuery);
        $studentStmt->execute([$student_id]);
        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

        $html .= '<tr>
            <td>' . htmlspecialchars($student_id) . '</td>
            <td>' . htmlspecialchars($student['first_name']) . '</td>
            <td>' . htmlspecialchars($student['last_name']) . '</td>
            <td>' . htmlspecialchars($student['leader_first_name'] . ' ' . $student['leader_last_name']) . '</td>
        </tr>';
    }

    $html .= '</tbody></table></div></body></html>';

    return $html;
}

// Additional functions like sendEmailReport() can be implemented here as needed.

?>
