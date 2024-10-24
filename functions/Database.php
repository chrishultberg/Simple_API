<?php

// Include our Database connection information
require_once 'db.php';

function backupDatabase($pdo, $format = 'json') {
    global $user, $pass, $host, $db;

    try {
        $backupFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $command = "mysqldump --user=" . $user . " --password=" . $pass . " --host=" . $host . " " . $db . " > " . $backupFile;

        system($command, $output);

        if ($output === 0) {
            if ($format === 'html') {
                return '
                <html>
                <head>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f9f9f9;
                            margin: 0;
                            padding: 20px;
                        }
                        .container {
                            max-width: 800px;
                            margin: 0 auto;
                            background-color: #fff;
                            border-radius: 8px;
                            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                            padding: 20px;
                        }
                        h1, h2, h3 {
                            color: #333;
                        }
                        h1 {
                            text-align: center;
                        }
                        .endpoint {
                            margin-bottom: 30px;
                        }
                        .endpoint h3 {
                            margin-bottom: 5px;
                            text-align: center;
                            font-size: 25px;
                            padding: 10px;
                        }
                        .endpoint form {
                            margin-bottom: 20px;
                        }
                        .endpoint label {
                            display: block;
                            margin-bottom: 5px;
                        }
                        .endpoint input {
                            margin-bottom: 10px;
                            padding: 10px;
                            width: 97%;
                            border: 1px solid #ddd;
                            border-radius: 5px;
                        }
                        .endpoint select, .endpoint button {
                            margin-bottom: 10px;
                            padding: 10px;
                            width: 100%;
                            border: 1px solid #ddd;
                            border-radius: 5px;
                        }
                        .required {
                            color: red;
                        }
                        .button {
                            background-color: #007BFF;
                            color: white;
                            cursor: pointer;
                        }
                        .button:hover {
                            background-color: #0056b3;
                        }
                        .get-links {
                            text-align: center;
                            padding: 10px;
                        }
                        .get-links a {
                            display: inline-block;
                            margin-right: 10px;
                            color: #007BFF;
                            text-decoration: none;
                        }
                        .hidden {
                            display: none;
                        }
                        #choices {
                            text-align: center;
                            padding: 10px;
                        }
                        #removeByName, #removeById {
                            width: auto;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h1>Database Backup</h1>
                        <p>Database backup created successfully. <a href="' . $backupFile . '">Download backup</a></p>
                    </div>
                </body>
                </html>';
            } else {
                return json_encode(['status' => 'success', 'message' => 'Database backup created successfully.', 'file' => $backupFile]);
            }
        } else {
            if ($format === 'html') {
                return '
                <html>
                <head>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f9f9f9;
                            margin: 0;
                            padding: 20px;
                        }
                        .container {
                            max-width: 800px;
                            margin: 0 auto;
                            background-color: #fff;
                            border-radius: 8px;
                            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                            padding: 20px;
                        }
                        h1, h2, h3 {
                            color: #333;
                        }
                        h1 {
                            text-align: center;
                        }
                        .endpoint {
                            margin-bottom: 30px;
                        }
                        .endpoint h3 {
                            margin-bottom: 5px;
                            text-align: center;
                            font-size: 25px;
                            padding: 10px;
                        }
                        .endpoint form {
                            margin-bottom: 20px;
                        }
                        .endpoint label {
                            display: block;
                            margin-bottom: 5px;
                        }
                        .endpoint input {
                            margin-bottom: 10px;
                            padding: 10px;
                            width: 97%;
                            border: 1px solid #ddd;
                            border-radius: 5px;
                        }
                        .endpoint select, .endpoint button {
                            margin-bottom: 10px;
                            padding: 10px;
                            width: 100%;
                            border: 1px solid #ddd;
                            border-radius: 5px;
                        }
                        .required {
                            color: red;
                        }
                        .button {
                            background-color: #007BFF;
                            color: white;
                            cursor: pointer;
                        }
                        .button:hover {
                            background-color: #0056b3;
                        }
                        .get-links {
                            text-align: center;
                            padding: 10px;
                        }
                        .get-links a {
                            display: inline-block;
                            margin-right: 10px;
                            color: #007BFF;
                            text-decoration: none;
                        }
                        .hidden {
                            display: none;
                        }
                        #choices {
                            text-align: center;
                            padding: 10px;
                        }
                        #removeByName, #removeById {
                            width: auto;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h1>Database Backup</h1>
                        <p>Error creating database backup.</p>
                    </div>
                </body>
                </html>';
            } else {
                return json_encode(['status' => 'error', 'message' => 'Error creating database backup.']);
            }
        }
    } catch (Exception $e) {
        if ($format === 'html') {
            return '
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f9f9f9;
                        margin: 0;
                        padding: 20px;
                    }
                    .container {
                        max-width: 800px;
                        margin: 0 auto;
                        background-color: #fff;
                        border-radius: 8px;
                        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                        padding: 20px;
                    }
                    h1, h2, h3 {
                        color: #333;
                    }
                    h1 {
                        text-align: center;
                    }
                    .endpoint {
                        margin-bottom: 30px;
                    }
                    .endpoint h3 {
                        margin-bottom: 5px;
                        text-align: center;
                        font-size: 25px;
                        padding: 10px;
                    }
                    .endpoint form {
                        margin-bottom: 20px;
                    }
                    .endpoint label {
                        display: block;
                        margin-bottom: 5px;
                    }
                    .endpoint input {
                        margin-bottom: 10px;
                        padding: 10px;
                        width: 97%;
                        border: 1px solid #ddd;
                        border-radius: 5px;
                    }
                    .endpoint select, .endpoint button {
                        margin-bottom: 10px;
                        padding: 10px;
                        width: 100%;
                        border: 1px solid #ddd;
                        border-radius: 5px;
                    }
                    .required {
                        color: red;
                    }
                    .button {
                        background-color: #007BFF;
                        color: white;
                        cursor: pointer;
                    }
                    .button:hover {
                        background-color: #0056b3;
                    }
                    .get-links {
                        text-align: center;
                        padding: 10px;
                    }
                    .get-links a {
                        display: inline-block;
                        margin-right: 10px;
                        color: #007BFF;
                        text-decoration: none;
                    }
                    .hidden {
                        display: none;
                    }
                    #choices {
                        text-align: center;
                        padding: 10px;
                    }
                    #removeByName, #removeById {
                        width: auto;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>Database Backup</h1>
                    <p>Error: ' . $e->getMessage() . '</p>
                </div>
            </body>
            </html>';
        } else {
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
?>
