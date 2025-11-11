<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "db_archive");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentId = $_POST['studentId'];
    $documentTypeId = $_POST['documentTypeId']; // This is the DocumentType_Id
    $documentTypeName = $_POST['documentTypeName']; // Get the document type name from the form
    $categoryId = 1; // Automatically set Category_Id to 1 (Active)

    // Check if file was uploaded without errors
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];

        // Check if the uploaded file is a PDF
        $allowedFileType = "application/pdf";
        if ($fileType == $allowedFileType) {
            // Define the new file name based on the student number and document type
            $newFileName = $studentId . "_" . str_replace(" ", "_", $documentTypeName) . ".pdf"; // Correct file name

            // Define the upload directory relative to the script
            $uploadDir = "uploads/";
            $absoluteUploadDir = __DIR__ . "/uploads/"; // Absolute path using __DIR__

            // Create the upload directory if it does not exist
            if (!is_dir($absoluteUploadDir)) {
                mkdir($absoluteUploadDir, 0777, true);
            }

            // Define the full path where the file will be moved
            $filePath = $absoluteUploadDir . $newFileName;

            // Move the uploaded file to the correct directory with the new name
            if (move_uploaded_file($fileTmpPath, $filePath)) {
                // Store the correct relative file path and file name in the database
                $relativeFilePath = "connection/uploads/" . $newFileName;

                // Insert the file information into the database
                $sql = "INSERT INTO tbl_documents (Student_Id, DocumentType_Id, File_Path, FileName, Category_Id) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iissi', $studentId, $documentTypeId, $relativeFilePath, $newFileName, $categoryId);

                if ($stmt->execute()) {
                    echo "File uploaded and record saved successfully.";
                } else {
                    echo "Error: " . $stmt->error;
                }
            } else {
                echo "Error moving the uploaded file.";
            }
        } else {
            echo "Invalid file type. Only PDF files are allowed.";
        }
    } else {
        echo "No file uploaded or there was an error uploading the file.";
    }
}

$conn->close();
?>
