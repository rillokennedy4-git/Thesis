<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: ../index.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

if ($conn->connect_error) {
    $error_message = "Database connection failed: " . $conn->connect_error;
    echo "<script>var error_message = '$error_message';</script>";
    $conn = null; // Close connection variable
}

// Flag to check if an error occurred
$alert_message = '';
$alert_type = '';

// Handle file upload or update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $conn) {
    $isUpdate = isset($_POST['documentId']) && $_POST['documentId'] !== '';
    
    if ($isUpdate) {
        // Process file update
        $documentId = $_POST['documentId'];
        $stmtGetInfo = $conn->prepare("SELECT File_Path FROM tbl_documents WHERE Document_Id = ?");
        $stmtGetInfo->bind_param('i', $documentId);
        $stmtGetInfo->execute();
        $result = $stmtGetInfo->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $absoluteFilePath = __DIR__ . "/" . $row['File_Path'];

            if (isset($_FILES['file']) && $_FILES['file']['error'] == 0 && $_FILES['file']['type'] == "application/pdf") {
                if (file_exists($absoluteFilePath)) unlink($absoluteFilePath);

                if (move_uploaded_file($_FILES['file']['tmp_name'], $absoluteFilePath)) {
                    $alert_message = "File updated successfully.";
                    $alert_type = "success";
                } else {
                    $alert_message = "Error moving the uploaded file.";
                    $alert_type = "error";
                }
            } else {
                $alert_message = "Invalid file type or no file uploaded.";
                $alert_type = "error";
            }
        } else {
            $alert_message = "Document not found.";
            $alert_type = "error";
        }
    } else {
        // New file upload
        $studentId = $_POST['studentId'];
        $documentTypeId = $_POST['documentTypeId'];
        $documentTypeName = $_POST['documentTypeName'];
        $newFileName = $studentId . "_" . preg_replace('/[^A-Za-z0-9_]/', '_', $documentTypeName) . ".pdf";
        $relativeFilePath = "upload/201files/" . $newFileName;

        // Duplicate check
        $stmtCheck = $conn->prepare("SELECT * FROM tbl_documents WHERE Student_Id = ? AND DocumentType_Id = ?");
        $stmtCheck->bind_param('ii', $studentId, $documentTypeId);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            $alert_message = "A document for this student and document type already exists.";
            $alert_type = "error";
        } elseif (move_uploaded_file($_FILES['file']['tmp_name'], __DIR__ . "/upload/201files/" . $newFileName)) {
            $stmtInsert = $conn->prepare("INSERT INTO tbl_documents (Student_Id, DocumentType_Id, File_Path, FileName, Category_Id) VALUES (?, ?, ?, ?, ?)");
            $categoryId = 1;
            $stmtInsert->bind_param('iissi', $studentId, $documentTypeId, $relativeFilePath, $newFileName, $categoryId);
            if ($stmtInsert->execute()) {
                $alert_message = "File uploaded successfully.";
                $alert_type = "success";
            } else {
                $alert_message = "Database error: " . $stmtInsert->error;
                $alert_type = "error";
            }
        } else {
            $alert_message = "Error moving the uploaded file.";
            $alert_type = "error";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload 201 File</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php if ($alert_message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $alert_type; ?>',
                title: '<?php echo ($alert_type == "success") ? "Success" : "Error"; ?>',
                text: '<?php echo $alert_message; ?>',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '201upload.php';
            });
        });
    </script>
<?php endif; ?>

</body>
</html>
