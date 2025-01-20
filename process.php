<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'library_system');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Check if registration number is provided
if (isset($_POST['registration_number'])) {
    $reg_number = trim($_POST['registration_number']);
    
    if (empty($reg_number)) {
        echo json_encode(['status' => 'error', 'message' => 'Registration number cannot be empty.']);
        exit;
    }

    // Check if the registration number already has an entry in the database (no out_time)
    $check_sql = "SELECT id, in_time FROM entries WHERE registration_number = ? AND out_time IS NULL";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $reg_number);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // If an entry exists (in_time), update the out_time (Check-Out)
        $row = $result->fetch_assoc();
        $entry_id = $row['id'];

        $update_sql = "UPDATE entries SET out_time = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $entry_id);
        if ($update_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Out-time recorded successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to record out-time.']);
        }
    } else {
        // If no entry exists, insert a new in_time record (Check-In)
        $insert_sql = "INSERT INTO entries (registration_number, in_time) VALUES (?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("s", $reg_number);
        if ($insert_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'In-time recorded successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to record in-time.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No registration number provided.']);
}

// Close the database connection
$conn->close();
?>
