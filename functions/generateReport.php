<?php

/**
 * Generates an attendance report based on the provided parameters.
 *
 * @param PDO $pdo - The database connection.
 * @param int|null $term_id - The ID of the term to filter attendance records.
 * @param int|null $student_id - The ID of the student to filter attendance records.
 * @param string|null $date - A specific date to filter attendance records (YYYY-MM-DD).
 * @param bool|null $absent - If true, shows absent records. Defaults to false (present records).
 * @param string|null $format - The format of the report (html, email, or json). Defaults to JSON.
 *
 * @return array|string - JSON array for data or HTML string if specified.
 */

function generateReport($pdo, $term_id = null, $student_id = null, $date = null, $absent = false, $format = 'json') {
    // Validate input parameters
    $absent = filter_var($absent, FILTER_VALIDATE_BOOLEAN);

    // Check that at least one parameter is provided
    if (is_null($term_id) && is_null($student_id) && is_null($date)) {
        return [
            'status' => 'info',
            'message' => 'At least one of term_id, student_id, or date is required.',
            'data' => []
        ];
    }

    // Build dynamic SQL query based on provided parameters
    $attendanceQuery = "SELECT a.date, a.student_id
                        FROM attendance a
                        JOIN students s ON a.student_id = s.student_id
                        WHERE 1=1";  // Placeholder to allow dynamic AND clauses
    $params = [];

    // Apply filters based on provided parameters
    if ($term_id) {
        $attendanceQuery .= " AND a.term_id = ?";
        $params[] = $term_id;
    }
    if ($student_id) {
        $attendanceQuery .= " AND a.student_id = ?";
        $params[] = $student_id;
    }
    if ($date) {
        $attendanceQuery .= " AND a.date = ?";
        $params[] = $date;
    }

    // Execute query and fetch results
    $attendanceQuery .= " ORDER BY a.date, a.student_id";
    $stmt = $pdo->prepare($attendanceQuery);
    $stmt->execute($params);
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);

    //return ['status' => 'success', 'attendanceRecords' => $attendanceRecords];

    $reportData = [];

    if (empty($attendanceRecords)) {
        /*
        return [
            'status' => 'info',
            'message' => 'No attendance records found for the specified parameters.',
            'data' => [$absent, $params]
        ];
        */
        //$reportData[$attendanceDate] = [$attendanceDate => null];
    }

    // Collect all students enrolled in valid courses for the term
    $enrolledStudents = [];
    if ($term_id) {
        $enrolledStudents = getEnrolledStudents($pdo, $term_id);
    }

    //return ['status' => 'success', 'enrolledStudents' => $enrolledStudents];

    if ($student_id) {

        // Step 1: Check if the student is enrolled
        if (!in_array($student_id, $enrolledStudents)) {
            return ["status" => "error", "message" => "Student ID: {$student_id} is NOT enrolled."];
        }

        // Step 2: Iterate through attendance records for the specific student
        foreach ($attendanceRecords as $attendanceDate => $presentStudents) {
            if ($absent) {
                // If $absent is true, check if student_id is NOT in presentStudents
                if (!in_array($student_id, $presentStudents)) {
                    $reportData[$attendanceDate] = [$attendanceDate => null];  // Mark student as absent for this date
                }
            } else {
                // If $absent is false, check if student_id is in presentStudents
                if (in_array($student_id, $presentStudents)) {
                    $reportData[$attendanceDate] = [$attendanceDate => $student_id];  // Mark student as present for this date
                }
            }
        }

        //return ["status" => "success", "studentsToReport" => $reportData];

    } else {
        // Handle the case where $student_id is not provided (multiple students)
        foreach ($attendanceRecords as $attendanceDate => $presentStudents) {
            $studentsToReport = $absent 
                ? array_diff($enrolledStudents, $presentStudents)  // Absent students
                : array_intersect($enrolledStudents, $presentStudents);  // Present students

            $reportData[$attendanceDate] = $studentsToReport;
        }

        //return ["status" => "success", "studentsToReport" => $reportData];
    }

    // Determine the output format
    if ($format === 'html') {
        $termShortName = getTermShortName($pdo, $term_id);
        return generateHtmlGroupedReport($pdo, $reportData, $termShortName, $absent);
    } elseif ($format === 'email') {
        require_once 'sendEmail.php';
        $htmlReport = generateHtmlGroupedReport($pdo, $reportData, $termShortName, $absent);
        return sendEmailReport($pdo, $htmlReport, $absent);
    } else {
        return ['status' => 'success', 'data' => $reportData];
    }
}

