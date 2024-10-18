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

?>
