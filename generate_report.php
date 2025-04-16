<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "cron";
$password = "1234";
$dbname = "asterisk";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Get form data
$startDate = isset($_POST['startDate']) ? $_POST['startDate'] . ' 00:00:00' : '';
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] . ' 23:59:59' : '';
$user = isset($_POST['user']) ? $_POST['user'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Input validation (basic date format check)
if (empty($startDate) || empty($endDate) || empty($user) || empty($status) ||
    !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $startDate) ||
    !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $endDate)) {
    echo json_encode(["error" => "Invalid or missing parameters"]);
    exit;
}

// Prepare the SQL statement
$sql = "
    SELECT lead_id, status, user, list_id, phone_number, title, first_name, last_name, address1, address2, city, state, postal_code, email, comments, entry_date
    FROM vicidial_list
    WHERE modify_date BETWEEN ? AND ?
    AND user = ?
    AND status = ?
";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    exit;
}

// Bind parameters
$stmt->bind_param("ssss", $startDate, $endDate, $user, $status);

// Execute the statement
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo json_encode(["error" => "Query failed: " . $stmt->error]);
    exit;
}

$report = array();
while($row = $result->fetch_assoc()) {
    $report[] = $row;
}

echo json_encode($report);

$stmt->close();
$conn->close();
?>
