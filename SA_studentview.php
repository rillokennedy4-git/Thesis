<?php

session_start();

// Check if the user is logged in by verifying the presence of `user_id` in the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: index.php");
    exit();
}

// Optional: Enforce role-based access
// Ensure that only users with the 'Staff' role can access this page
if ($_SESSION['role'] !== 'System Admin') {
    // Redirect to the login page or an error page if the role is not 'Staff'
    header("Location: index.php");
    exit();
}


include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn; 

// Check if StudentNumber is provided in the URL
if (isset($_GET['studentNumber'])) {
    $studentNumber = $_GET['studentNumber'];

    // Query to fetch student details including the Category and Record Status
    $sql = "
    SELECT 
        s.StudentNumber, 
        s.LastName, 
        s.FirstName, 
        s.MiddleName, 
        s.Gender, 
        c.course_name AS Course, 
        a.Academic_Year, 
        sem.Semester_Name, 
        s.Status, 
        cat.Category_Name,
        rs.Record_Status -- Added Record Status from tbl_record_status
    FROM 
        tbl_student s
    JOIN 
        tbl_course c ON s.Course_id = c.Course_id
    JOIN 
        tbl_academicyr a ON s.AcademicYr_id = a.AcademicYr_id
    JOIN 
        tbl_semester sem ON s.Semester_id = sem.Semester_id
    JOIN 
        tbl_category cat ON s.Category_id = cat.Category_id
    LEFT JOIN
        tbl_record_status rs ON s.StudentNumber = rs.StudentNumber -- Joining with tbl_record_status
    WHERE 
        s.StudentNumber = ?
    ";

    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $studentNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the student details
        $student = $result->fetch_assoc();
    } else {
        die("Student not found.");
    }

    // Fetch the submitted documents for this student
$submittedDocumentsSql = "
SELECT 
    d.DocumentType_Id
FROM 
    tbl_documents d
WHERE 
    d.Student_Id = ?
";
$stmtDocuments = $conn->prepare($submittedDocumentsSql);
$stmtDocuments->bind_param("s", $studentNumber);
$stmtDocuments->execute();
$submittedDocumentsResult = $stmtDocuments->get_result();

// Collect all the submitted document types in an array
$submittedDocuments = [];
while ($doc = $submittedDocumentsResult->fetch_assoc()) {
$submittedDocuments[] = $doc['DocumentType_Id'];
}

// Define the required document types based on student status
$requiredDocuments = [];
if ($student['Status'] === 'TRANSFEREE') {
$requiredDocuments = [
    3 => 'COA',
    8 => 'TOR',
    9 => 'Transfer Credentials',
    4 => 'Good Moral',
    5 => '2x2 Picture',
    10 => 'Application Admission',
    7 => 'Birth Certificate',
    6 => 'Barangay Clearance'
];
} else { // For "Regular" and "Irregular"
$requiredDocuments = [
    1 => 'Form137',
    3 => 'COA',
    2 => 'Form138',
    4 => 'Good Moral',
    5 => '2x2 Picture',
    7 => 'Birth Certificate',
    6 => 'Barangay Clearance'
];
}

// Check if all required documents have been submitted
$isComplete = true; // Assume all documents are submitted initially

foreach ($requiredDocuments as $docId => $docName) {
if (!in_array($docId, $submittedDocuments)) {
    $isComplete = false; // If any required document is missing, set to false
    break;
}
}

