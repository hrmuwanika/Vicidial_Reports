<?php
/**
 * Web-based script to search for MP3 recordings in the Asterisk monitor directory with a date filter.
 *
 * This script searches for files with the .mp3 extension within the
 * /var/spool/asterisk/monitorDONE/MP3 directory and its subdirectories,
 * filtering by file modification date, and provides a web interface for setting the date range.
 *
 * @author AI Assistant
 * @version 1.6
 * @date 2025-04-16
 */

// Define the directory to search in.
$monitor_dir = '/var/spool/asterisk/monitorDONE/MP3';

// Initialize date range.  Use today as a default end date.
$start_date = isset($_GET['start_date']) ? strtotime($_GET['start_date'] . ' 00:00:00') : strtotime(date('Y-m-d', strtotime('-7 days')) . ' 00:00:00'); // Default: 7 days ago
$end_date = isset($_GET['end_date']) ? strtotime($_GET['end_date'] . ' 23:59:59') : strtotime(date('Y-m-d') . ' 23:59:59'); // Default: today

// Function to recursively search for files in a directory with a date filter.
function search_files_with_date_filter(string $dir, string $extension, int $start_time, int $end_time): array
{
    $files = [];

    // Check if the directory exists.
    if (!is_dir($dir)) {
        echo "Error: Directory '$dir' not found or is not a directory.\n";
        return []; // Return empty array on error
    }

    // Open the directory.
    $handle = opendir($dir);
    if ($handle) {
        // Read directory entries.
        while (false !== ($entry = readdir($handle))) {
            // Skip current and parent directory entries.
            if ($entry != "." && $entry != "..") {
                $file_path = $dir . DIRECTORY_SEPARATOR . $entry;
                // Check if it's a directory.
                if (is_dir($file_path)) {
                    // Recursively search in subdirectory.
                    $files = array_merge($files, search_files_with_date_filter($file_path, $extension, $start_time, $end_time));
                } else {
                    // Check if it's a file.
                    if (is_file($file_path)) { // Added check to make sure it is a file.
                        // Check if it's a file with the desired extension.
                        if (strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === strtolower($extension)) {
                            // Get the file modification time.
                            $file_modified_time = filemtime($file_path);
                            // Check if the file modification time is within the specified range.
                            if ($file_modified_time >= $start_time && $file_modified_time <= $end_time) {
                                $files[] = $file_path;
                            }
                        }
                    }
                }
            }
        }
        // Close the directory handle.
        closedir($handle);
    } else {
        echo "Error: Could not open directory '$dir'.\n";
        return []; // Return empty array on error
    }
    return $files;
}

// Call the function to search for MP3 files with the date filter.
$mp3_files = search_files_with_date_filter($monitor_dir, 'mp3', $start_date, $end_date);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Recording Search</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            margin-bottom: 20px;
        }
        label {
            display: inline-block;
            width: 100px;
            margin-right: 10px;
        }
        input[type="text"] {
            width: 150px;
            padding: 5px;
        }
        button {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        li {
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        li:last-child {
            border-bottom: none;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <h2>Call Recording Search</h2>

    <form action="" method="get">
        <label for="start_date">Start Date:</label>
        <input type="text" id="start_date" name="start_date" value="<?php echo date('Y-m-d', $start_date); ?>">
        <br><br>
        <label for="end_date">End Date:</label>
        <input type="text" id="end_date" name="end_date" value="<?php echo date('Y-m-d', $end_date); ?>">
        <br><br>
        <button type="submit">Search</button>
    </form>

    <?php
    // Display the results.
    if (count($mp3_files) > 0) {
        echo "Found " . count($mp3_files) . " MP3 file(s) within the specified date range:<br><br>";
        echo "<ul>";
        foreach ($mp3_files as $full_file_path) {
            $filename = basename($full_file_path);
            // Create a relative path from the base monitor directory
            $relative_path = ltrim(str_replace($monitor_dir, '', $full_file_path), '/');
            $encoded_path = urlencode($relative_path);

            echo "<li>";
            echo "<b>Filename:</b> " . $filename . "<br>";
            echo "<b>Modification Time:</b> " . date('Y-m-d H:i:s', filemtime($full_file_path)) . "<br>";
            echo '<audio controls>';
            echo '<source src="play_audio.php?file=' . $encoded_path . '" type="audio/mpeg">';
            echo 'Your browser does not support the audio element.';
            echo '</audio>';
            echo '<br>';
            echo '<a href="download_audio.php?file=' . $encoded_path . '" download="' . $filename . '">Download</a>';
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "No MP3 files found in '$monitor_dir' or its subdirectories within the specified date range.";
    }
    ?>
</body>
</html>
