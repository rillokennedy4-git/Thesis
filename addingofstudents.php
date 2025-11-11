<?php
// Start output buffering to prevent output before headers
ob_start(); // Start output buffering at the top
session_start();

// Check if the user is logged in by verifying the presence of `user_id` in the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: index.php");
    exit();
}

// Optional: Enforce role-based access
// Ensure that only users with the 'Staff' role can access this page
if ($_SESSION['role'] !== 'Staff') {
    // Redirect to the login page or an error page if the role is not 'Staff'
    header("Location: index.php");
    exit();
}

// Include the Composer autoloader to load PhpSpreadsheet and other dependencies
require __DIR__ . '/vendor/autoload.php';

// Include the correct database connection file
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

$error = ""; // Initialize error variable
$success = false; // To check if record added successfully
$successMessage = ""; // To show success message
$errors = []; // To collect errors
$con = getDbConnection(); // Ensure the function is defined in the included file

// Function to get courses
function getCourses() {
    $con = getDbConnection();
    $sql = "SELECT course_id, course_name FROM tbl_course";
    $result = $con->query($sql);
    $courses = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
    
    $con->close();
    return $courses;
}

// Function to get academic years
function getAcademicYears() {
    $con = getDbConnection();
    $sql = "SELECT academicYr_id, Academic_Year FROM tbl_academicyr";
    $result = $con->query($sql);
    $academicYears = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $academicYears[] = $row;
        }
    }
    
    $con->close();
    return $academicYears;
}

// Function to get semesters
function getSemesters() {
    $con = getDbConnection();
    $sql = "SELECT semester_id, semester_name FROM tbl_semester";
    $result = $con->query($sql);
    $semesters = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $semesters[] = $row;
        }
    }
    
    $con->close();
    return $semesters;
}

// Handle Excel template download
if (isset($_POST['download_template'])) {
    // Clear any previously sent output
    if (ob_get_length()) ob_end_clean(); // Clear the buffer before headers

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Add headers to the Excel sheet
    $sheet->setCellValue('A1', 'StudentNumber');
    $sheet->setCellValue('B1', 'LastName');
    $sheet->setCellValue('C1', 'FirstName');
    $sheet->setCellValue('D1', 'MiddleName');
    $sheet->setCellValue('E1', 'Gender');
    $sheet->setCellValue('F1', 'Program');
    $sheet->setCellValue('G1', 'Status');
    $sheet->setCellValue('H1', 'Academic Year');
    $sheet->setCellValue('I1', 'Semester');

    // Set the file name and export the Excel template
    $writer = new Xlsx($spreadsheet);
    $fileName = 'Student_Registration_Template.xlsx';

    // Send headers to prompt file download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $writer->save('php://output'); // Output to the browser
    exit; // Stop further execution
}

