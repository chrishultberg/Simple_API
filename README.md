# Overview of `api.php`

The `api.php` file handles API requests and determines which action to perform. The focus here is on generating reports using the `report` action. Below is a breakdown of how the `api.php` script works.

## 1. Headers

- **Cache Control**: The script sets headers to prevent caching.
- **Error Reporting**: Error reporting is enabled by using `ini_set` to display errors during development.

```php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## 2. Config and Database Includes
- The configuration and database connection settings are included via `config.php` and `db.php`.

```php
require_once 'config.php';
require_once 'db.php';
```

## 3. Parameter Retrieval
- The parameters are retrieved using a helper function `getParam()` based on the request method (`GET` or `POST`). The action parameter is converted to lowercase for consistency.

```php
$action = strtolower(getParam('action', $requestMethod) ?? '');
```

## 4. API Action Logic
- The script uses a `switch` statement to check the action requested. In this case, it listens for the `report` action.
- It retrieves the necessary parameters: `term_id`, `student_id`, `date`, `absent`, and `format` using the `getParams()` function.
- If all required parameters are available, the script calls `generateReport.php` to process the request.

```php
$result = getParams([], ['term_id', 'student_id', 'date', 'absent', 'format'], $requestMethod);
if (empty($result['missing'])) {
    include 'functions/generateReport.php';
```

## 5. Default JSON Response
- If no format is specified, the API defaults to returning a JSON response.

```php
$format = $result['params']['format'] ?? 'json';
```

## 6. HTML or JSON Output
- If the requested format is `html`, the generated report is output directly as HTML.
- Otherwise, the report is returned as a JSON response.

```php
if ($format == 'html' && is_string($response)) {
    echo $response;
} else {
    echo json_encode($response);
}
```

## 7. Error Handling
- If parameters are missing, the script returns a JSON error response listing the missing parameters.

```php
} else {
    $response = 'Missing parameters: ' . implode(', ', $result['missing']);
    echo json_encode(['status' => 'error', 'message' => $response]);
}
```

## 8. Invalid Action
- If an unrecognized action is sent, a 404 response is returned.

```php
default:
    http_response_code(404);
    echo json_encode(['error' => 'Invalid action!']);
    break;
```





# Overview of `generateReport.php`

Here's an overview of its functionality:

### Key Features of `generateReport.php`

1.  __Report Generation Logic__:
    
    -   The function `generateReport` connects to the database using `$pdo` to retrieve attendance data based on specific criteria:
        -   `term_id`: The term to filter by.
        -   `student_id`: The specific student to report on.
        -   `date`: The date for the attendance report.
        -   `absent`: If set to `true`, it will retrieve students who were absent; otherwise, it will get students who were present.
    -   The report can be outputted in either HTML or email format.

2.  __SQL Query Structure__:
    
    -   The SQL query is dynamically adjusted based on whether the report is for present or absent students. The query filters students based on their attendance records for the provided `term_id` and `date`.
    -   The query joins the `students`, `terms`, and `leaders` tables to fetch student names, leader names, and term information.

3.  __HTML Report__:
    
    -   The `generateHtmlReport` function builds a clean HTML table to display attendance data, including student ID, name, and group leader.
    -   It uses inline CSS for styling, ensuring a visually clean and formatted report.

4.  __Error Handling__:
    
    -   If there is an error during the database operation, it is caught by a `try-catch` block, and an error message is returned.

### Suggested Enhancements

-   __Pagination__: If you expect large datasets, you could add pagination to break the results into pages.
-   __CSV Export__: Consider adding an option to export the report as a CSV file, making it easier to share or analyze in spreadsheet software.
-   __Advanced Filters__: You could add filters like `course_id` or `leader_id` to narrow down the report further.





Here’s a step-by-step breakdown of how we build our query using logic to handle the attendance and absent students based on the enrollment in courses ending with "MLC-0000-1":

### Steps to Implement

1.  __Identify the Correct Term__: We’ll use the provided `term_id` to fetch the correct term from the `terms` table.
    
2.  __Filter Courses Ending with "MLC-0000-1"__: We’ll check the `courses` table for courses within the specified `term_id` that end with "MLC-0000-1".
    
3.  __Extract the List of Enrolled Students__: Each course contains a JSON array of students in the `students` column. We'll extract this array of students for courses that meet the filtering criteria.
    
4.  __Compare Enrollment with Attendance Records__: Using the provided `date`, we will compare the list of enrolled students with the records in the `attendance` table.
    
    -   If `absent=true`, we’ll return students who __do not__ have an attendance record for the specified date.
    -   If `absent=false`, we’ll return students who __do__ have an attendance record for the specified date.

### Code to Handle the Logic

```php
<?php

function generateReport($pdo, $term_id, $date, $absent = false, $format = 'json') {
    // Fetch courses for the specified term that end with "MLC-0000-1"
    $query = "SELECT c.students
              FROM courses c
              JOIN terms t ON c.term = t.id
              WHERE c.term = ? AND c.shortName LIKE '%MLC-0000-1'";
              
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
        $result = array_values($absentStudents);
    } else {
        // Return students who are enrolled and have an attendance record for the specified date
        $presentStudents = array_intersect($enrolledStudents, $attendanceRecords);
        $result = array_values($presentStudents);
    }

    // Return the report in the requested format (default is JSON)
    if ($format == 'html') {
        return generateHtmlReport($result, $date, $term_id, $absent);
    } else if ($format == 'email') {
        return sendEmailReport($pdo, generateHtmlReport($result, $date, $term_id, $absent));
    } else {
        return json_encode(['status' => 'success', 'data' => $result]);
    }
}

?>
```

### Explanation - How it Works:

1.  __Fetch Courses with "MLC-0000-1"__: The query filters courses based on the `term_id` and course names ending with "MLC-0000-1" using the `LIKE` clause.
    
2.  __Decode the Student JSON List__: For each course that matches the query, we decode the `students` column, which contains a JSON list of enrolled students.
    
3.  __Compare Enrollment and Attendance__: We compare the list of students enrolled in the courses with the records in the `attendance` table for the specified date:
    
    -   For `absent=true`, we return students who are enrolled but __do not__ have a matching attendance record.
    -   For `absent=false`, we return students who are enrolled and __do__ have a matching attendance record.
4.  __Output Formats__: The report is returned in either JSON (default), HTML, or email format depending on the `format` parameter.
    

### Test Cases

-   __Absent Students Report__:
    
    ```php    
    https://localhost/attendance/api.php?action=report&date=2024-08-21&absent=true&format=json&term_id=2
    ```
    
    This will return all students who do not have an attendance record on `2024-08-21` but are enrolled in courses ending in "MLC-0000-1" for the specified `term_id`.
    
-   __Present Students Report (Attendance Report)__:
    
    ```php
    https://localhost/attendance/api.php?action=report&date=2024-08-21&absent=false&format=json&term_id=2
    ```
    
    This will return all students who have an attendance record on `2024-08-21` and are enrolled in courses ending in "MLC-0000-1" for the specified `term_id`.
    

### Next Steps

1.  __Test with Sample Data__: Verify that the API returns the correct list of students (either absent or present) based on the attendance records and course enrollments.
    
2.  __Troubleshoot Edge Cases__: If some students are missing, check for potential issues such as:
    
    -   Incorrect student data in the JSON-encoded `students` column.
    -   Attendance records that are not properly linked to the correct `term_id` or date.

