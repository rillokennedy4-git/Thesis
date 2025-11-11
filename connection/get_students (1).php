<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "db_archive");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['query'])) {
    $query = $_POST['query'];
    
    // Fetch matching student numbers from tbl_student
    $sql = "SELECT StudentNumber FROM tbl_student WHERE StudentNumber LIKE ? LIMIT 10";
    $stmt = $conn->prepare($sql);
    $searchQuery = "%" . $query . "%";
    $stmt->bind_param('s', $searchQuery);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<li class="list-group-item">' . $row['StudentNumber'] . '</li>';
        }
    } else {
        echo '<li class="list-group-item">No student found</li>';
    }
}

$conn->close();
?>