// Handle Excel file upload and data processing
if (isset($_POST['upload_excel'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    // Verify if file uploaded successfully
    if (!is_uploaded_file($file)) {
        $errors[] = "Failed to upload the file.";
    }

    // Load the Excel file
    try {
        $spreadsheet = IOFactory::load($file);
    } catch (Exception $e) {
        $errors[] = "Error loading file: " . $e->getMessage();
    }

    if (empty($errors)) {
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow(); // Get the total number of rows

        $con = getDbConnection();

        $successCount = 0;

        for ($row = 2; $row <= $highestRow; $row++) { // Start from row 2, assuming row 1 is headers
            $studentNumber = strtoupper(trim($sheet->getCell('A' . $row)->getValue()));
            $lastName = strtoupper(trim($sheet->getCell('B' . $row)->getValue()));
            $firstName = strtoupper(trim($sheet->getCell('C' . $row)->getValue()));
            $middleName = strtoupper(trim($sheet->getCell('D' . $row)->getValue()));
            $middleName = $middleName ?: ""; // Set to empty string if blank
            $gender = strtoupper(trim($sheet->getCell('E' . $row)->getValue()));
            $courseName = strtoupper(trim($sheet->getCell('F' . $row)->getValue()));
            $status = strtoupper(trim($sheet->getCell('G' . $row)->getValue()));
            $academicYear = strtoupper(trim($sheet->getCell('H' . $row)->getValue()));
            $semesterName = strtoupper(trim($sheet->getCell('I' . $row)->getValue()));
            $categoryId = 1; // Default category

            // Validate Course_Id
            $stmt = $con->prepare("SELECT course_id FROM tbl_course WHERE UPPER(course_name) = ?");
            $stmt->bind_param("s", $courseName);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($courseId);
                $stmt->fetch();
            } else {
                $errors[] = "Row $row: Invalid Course - $courseName does not exist.";
                continue;
            }

            // Validate AcademicYr_Id
            $stmt = $con->prepare("SELECT AcademicYr_Id FROM tbl_academicyr WHERE UPPER(Academic_Year) = ?");
            $stmt->bind_param("s", $academicYear);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($academicYrId);
                $stmt->fetch();
            } else {
                $errors[] = "Row $row: Invalid Academic Year - $academicYear does not exist.";
                continue;
            }

            // Validate Semester_Id
            $stmt = $con->prepare("SELECT Semester_Id FROM tbl_semester WHERE UPPER(Semester_Name) = ?");
            $stmt->bind_param("s", $semesterName);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($semesterId);
                $stmt->fetch();
            } else {
                $errors[] = "Row $row: Invalid Semester - $semesterName does not exist.";
                continue;
            }

            // Check if student number already exists
            $stmt = $con->prepare("SELECT StudentNumber FROM tbl_student WHERE StudentNumber = ?");
            $stmt->bind_param("s", $studentNumber);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "Row $row: Student number $studentNumber already exists.";
            } else {
                // Insert student data
                $stmt = $con->prepare("INSERT INTO tbl_student (StudentNumber, LastName, FirstName, MiddleName, Gender, Course_Id, Status, AcademicYr_Id, Semester_Id, Category_Id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssisiii", $studentNumber, $lastName, $firstName, $middleName, $gender, $courseId, $status, $academicYrId, $semesterId, $categoryId);

                if ($stmt->execute()) {
                    // Insert default status "pending" into tbl_record_status
                    $recordStmt = $con->prepare("INSERT INTO tbl_record_status (StudentNumber, record_status) VALUES (?, 'INCOMPLETE')");
                    $recordStmt->bind_param("s", $studentNumber);
                    $recordStmt->execute();
                    $recordStmt->close();

                    $successCount++;
                } else {
                    $errors[] = "Row $row: Error inserting data - " . $stmt->error;
                }
            }
        }

        $con->close();

        if (empty($errors)) {
            $successMessage = "All data successfully inserted.";
        }
    }
}

