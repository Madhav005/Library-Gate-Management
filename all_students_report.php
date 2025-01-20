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
$success_message = '';
$error_message = '';

// Generate report based on date range
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_date'], $_POST['end_date'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $sql = "
        SELECT e.id, e.registration_number, 
               IF(e.registration_number REGEXP '^[0-9]', s.name, st.name) AS name, 
               IF(e.registration_number REGEXP '^[0-9]', s.dept, st.dept) AS dept, 
               DATE(e.in_time) AS date, 
               TIME(e.in_time) AS in_time_time, 
               IFNULL(TIME(e.out_time), 'Not Checked Out') AS out_time_time
        FROM entries e
        LEFT JOIN student_data s ON e.registration_number = s.registration_number
        LEFT JOIN staff_data st ON e.registration_number = st.staff_id
        WHERE e.in_time >= ? AND e.in_time <= DATE_ADD(?, INTERVAL 1 DAY)
        ORDER BY e.in_time ASC;
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $report .= '<table>
                        <thead>
                            <tr>
                                <th>Registration Number</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>In Time</th>
                                <th>Out Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>';
        while ($row = $result->fetch_assoc()) {
            $report .= "<tr>
                            <td>{$row['registration_number']}</td>
                            <td>{$row['name']}</td>
                            <td>{$row['dept']}</td>
                            <td>{$row['date']}</td>
                            <td>{$row['in_time_time']}</td>
                            <td id='out_time_{$row['id']}'>{$row['out_time_time']}</td>
                            <td>";

            // Show checkout button if out_time is NULL
            if ($row['out_time_time'] == 'Not Checked Out') {
                $report .= "
                    <form method='POST' action=''>
                        <input type='hidden' name='checkout_id' value='{$row['id']}'>
                        <input type='time' name='out_time' required>
                        <button type='submit' name='checkout'>Checkout</button>
                    </form>";
            } else {
                $report .= "Checked Out";
            }

            $report .= "</td>
                        </tr>";
        }
        $report .= '</tbody></table>';
    } else {
        $report = '<div class="alert">No records found for the selected date range.</div>';
    }

    $stmt->close();
}

// Handle the checkout action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    $checkout_id = $_POST['checkout_id'];
    $out_time = $_POST['out_time'];

    // Validate the input time
    if (empty($out_time)) {
        $error_message = "Please enter a valid out time.";
    } else {
        // Get the in_time date from the database
        $sql = "SELECT in_time FROM entries WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $checkout_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $in_time_date = date("Y-m-d", strtotime($row['in_time']));  // Extract date from in_time

            // Format the out_time to the same date
            $formatted_out_time = $in_time_date . ' ' . $out_time . ':00'; // Append seconds

            // Update the out_time for the specific entry
            $sql = "UPDATE entries SET out_time = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("si", $formatted_out_time, $checkout_id);
                if ($stmt->execute()) {
                    $success_message = "Out time updated successfully!";
                    // Redirect to refresh the page after saving
                    header("Location: all_students_report.php?start_date={$start_date}&end_date={$end_date}&success_message={$success_message}");
                    exit();
                } else {
                    $error_message = "Error executing update query: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Error preparing update query: " . $conn->error;
            }
        } else {
            $error_message = "No entry found with the specified ID.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entry Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
        }
        .form-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            gap: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        table th {
            background-color: #f4f4f4;
        }
        .alert {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
        .success-message {
            margin-top: 20px;
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            text-align: center;
        }
        .error-message {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
        button {
            padding: 10px 20px;
            font-size: 14px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .export-btn {
            background-color: #007bff;
        }
        .export-btn:hover {
            background-color: #0056b3;
        }
        .back-button {
            display: block;
            margin: 20px 0;
            padding: 10px 20px;
            font-size: 14px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            text-align: center;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
    <script>
function exportTableToCSV(filename) {
    var table = document.querySelector("table"); // Get the table
    var rows = Array.from(table.rows); // Convert rows into an array
    
    // Create CSV content
    var csvContent = "";
    
    // Loop through each row and extract the cells
    rows.forEach(function(row, rowIndex) {
        var rowData = Array.from(row.cells).map(function(cell) {
            return cell.innerText; // Get the text content of each cell
        });
        csvContent += rowData.join(",") + "\n"; // Add comma-separated values for each row
    });
    
    // Create a link to trigger the download
    var link = document.createElement('a');
    link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent));
    link.setAttribute('download', filename); // Set the filename for download
    
    // Trigger the click to download the file
    link.click();
}
    </script>
</head>
<body>

<h1>Library Entry Report</h1>

<div class="form-container">
    <form method="POST" action="">
        <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
        <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
        <button type="submit">Generate Report</button>
        <button class="export-btn" onclick="exportTableToCSV('students_report.csv')">Export to CSV</button>
    </form>
</div>

<?php if ($success_message): ?>
    <div class="success-message"><?php echo $success_message; ?></div>
<?php elseif ($error_message): ?>
    <div class="error-message"><?php echo $error_message; ?></div>
<?php endif; ?>

<?php echo $report; ?>

<a href="report.php" class="back-button">Back to Dashboard</a>

</body>
</html>
