<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'library_system');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Initialize variables
$start_date = '';
$end_date = '';
$department = '';
$report = '';

// Generate report based on date range and department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_date'], $_POST['end_date'], $_POST['department'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $department = $_POST['department'];

    $sql = "
        SELECT e.registration_number, e.in_time, e.out_time, s.dept
        FROM entries e
        JOIN student_data s ON e.registration_number = s.registration_number
        WHERE e.in_time >= ? AND e.in_time <= DATE_ADD(?, INTERVAL 1 DAY) 
        AND s.dept = ?
        ORDER BY e.in_time ASC;
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $start_date, $end_date, $department);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $report .= '<table>
                        <thead>
                            <tr>
                                <th>Registration Number</th>
                                <th>Date</th>
                                <th>In Time</th>
                                <th>Out Time</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>';
        while ($row = $result->fetch_assoc()) {
            // Extract date from in_time
            $date = date('Y-m-d', strtotime($row['in_time']));
            $report .= "<tr>
                            <td>{$row['registration_number']}</td>
                            <td>{$date}</td>
                            <td>{$row['in_time']}</td>
                            <td>{$row['out_time']}</td>
                            <td>{$row['dept']}</td>
                        </tr>";
        }
        $report .= '</tbody></table>';
    } else {
        $report = '<div class="alert">No records found for the selected department and date range.</div>';
    }

    $stmt->close();
}

// Export to CSV
if (isset($_POST['export_csv']) && !empty($start_date) && !empty($end_date) && !empty($department)) {
    $sql = "
        SELECT e.registration_number, e.in_time, e.out_time, s.dept
        FROM entries e
        JOIN student_data s ON e.registration_number = s.registration_number
        WHERE e.in_time >= ? AND e.in_time <= DATE_ADD(?, INTERVAL 1 DAY) 
        AND s.dept = ?
        ORDER BY e.in_time ASC;
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $start_date, $end_date, $department);
    $stmt->execute();
    $result = $stmt->get_result();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="department_wise_report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Registration Number', 'Date', 'In Time', 'Out Time', 'Department']);

    while ($row = $result->fetch_assoc()) {
        // Extract date from in_time
        $date = date('Y-m-d', strtotime($row['in_time']));
        fputcsv($output, [$row['registration_number'], $date, $row['in_time'], $row['out_time'], $row['dept']]);
    }

    fclose($output);
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department-Wise Entry Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f9;
        }
        h1 {
            text-align: center;
            color: #444;
        }
        .form-container {
            margin: 20px auto;
            width: 80%;
            text-align: center;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        input, select, button {
            margin: 10px 5px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #28a745;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            margin: 20px auto;
            border-collapse: collapse;
            text-align: left;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .alert {
            text-align: center;
            padding: 10px;
            color: #d9534f;
        }
        .back-button {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            width: 200px;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Department-Wise Entry Report</h1>
    <div class="form-container">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
            <label for="department">Department:</label>
            <select id="department" name="department" required>
                <option value="" disabled selected>Select Department</option>
                <option value="CSE" <?php echo ($department == 'CSE') ? 'selected' : ''; ?>>CSE</option>
                <option value="IT" <?php echo ($department == 'IT') ? 'selected' : ''; ?>>IT</option>
                <option value="AI&DS" <?php echo ($department == 'AI&DS') ? 'selected' : ''; ?>>AI&DS</option>
                <option value="AI&ML" <?php echo ($department == 'AI&ML') ? 'selected' : ''; ?>>AI&ML</option>
                <option value="CSE - CS" <?php echo ($department == 'CSE - CS') ? 'selected' : ''; ?>>CSE - CS</option>
                <option value="CSE - IOT" <?php echo ($department == 'CSE - IOT') ? 'selected' : ''; ?>>CSE - IOT</option>
                <option value="ECE" <?php echo ($department == 'ECE') ? 'selected' : ''; ?>>ECE</option>
                <option value="EEE" <?php echo ($department == 'EEE') ? 'selected' : ''; ?>>EEE</option>
                <option value="MECH" <?php echo ($department == 'MECH') ? 'selected' : ''; ?>>MECH</option>
                <option value="CIVIL" <?php echo ($department == 'CIVIL') ? 'selected' : ''; ?>>CIVIL</option>
                <option value="EIE" <?php echo ($department == 'EIE') ? 'selected' : ''; ?>>EIE</option>
                <option value="AGRI" <?php echo ($department == 'AGRI') ? 'selected' : ''; ?>>AGRI</option>
                <option value="MEE" <?php echo ($department == 'MEE') ? 'selected' : ''; ?>>MEE</option>
                <option value="CHE" <?php echo ($department == 'CHE') ? 'selected' : ''; ?>>CHE</option>
                <option value="" <?php echo ($department == 'LIBRARY') ? 'selected' : ''; ?>>LIBRARY</option>
            </select>
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
