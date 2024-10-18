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
