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
    e.in_time
FROM entries e
LEFT JOIN student_data sd ON e.registration_number = sd.registration_number
LEFT JOIN staff_data s ON e.registration_number = s.staff_id  
WHERE e.out_time IS NULL AND DATE(e.in_time) = CURDATE();

";

$result = $conn->query($sql);

// Array to store the list of users
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Return the list of users as JSON
echo json_encode($users);

// Close the database connection
$conn->close();
?>
