<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['studentNumber'])) {
    $con = getDbConnection();
    $studentNumber = $_POST['studentNumber'];

    // Query to get uploaded documents for the student
    $stmt = $con->prepare("SELECT d.DocumentType_Id, dt.Type_Name AS DocumentType, d.FileName 
                           FROM tbl_documents d
                           INNER JOIN tbl_documenttype dt ON d.DocumentType_Id = dt.DocumentType_Id
                           WHERE d.Student_Id = ?");
    $stmt->bind_param('s', $studentNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    $uploadedDocuments = [];
    while ($row = $result->fetch_assoc()) {
        $uploadedDocuments[] = $row; // Add document data to the list
    }

    echo json_encode($uploadedDocuments); // Return JSON-encoded data
    $stmt->close();
    $con->close();
}
?>
