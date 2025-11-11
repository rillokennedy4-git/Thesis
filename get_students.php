<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $con = getDbConnection();
    $query = trim($_POST['query']);

    $stmt = $con->prepare("SELECT StudentNumber, CONCAT(LastName, ', ', FirstName, ' ', MiddleName) AS StudentName, Status, Category_Id FROM tbl_student WHERE StudentNumber LIKE ? LIMIT 10");
    $search = "%$query%";
    $stmt->bind_param('s', $search);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $archived = $row['Category_Id'] == 2 ? "<span class='text-danger fw-bold'>ARCHIVED</span>" : "";
            echo "<li class='list-group-item' style='cursor: pointer;' 
                data-student-number='{$row['StudentNumber']}' 
                data-student-name='{$row['StudentName']}'
                data-status='{$row['Status']}' 
                data-category='{$row['Category_Id']}'>
                <strong>{$row['StudentNumber']}</strong> - {$row['StudentName']} {$archived}
            </li>";
        }
    } else {
        echo "<li class='list-group-item text-muted'>No matches found</li>";
    }

    $stmt->close();
    $con->close();
}
?>