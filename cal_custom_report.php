<!DOCTYPE html>
<html>
<head>
    <title>Call Log Data</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">   
    <style>
        body {
            font-family: font-family: Arial, sans-serif;
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
        }

        form {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        form label {
            margin-right: 10px;
        }

        form input[type="date"] {
            padding: 8px;
            margin-right: 10px;
        }

        button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            margin-right: 10px;
        }

        @media (max-width: 600px) {
            table, form {
                width: 100%;
            }

            form {
                flex-direction: column;
                align-items: flex-start;
            }

            form input[type="date"], form input[type="submit"] {
                margin-bottom: 10px;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php

$servername = "localhost";
$username = "cron";
$password = "1234";
$dbname = "asterisk";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filter dates from form submission
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '0000-00-00'; // Default start date
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '0000-00-00';     // Default end date

// SQL query with date filter
$sql = "SELECT
    vl.lead_id,
    vlog.call_date,
    vl.user,
    vl.phone_number,
    vl.first_name,
    vl.address1,
    vl.last_name,
    vl.address2,
    vl.address3,
    vl.city,
    vlog.length_in_sec,
    vl.comments,
    vcn.status_name
FROM
    vicidial_list vl
JOIN
    vicidial_log vlog ON vl.lead_id = vlog.lead_id
LEFT JOIN
    vicidial_statuses vcn ON vlog.status = vcn.status
WHERE
    vlog.call_date BETWEEN '$startDate' AND '$endDate'
ORDER BY
    vlog.call_date DESC;";

$result = $conn->query($sql);

// Form for date selection
echo "<form method='get'>";
echo "<label for='startDate'>Start Date:</label><input type='date' name='startDate' id='startDate' value='$startDate'>";
echo "<label for='endDate'>End Date:</label><input type='date' name='endDate' id='endDate' value='$endDate'>";
echo "<input type='submit' value='Filter'>";
echo "</form><br>";

if ($result->num_rows > 0) {
    // Start the HTML table
    echo "<table>";

    // Output header row (matching the SQL query)
    echo "<tr>";
    echo "<th>Lead ID</th><th>Call Date</th><th>Agent</th><th>Phone Number</th><th>Customer Name</th><th>Outstanding Balance</th><th>Date of Payment</th><th>Account number</th><th>National ID No.</th><th>Customer Number</th><th>Length in sec</th><th>Customer feedback</th><th>Disposition</th>";
    echo "</tr>";

    // Store data for Excel export in a JavaScript variable as an array of objects
    $excel_data = [];
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row["lead_id"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["call_date"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["user"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["phone_number"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["first_name"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["address1"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["last_name"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["address2"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["address3"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["city"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["length_in_sec"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["comments"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["status_name"]) . "</td>";
        echo "</tr>";

        $excel_data[] = [
            'Lead ID' => $row["lead_id"],
            'Call Date' => $row["call_date"],
            'Agent' => $row["user"],
            'Phone Number' => $row["phone_number"],
            'Customer Name' => $row["first_name"] . ' ' . $row["last_name"],
            'Outstanding Balance' => $row["address1"],
            'Date of Payment' => $row["address2"],
            'Account number' => $row["address3"],
            'National ID No.' => $row["city"],
            'Customer Number' => '', // No corresponding field in your SQL
            'Length in sec' => $row["length_in_sec"],
            'Customer feedback' => $row["comments"],
            'Disposition' => $row["status_name"]
        ];
    }

    // End the HTML table
    echo "</table>";

    // Encode the Excel data as a JSON string for JavaScript
    $json_excel_data = json_encode($excel_data);

    // Add export button with JavaScript download for Excel 2007 (.xlsx)
    echo '<button onclick="downloadExcel(' . htmlspecialchars($json_excel_data, ENT_QUOTES, 'UTF-8') . ')">Export to Excel (xlsx)</button>';
    echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>';
    echo '<script>
        function downloadExcel(excelData) {
            // Create a new workbook
            const wb = XLSX.utils.book_new();
            const ws_name = "Call Log";

            // Convert JSON data to worksheet
            const ws_data = [Object.keys(excelData[0])]; // Header row
            excelData.forEach(row => {
                ws_data.push(Object.values(row));
            });
            const ws = XLSX.utils.aoa_to_sheet(ws_data);

            // Add the worksheet to the workbook
            XLSX.utils.book_append_sheet(wb, ws, ws_name);

            // Generate and trigger the download
            XLSX.writeFile(wb, "call_log_report_" + new Date().toISOString().slice(0,19).replace(/[-:T]/g, "") + ".xlsx");
        }
    </script>';

} else {
    echo "0 results";
}

$conn->close();
?>

</body>
</html>
