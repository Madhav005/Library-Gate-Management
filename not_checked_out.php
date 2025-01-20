<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'library_system');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$report = '';
$success_message = '';
$error_message = '';

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
        if ($stmt === false) {
            $error_message = "Error preparing the query.";
        } else {
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
                $update_stmt = $conn->prepare($sql);
                if ($update_stmt === false) {
                    $error_message = "Error preparing update query.";
                } else {
                    $update_stmt->bind_param("si", $formatted_out_time, $checkout_id);
                    if ($update_stmt->execute()) {
                        $success_message = "Out time updated successfully!";
                    } else {
                        $error_message = "Error executing update query: " . $update_stmt->error;
                    }
                    $update_stmt->close();
                }
            } else {
                $error_message = "No entry found with the specified ID.";
            }
            $stmt->close();  // Close the original statement after use
        }
    }
}

// Get all users who haven't checked out (out_time is NULL or "Not Checked Out")
$sql = "
    SELECT e.id, e.registration_number, s.name, s.dept, DATE(e.in_time) AS date, 
           TIME(e.in_time) AS in_time_time, IFNULL(TIME(e.out_time), 'Not Checked Out') AS out_time_time
    FROM entries e
    JOIN student_data s ON e.registration_number = s.registration_number
    WHERE e.out_time IS NULL
    ORDER BY e.in_time ASC;
";
$result = $conn->query($sql);

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
                        <td>" . ($row['out_time_time'] == 'Not Checked Out' ? "
                            <form method='POST' action=''>
                                <input type='hidden' name='checkout_id' value='{$row['id']}'>
                                <input type='time' name='out_time' required>
                                <button type='submit' name='checkout'>Checkout</button>
                            </form>" : "Checked Out") . "</td>
                    </tr>";
    }
    $report .= '</tbody></table>';
} else {
    $report = '<div class="alert">No users found who have not checked out.</div>';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not Checked Out Users</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f7f6;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        table th {
            background-color: #f4f4f4;
            color: #333;
        }

        table td {
            background-color: #fff;
        }

        .alert {
            padding: 10px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            text-align: center;
            margin-top: 20px;
        }

        .success-message {
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            text-align: center;
            margin-top: 20px;
        }

        .back-button {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-button:hover {
            background-color: #0056b3;
        }

        input[type="time"] {
            padding: 5px;
            font-size: 14px;
            border: 1px solid #ddd;
        }

        button {
            padding: 8px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
            background-color: #218838;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
    </style>
</head>
<body>
    <h1>Not Checked Out Users</h1>

    <!-- Display success or error messages -->
    <?php if ($success_message): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div>
        <?php echo $report; ?>
    </div>

    <a href="report.php" class="back-button">Back to Dashboard</a>
</body>
</html>
