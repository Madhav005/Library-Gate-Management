<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'library_system');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Query to fetch users who are checked in (out_time is NULL)
$sql = "
   SELECT 
    e.registration_number, 
    COALESCE(sd.name, s.name) AS name,  -- Show student name if found, otherwise staff name
    COALESCE(sd.dept, s.dept) AS dept,  -- Show student dept if found, otherwise staff dept
    e.out_time
FROM entries e
LEFT JOIN student_data sd ON e.registration_number = sd.registration_number
LEFT JOIN staff_data s ON e.registration_number = s.staff_id  
WHERE e.out_time IS NOT NULL AND DATE(e.in_time) = CURDATE();
";

// Execute the query
$result = $conn->query($sql);

// Check for query execution errors
if (!$result) {
    die('Query failed: ' . $conn->error);
}

$checked_out_users = [];

// Fetch the result rows
while ($row = $result->fetch_assoc()) {
    $checked_out_users[] = $row;  // Add checked-out user to the list
}

// Return the list of checked-out users as JSON
echo json_encode($checked_out_users);

// Close the database connection
$conn->close();
?>