// Handling form submission for manual student entry
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['upload_excel']) && !isset($_POST['download_template'])) {
    // Retrieve form data
    $studentNumber = strtoupper(trim($_POST['StudentNumber']));
    $lastName = strtoupper(trim($_POST['LastName']));
    $firstName = strtoupper(trim($_POST['FirstName']));
    $middleName = isset($_POST['MiddleName']) && !empty(trim($_POST['MiddleName'])) ? strtoupper(trim($_POST['MiddleName'])) : "";
    $gender = strtoupper(trim($_POST['Gender']));
    $courseId = $_POST['Course_id'];
    $status = strtoupper(trim($_POST['Status']));
    $academicYrId = $_POST['AcademicYr_id'];
    $semesterId = $_POST['Semester_id'];
    $categoryId = 1; // Assuming 1 is the default value for active students

    // Normalize the "Status" field for consistent values
    if ($status === 'NEW STUDENT') {
        $status = 'NEW STUDENT';
    }

    // Validate input data (you can add more validation here)
    if (empty($studentNumber) || empty($lastName) || empty($firstName) || empty($gender) || empty($courseId) || empty($status) || empty($academicYrId) || empty($semesterId)) {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {
        // Check if student number already exists
        $stmt = $con->prepare("SELECT StudentNumber FROM tbl_student WHERE StudentNumber = ?");
        $stmt->bind_param("s", $studentNumber);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Student number $studentNumber already exists.";
        } else {
            // Insert new student record
            $stmt = $con->prepare("INSERT INTO tbl_student (StudentNumber, LastName, FirstName, MiddleName, Gender, Course_Id, Status, AcademicYr_Id, Semester_Id, Category_Id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssisiii", $studentNumber, $lastName, $firstName, $middleName, $gender, $courseId, $status, $academicYrId, $semesterId, $categoryId);

            if ($stmt->execute()) {
                // Insert default status "PENDING" into tbl_record_status
                $recordStmt = $con->prepare("INSERT INTO tbl_record_status (StudentNumber, record_status) VALUES (?, 'INCOMPLETE')");
                $recordStmt->bind_param("s", $studentNumber);
                $recordStmt->execute();
                $recordStmt->close();

                $successMessage = "Student record added successfully.";
                $success = true;
            } else {
                $errors[] = "Error inserting data: " . $stmt->error;
            }

            $stmt->close();
        }
    }
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
    <link href="css/all.min.css" rel="stylesheet" />
    <link href="css/33styles.css" rel="stylesheet" />
    <link rel="shortcut icon" href="images/ccticon.png">

    <script src="js/alert.js"></script>
</head>
<body class="sb-nav-fixed">

    <div >
    <?php include 'topnav.php'; ?>
        </div>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'sidebar.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <?php include 'addingexcel.php'; ?>
                </div>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-7">
                            <div class="card shadow-lg border-0 rounded-lg mt-5">
                                <div class="card-body">
                                    <form method="POST" id="studentForm" action="addingofstudents.php" onsubmit="return validateFormAndConfirm();">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="studentNumber" name="StudentNumber" type="text" oninput="validateStudentNumber(this)" pattern="\d*" title="Please enter numbers only" required />
                                            <label for="studentNumber">Student Number</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="lastName" name="LastName" type="text" oninput="validateName(this);" required />
                                            <label for="lastName">Last Name</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="firstName" name="FirstName" type="text" oninput="validateName(this);" required />
                                            <label for="firstName">First Name</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="middleName" name="MiddleName" type="text" oninput="validateName(this);" />
                                            <label for="middleName">Middle Name (optional)</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <select class="form-select" id="gender" name="Gender" required>
                                                <option value="" selected>Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                            <label for="gender">Gender</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <select class="form-select" id="course" name="Course_id" required>
                                                <option value="">Select Course</option>
                                                <?php
                                                $courses = getCourses();
                                                foreach ($courses as $course) {
                                                    echo '<option value="' . $course['course_id'] . '">' . $course['course_name'] . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <label for="course">Course</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <select class="form-select" id="status" name="Status" required>
                                                <option value="">Select Status</option>
                                                <option value="New Student" <?php echo isset($_POST['Status']) && $_POST['Status'] == 'New Student' ? 'selected' : ''; ?>>New Student</option>
                                                <option value="Transferee" <?php echo isset($_POST['Status']) && $_POST['Status'] == 'Transferee' ? 'selected' : ''; ?>>Transferee</option>
                                            </select>
                                            <label for="status">Status</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <select class="form-select" id="academicYr" name="AcademicYr_id" required>
                                                <option value="">Select Academic Year</option>
                                                <?php
                                                $academicYears = getAcademicYears();
                                                foreach ($academicYears as $academicYear) {
                                                    echo '<option value="' . $academicYear['academicYr_id'] . '">' . $academicYear['Academic_Year'] . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <label for="academicYr">Academic Year</label>
                                        </div>

                                        <div class="form-floating mb-3">
                                            <select class="form-select" id="semester" name="Semester_id" required>
                                                <option value="">Select Semester</option>
                                                <?php
                                                $semesters = getSemesters();
                                                foreach ($semesters as $semester) {
                                                    echo '<option value="' . $semester['semester_id'] . '">' . $semester['semester_name'] . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <label for="semester">Semester</label>
                                        </div>

                                        <div class="mt-4 mb-0">
                                            <button class="btn btn-primary" type="submit">Add Student</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <div>
                <br><br><br><br><br>
                <?php include 'footer.php'; ?>
                </div>
        </div>
    </div>
    <script>
    // Function to confirm "Add Student" action with SweetAlert
    function validateFormAndConfirm(event) {
        event.preventDefault(); // Prevent immediate form submission

        // Show the confirmation prompt first
        Swal.fire({
            title: 'Are you sure you want to add this student?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, add student',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // If confirmed, validate the form
                if (isFormValid()) {
                    // Directly submit the form without showing success or error alerts
                    document.getElementById('studentForm').submit();
                } else {
                    // If form is invalid, show an error alert
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please complete all required fields correctly.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            }
        });
    }

    // Function to check form validity
    function isFormValid() {
        const studentNumber = document.getElementById("studentNumber").value;
        const lastName = document.getElementById("lastName").value;
        const firstName = document.getElementById("firstName").value;

        // Check middleName separately to allow blanks
        const middleName = document.getElementById("middleName").value;

        // Ensure all required fields except middleName are filled
        return studentNumber && lastName && firstName;
    }

    // Attach validateFormAndConfirm to the form's submit event
    document.getElementById('studentForm').addEventListener('submit', validateFormAndConfirm);
</script>

<script>
    // Function to show/hide the new academic year input field
    document.getElementById('academicYr').addEventListener('change', function() {
        var newAcademicYearDiv = document.getElementById('newAcademicYearDiv');
        if (this.value === 'new') {
            newAcademicYearDiv.style.display = 'block';
        } else {
            newAcademicYearDiv.style.display = 'none';
        }
    });

    // JavaScript function to handle form submission with SweetAlert confirmation
    function validateFormAndConfirm() {
        var studentNumber = document.getElementById("studentNumber").value;
        var lastName = document.getElementById("lastName").value;
        var firstName = document.getElementById("firstName").value;
        var middleName = document.getElementById("middleName").value;

        // Ensure required fields are filled except middleName
        if (!studentNumber || !lastName || !firstName) {
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Information',
                text: 'Please complete all required fields.'
            });
            return false; // Prevent form submission
        }

        // SweetAlert confirmation for form submission
        return Swal.fire({
            title: 'Confirm Submission',
            text: 'Are you sure you want to add this student?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, add student',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            return result.isConfirmed; // Submit form if confirmed
        });
    }

    // Function to validate name fields to only contain letters
    function validateName(input) {
        var pattern = /^[a-zA-Z\s]*$/;
        if (!pattern.test(input.value)) {
            input.setCustomValidity('Please enter a valid name (letters only).');
            input.classList.add('is-invalid');
        } else {
            input.setCustomValidity('');
            input.classList.remove('is-invalid');
        }
    }

    // Function to validate student number field to only contain digits
    function validateStudentNumber(input) {
        var pattern = /^\d+$/;
        if (!pattern.test(input.value)) {
            input.setCustomValidity('Please enter a valid student number (digits only).');
            input.classList.add('is-invalid');
        } else {
            input.setCustomValidity('');
            input.classList.remove('is-invalid');
        }
    }

    // SweetAlert confirmation for logout
    document.getElementById('logoutBtn').addEventListener('click', function(event) {
        event.preventDefault(); // Prevent immediate navigation
        Swal.fire({
            title: 'Are you sure you want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, logout'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php'; // Redirect to logout.php to destroy the session
            }
        });
    });

    // CSRF token fetch if necessary
    document.addEventListener('DOMContentLoaded', function() {
        fetch('/csrf-token')
            .then(response => response.json())
            .then(data => {
                document.querySelector('input[name="_token"]').value = data.csrfToken;
            });
    });

    // Function to show/hide the new course input field
    document.getElementById('course').addEventListener('change', function() {
        var newCourseDiv = document.getElementById('newCourseDiv');
        if (this.value === 'new') {
            newCourseDiv.style.display = 'block';
        } else {
            newCourseDiv.style.display = 'none';
        }
    });
</script>
<!-- Bootstrap -->
<script src="js/bootsrap.js" defer></script>

<!-- SweetAlert2 -->
<script src="js/sweetalert.js" defer></script>
<!-- Custom Scripts -->
<script src="js/scripts.js" defer></script>
    </body>
</html>