// If all documents are submitted, update the record status to "Complete"
if ($isComplete) {
$updateStatusSql = "
    INSERT INTO tbl_record_status (StudentNumber, Record_Status)
    VALUES (?, 'COMPLETE')
    ON DUPLICATE KEY UPDATE Record_Status = 'COMPLETE'
";
$stmtUpdateStatus = $conn->prepare($updateStatusSql);
$stmtUpdateStatus->bind_param("s", $studentNumber);
$stmtUpdateStatus->execute();
}

    // Query to fetch all documents with the document names and paths
    $documentsSql = "
    SELECT 
        d.FileName, 
        d.File_Path, 
        dt.Type_Name AS DocumentType
    FROM 
        tbl_documents d
    JOIN 
        tbl_documenttype dt ON d.DocumentType_Id = dt.DocumentType_ID
    WHERE 
        d.Student_Id = ?
    ";
    $stmtDocuments = $conn->prepare($documentsSql);
    $stmtDocuments->bind_param("s", $studentNumber);
    $stmtDocuments->execute();
    $documentsResult = $stmtDocuments->get_result(); // Store this result for the document section
} else {
    die("Invalid StudentNumber.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Archiving System</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/33styles.css" rel="stylesheet" />
    <link href="css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="shortcut icon" href="images/ccticon.png">
 
    <style>

body {
            background-color: #f8f9fa;
            
        }
        /* Updated Basic Information Section */
        .card-header {
            background-color: #343a40;
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 20px;
            background-color: #fff;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .profile-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(102, 4, 4, 0.1);
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 4px solid #e9ecef;
        }

        .student-info {
            flex-grow: 1;
            text-align: center;
        }

        .student-info h4 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            color: #343a40;
        }

        .student-info p {
            margin: 0;
            font-size: 1.1rem;
            color: #6c757d;
        }

        .student-details {
    text-align: left;
    font-size: 1rem;
    margin-top: 10px;
}

.student-details div {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

.student-details i {
    margin-right: 12px; /* Increased space between icon and label */
    color: #6c757d;
    min-width: 30px;
    text-align: center;
}

.student-details span {
    margin-right: 5px; /* Space between label and value */
    font-weight: 500; /* Slightly bold for labels */
    color: #343a40; /* Darker color for labels */
}

.student-details .value {
    color: #495057; /* Muted color for values */
    font-weight: 400; /* Normal weight for values */
}

        .info-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
            background-color: #ffffff; /* White background for the container */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            font-size: 1rem;
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .info-label {
            font-weight: 600;
            color: #343a40;
        }

        .info-value {
            color: #495057;
        }




        .banner {
            background: linear-gradient(90deg, #343a40, #495057);
            color: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        /* Updated Banner Design */
        .banner {
            background: linear-gradient(135deg,rgb(229, 61, 86),rgb(117, 35, 39)); /* Blue gradient */
            color: white;
            padding: 40px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Subtle shadow */
            position: relative;
            overflow: hidden;
        }

        .banner h1 {
            margin: 0;
            font-size: 2.8rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .banner p {
            margin: 10px 0 0;
            font-size: 1.2rem;
            font-weight: 300;
            opacity: 0.9;
        }

        .banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 10%, transparent 10.01%);
            background-size: 20px 20px;
            transform: rotate(45deg);
            pointer-events: none;
        }

        .banner::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
        }

        .table-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px; /* Add some spacing below the header */
        }
        .table-header-container .btn {
            display: flex;
            align-items: center;
        }
        .table-header-container .btn i {
            margin-right: 5px;
        }

    </style>



</head>
<body class="sb-nav-fixed">
<div >
            <?php include 'topnav2.php'; ?>
        </div>

        <div >
            <main>


            <div class="container mt-5">
        <div class="banner">
            <h1>Student Profile</h1>
          
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user"></i> Basic Information
            </div>
            <div class="card-body">
                <div class="profile-container">
                    <div class="d-flex align-items-center justify-content-center">
                        <img src="images/defaultdp.png" alt="Profile Picture" class="profile-pic">
                        <div class="student-info">
                            <h4><?php echo $student['LastName'] . ', ' . $student['FirstName'] . ' ' . $student['MiddleName']; ?></h4>
                            <p><?php echo $student['Course']; ?></p>
                        </div>
                    </div>
                    <div class="student-details">
                        <div><i class="fas fa-id-badge"></i><span>Student Number: </span> <?php echo $student['StudentNumber']; ?></div>
                        <div><i class="fas fa-calendar-alt"></i><span>Academic Year: </span> <?php echo $student['Academic_Year']; ?></div>
                        <div><i class="fas fa-calendar"></i><span>Semester: </span> <?php echo $student['Semester_Name']; ?></div>
                    </div>
                </div>
                <div class="info-container">
                    <div class="info-item">
                        <span class="info-label">Last Name:</span>
                        <span><?php echo $student['LastName']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">First Name:</span>
                        <span><?php echo $student['FirstName']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Middle Initial:</span>
                        <span><?php echo $student['MiddleName']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Gender:</span>
                        <span><?php echo $student['Gender']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span><?php echo $student['Status']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Category:</span>
                        <span><?php echo $student['Category_Name']; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Record Status:</span>
                        <span><?php echo $student['Record_Status'] ? $student['Record_Status'] : 'Not Available'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
    <!-- Student Documents Section -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file"></i> Student Documents
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Document Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($documentsResult->num_rows > 0) {
                            $i = 1;
                            while ($document = $documentsResult->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo $document['DocumentType']; ?></td>
                                    <td>
                                    <?php 
    // Determine the correct base path based on the student's category
    if (strtolower($student['Category_Name']) === 'archived') {
        $docPath = "/NEW/" . $document['File_Path']; // Archived files are already in 'archive/'
    } elseif (strtolower($student['Status']) === 'new student') {
        $docPath = "/NEW/upload/201files/" . basename($document['File_Path']); // New student documents
    } elseif (strtolower($student['Status']) === 'transferee') {
        $docPath = "/NEW/upload/transfereesfiles/" . basename($document['File_Path']); // Transferee documents
    }
?>

<a href="<?php echo $docPath; ?>" target="_blank" class="btn btn-primary btn-sm">View</a>

                                    </td>
                                </tr>
                            <?php } 
                        } else { ?>
                            <tr><td colspan="3">No documents found for this student.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Document Checklist Section -->
    <?php if (strtolower($student['Category_Name']) !== 'archived'): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-check-circle"></i> Document Checklist 
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Document Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php
    $index = 1;
    foreach ($requiredDocuments as $docId => $docName): ?>
        <tr>
            <td><?php echo $index++; ?></td>
            <td>
                <?php if (in_array($docId, $submittedDocuments)): ?>
                    <!-- Disable link if document is already submitted -->
                    <span class="text-secondary">
                        <?php echo $docName; ?>
                    </span>
                <?php else: ?>
                    <!-- Allow uploading for not submitted documents -->
                    <a 
                        href="javascript:void(0)" 
                        class="text-primary text-decoration-underline" 
                        data-doc-id="<?php echo $docId; ?>" 
                        data-student-id="<?php echo $student['StudentNumber']; ?>"
                        onclick="openUploadModal('<?php echo $docId; ?>', '<?php echo $student['StudentNumber']; ?>')"
                    >
                        <?php echo $docName; ?>
                    </a>
                <?php endif; ?>
            </td>
            <td>
                <?php if (in_array($docId, $submittedDocuments)): ?>
                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> Submitted</span>
                <?php else: ?>
                    <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Not Submitted</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>













<!-- File Upload Modal with Verification -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadForm" method="POST" enctype="multipart/form-data" action="single_upload.php">
                <div class="modal-body">
                    <input type="hidden" id="studentId" name="studentId">
                    <input type="hidden" id="documentTypeId" name="documentTypeId">
                    <div class="mb-3">
                        <label for="file" class="form-label">Choose File (PDF only):</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".pdf" required>
                    </div>
                    <!-- Preview Section -->
                    <div id="previewSection" class="mt-3" style="display: none;">
                        <h6>Document Preview:</h6>
                        <iframe id="previewFrame" width="100%" height="500px" style="border: 1px solid #ddd;"></iframe>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="verifyButton" class="btn btn-primary" style="display: none;">Verify</button>
                    <button type="submit" id="uploadButton" class="btn btn-success" style="display: none;">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>








<script>
    // Function to open the upload modal
    function openUploadModal(docId, studentId) {
        // Populate hidden fields in the form
        document.getElementById('studentId').value = studentId;
        document.getElementById('documentTypeId').value = docId;

        // Reset the modal state
        document.getElementById('previewSection').style.display = 'none';
        document.getElementById('verifyButton').style.display = 'none';
        document.getElementById('uploadButton').style.display = 'none';

        // Show the modal
        const uploadModalElement = document.getElementById('uploadModal');
        const modal = new bootstrap.Modal(uploadModalElement);
        modal.show();
    }

    // Handle file selection
    document.getElementById('file').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file && file.type === 'application/pdf') {
            // Show the preview section
            document.getElementById('previewSection').style.display = 'block';
            document.getElementById('previewFrame').src = URL.createObjectURL(file);

            // Show the Verify button
            document.getElementById('verifyButton').style.display = 'inline-block';
        } else {
            alert('Only PDF files are allowed.');
            e.target.value = ''; // Clear the file input
        }
    });

    // Handle Verify button click
    document.getElementById('verifyButton').addEventListener('click', function () {
        // Hide Verify button and show Upload button
        document.getElementById('verifyButton').style.display = 'none';
        document.getElementById('uploadButton').style.display = 'inline-block';
    });

    // Handle form submission
    document.getElementById('uploadForm').addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent default form submission

        const formData = new FormData(this);
        fetch('single_upload.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Reload the page to update the table
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
    });
</script>



<script>
    // Function to open the upload modal
    function openUploadModal(docId, studentId) {
        console.log("Opening modal with docId:", docId, "studentId:", studentId); // Debugging

        // Populate hidden fields in the form
        document.getElementById('studentId').value = studentId;
        document.getElementById('documentTypeId').value = docId;

        // Find the modal by ID and initialize it properly
        const uploadModalElement = document.getElementById('uploadModal');
        if (uploadModalElement) {
            const modal = new bootstrap.Modal(uploadModalElement);
            modal.show();
        } else {
            console.error("Modal with ID 'uploadModal' not found"); // Debugging
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        console.log("DOM fully loaded and parsed"); // Debugging

        // Handle form submission
document.getElementById('uploadForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent default form submission

    const formData = new FormData(this);

    // Show a loading indicator while the file is being uploaded
    Swal.fire({
        title: 'Uploading...',
        text: 'Please wait while your file is being uploaded.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('single_upload.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close the loading indicator
                Swal.close();

                // Show success message
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Reload the page to update the table
                    location.reload();
                });
            } else {
                // Show error message
                Swal.fire({
                    title: 'Error!',
                    text: data.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'An unexpected error occurred.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        });
});
</script>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