/**
 * Support function for getting enrolled students in valid courses (ending in "MLC-0000-1") for a term.
 */

function getEnrolledStudents($pdo, $term_id) {
    $coursesQuery = "SELECT c.students 
                     FROM courses c 
                     WHERE c.term = ? 
                     AND c.shortName LIKE '%MLC-0000-1'";
    $stmt = $pdo->prepare($coursesQuery);
    $stmt->execute([$term_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $enrolledStudents = [];
    foreach ($courses as $course) {
        $students = json_decode($course['students'], true);
        if ($students) {
            $enrolledStudents = array_merge($enrolledStudents, $students);
        }
    }
    return array_unique($enrolledStudents);
}

/**
 * Support function for fetching term details.
 */

function getTermShortName($pdo, $term_id) {
    $termQuery = "SELECT short_name FROM terms WHERE id = ?";
    $stmt = $pdo->prepare($termQuery);
    $stmt->execute([$term_id]);
    $term = $stmt->fetch(PDO::FETCH_ASSOC);
    return $term['short_name'] ?? '';
}

/**
 * Generate an HTML report grouped by date.
 */

function generateHtmlGroupedReport($pdo, $reportData, $termShortName, $absent) {
    $html = '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>' . ($absent ? 'Absent Report' : 'Attendance Report') . '</title>
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
            h3 {
                padding-top: 25px;
                margin-bottom: -10px;
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
        <div class="container">';

        $html .= '<h1>' . ($absent ? 'Absent Report' : 'Attendance Report') . '</h1>';
        $html .= '<h2>Term: ' . htmlspecialchars($termShortName) . '</h2>';

        if (!empty($reportData)) {

            foreach ($reportData as $date => $students) {

                // Ensure $students is an array
                if (!is_array($students)) {
                    $students = []; // Fallback to an empty array if $students is not an array
                }

                $html .= '<div class="report-info">
                            <p>' . ($absent ? 'Absent' : 'Attendance') . ' Details for ' . htmlspecialchars(DateTime::createFromFormat('Y-m-d', $date)->format('m-d-Y')) . '</p>
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

                foreach ($students as $student_id) {
                    $student = getStudentDetails($pdo, $student_id);
                    if ($student) {
                        $html .= '<tr>';
                        $html .= '<td>' . htmlspecialchars($student['student_id'] ?? '') . '</td>';
                        $html .= '<td>' . htmlspecialchars($student['first_name'] ?? '') . '</td>';
                        $html .= '<td>' . htmlspecialchars($student['last_name'] ?? '') . '</td>';
                        $html .= '<td>' . htmlspecialchars($student['leader_first_name'] . ' ' . $student['leader_last_name']) . '</td>';
                        $html .= '</tr>';
                    } else {
                        $html .= '<tr><td style="text-align: center;" colspan="3">Student details not found</td></tr>';
                    }
                }

                $html .= '</tbody></table>';

            }

        } else {
            $html .= '<p>No students found for the selected criteria.</p>';
        }

    $html .= '</div></body></html>';
    return $html;
}

/**
 * Fetches details for a specific student.
 */

function getStudentDetails($pdo, $student_id) {

    // Fetch student details and their leader information for each student_id
    $studentQuery = "SELECT s.first_name, s.last_name, s.student_id,
                            COALESCE(l.first_name, 'No Leader') AS leader_first_name, 
                            COALESCE(l.last_name, '') AS leader_last_name 
                        FROM students s
                        LEFT JOIN leaders l ON s.leader_id = l.id
                        WHERE s.student_id = ?";
    $studentStmt = $pdo->prepare($studentQuery);
    $studentStmt->execute([$student_id]);
    return $studentStmt->fetch(PDO::FETCH_ASSOC);
}

?>
