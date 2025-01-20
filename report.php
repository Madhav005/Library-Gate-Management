<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Reports</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .button-container {
            margin-bottom: 20px;
        }
        button {
            padding: 10px 20px;
            font-size: 14px;
            margin-right: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .btn-report {
            background-color: #007bff;
        }
        .btn-report:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Library Report Dashboard</h1>
    <div class="button-container">
        <form action="all_students_report.php" method="GET" style="display: inline;">
            <button type="submit" class="btn-report">All Students Entry Report</button>
        </form>
        <form action="department_report.php" method="GET" style="display: inline;">
            <button type="submit" class="btn-report">Department-wise Entry Report</button>
        </form>
        <form action="department_count.php" method="GET" style="display: inline;">
            <button type="submit" class="btn-report">Department-wise Students Count</button>
        </form>
        <!-- New Button for Not-checked out Users -->
        <form action="not_checked_out.php" method="GET" style="display: inline;">
            <button type="submit" class="btn-report">Not-checked out Users</button>
        </form>
    </div>
</body>
</html>
