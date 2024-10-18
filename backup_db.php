<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Backup & Restore</title>
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
        }
        .endpoint form {
            margin-bottom: 20px;
        }
        .endpoint label {
            display: block;
            margin-bottom: 5px;
        }
        .endpoint input, .endpoint select, .endpoint button {
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
        .get-links a {
            display: inline-block;
            margin-right: 10px;
            color: #007BFF;
            text-decoration: none;
        }
    </style>
    <script>
        function generateGetLinks(action, params, types) {
            let baseUrl = '/attendance?action=' + action;
            let url = baseUrl;
            for (let key in params) {
                if (params[key]) {
                    url += '&' + key + '=' + encodeURIComponent(params[key]);
                }
            }
            let jsonUrl = url;
            let htmlUrl = url + '&type=html';
            return '<a href="' + jsonUrl + '" target="_blank">JSON Response</a> | <a href="' + htmlUrl + '" target="_blank">HTML Response</a>';
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>DB Backup & Restore</h1>
        <div class="endpoint">
            <h2>Database Management Endpoints</h2>

            <h3>Backup Database</h3>
            <form onsubmit="event.preventDefault(); document.getElementById('backupDatabase').innerHTML = generateGetLinks('backupDatabase', {}, ['json', 'html']);">
                <button type="submit" class="button">Backup Database</button>
            </form>
            <div id="backupDatabase" class="get-links"></div>

            <h3>Reset Database</h3>
            <form onsubmit="event.preventDefault(); document.getElementById('resetDatabase').innerHTML = generateGetLinks('resetDatabase', {}, ['json', 'html']);">
                <button type="submit" class="button">Reset Database</button>
            </form>
            <div id="resetDatabase" class="get-links"></div>

            <h3>Populate Database with Dummy Data</h3>
            <form onsubmit="event.preventDefault(); document.getElementById('populateDummyData').innerHTML = generateGetLinks('populateDummyData', {}, ['json', 'html']);">
                <button type="submit" class="button">Populate Dummy Data</button>
            </form>
            <div id="populateDummyData" class="get-links"></div>
        </div>
    </div>
</body>
</html>
