<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the user is logged in and is a System Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'System Admin') {
    header("Location: index.php");
    exit();
}

// Include the database connection
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn;

// Define the filename for the SQL file
$sqlFileName = 'db_archive.sql'; // Fixed filename

// Set headers for file download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $sqlFileName . '"');

// Fetch all tables in the database
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

// Export each table
foreach ($tables as $table) {
    // Fetch table structure
    $createTable = $conn->query("SHOW CREATE TABLE {$table}")->fetch_row()[1];
    echo "-- Table structure for table `{$table}`\n";
    echo $createTable . ";\n\n";

    // Fetch table data
    $result = $conn->query("SELECT * FROM {$table}");
    while ($row = $result->fetch_assoc()) {
        $columns = implode('`, `', array_keys($row));
        $values = implode("', '", array_map([$conn, 'real_escape_string'], array_values($row)));
        echo "INSERT INTO `{$table}` (`{$columns}`) VALUES ('{$values}');\n";
    }
    echo "\n";
}

exit();
?>