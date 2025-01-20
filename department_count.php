<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'library_system');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Initialize variables
$start_date = '';
$end_date = '';
$report = '';

// Generate report based on date range
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_date'], $_POST['end_date'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $sql = "
        SELECT 
            d.dept,
            COUNT(DISTINCT e.registration_number) AS total_count,
            COUNT(DISTINCT CASE 
                            WHEN e.registration_number REGEXP '^[0-9]' THEN e.registration_number 
                            ELSE NULL 
                        END) AS student_count,
            COUNT(DISTINCT CASE 
                            WHEN e.registration_number REGEXP '^[A-Za-z]' THEN e.registration_number 
                            ELSE NULL 
                        END) AS staff_count
        FROM (
            SELECT DISTINCT dept FROM student_data
            UNION
            SELECT DISTINCT dept FROM staff_data
        ) d
        LEFT JOIN entries e ON e.registration_number IN (
            SELECT registration_number FROM student_data WHERE dept = d.dept
            UNION
            SELECT staff_id FROM staff_data WHERE dept = d.dept
        )
        AND e.in_time >= ? AND e.in_time <= DATE_ADD(?, INTERVAL 1 DAY)
        GROUP BY d.dept
        ORDER BY d.dept ASC;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $report .= '<table class="report-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Total Count</th>
                                <th>Student Count</th>
                                <th>Staff Count</th>
                            </tr>
                        </thead>
                        <tbody>';
        while ($row = $result->fetch_assoc()) {
            $report .= "<tr>
                            <td>{$row['dept']}</td>
                            <td>{$row['total_count']}</td>
                            <td>{$row['student_count']}</td>
                            <td>{$row['staff_count']}</td>
                        </tr>";
        }
        $report .= '</tbody></table>';
    } else {
        $report = '<div class="alert">No records found for the selected date range.</div>';
    }

    $stmt->close();
}

// Export to CSV
if (isset($_POST['export_csv']) && !empty($start_date) && !empty($end_date)) {
    $sql = "
       SELECT 
            d.dept,
            COUNT(DISTINCT e.registration_number) AS total_count,
            COUNT(DISTINCT CASE 
                            WHEN e.registration_number REGEXP '^[0-9]' THEN e.registration_number 
                            ELSE NULL 
                        END) AS student_count,
            COUNT(DISTINCT CASE 
                            WHEN e.registration_number REGEXP '^[A-Za-z]' THEN e.registration_number 
                            ELSE NULL 
                        END) AS staff_count
        FROM (
            SELECT DISTINCT dept FROM student_data
            UNION
            SELECT DISTINCT dept FROM staff_data
        ) d
        LEFT JOIN entries e ON e.registration_number IN (
            SELECT registration_number FROM student_data WHERE dept = d.dept
            UNION
            SELECT staff_id FROM staff_data WHERE dept = d.dept
        )
        AND e.in_time >= ? AND e.in_time <= DATE_ADD(?, INTERVAL 1 DAY)
        GROUP BY d.dept
        ORDER BY d.dept ASC;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="department_wise_student_staff_count.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Department', 'Total Count', 'Student Count', 'Staff Count']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit; // stop execution after CSV export
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department-Wise Student and Staff Count</title>
    <style>
        /* Basic reset for consistent layout */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .form-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .form-container form {
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-container label {
            font-size: 16px;
            margin-bottom: 8px;
            color: #555;
        }

        .form-container input[type="date"] {
            width: 200px;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-container button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .form-container button:hover {
            background-color: #45a049;
        }

        .export-btn {
            background-color: #007bff;
            margin-left: 10px;
        }

        .export-btn:hover {
            background-color: #0056b3;
        }

        .alert {
            background-color: #ffcc00;
            color: #333;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }

        .report-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .report-table thead {
            background-color: #4CAF50;
            color: white;
        }

        .report-table th, .report-table td {
            padding: 12px 20px;
            text-align: left;
        }

        .report-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .report-table tr:hover {
            background-color: #e1f5e1;
        }

        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #f44336;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .back-button:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <h1>Department-Wise Student and Staff Count</h1>
    <div class="form-container">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
            <button type="submit">Generate Report</button>
            <button type="submit" name="export_csv" class="export-btn">Export to CSV</button>
        </form>
    </div>

    <div>
        <?php echo $report; ?>
    </div>

    <a href="report.php" class="back-button">Back to Dashboard</a>
</body>
</html>
