<?php
// Prevent unauthorized access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'System Admin') {
    header("Location: index.php");
    exit();
}

// Initialize error and success messages
$errorMessages = [];
$successMessage = '';

// Include the database connection file
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

// Function to validate and add a new course
function addNewCourse($courseName, $adminPassword) {
    global $errorMessages, $successMessage;

    // Convert input to uppercase
    $courseName = strtoupper($courseName);

    // Validate input (acronym format: BSIT, BSTM, BSE-MATH, etc.)
    if (!preg_match("/^[A-Z]{2,4}(-[A-Z]{2,4})?$/", $courseName)) {
        $errorMessages[] = "Course name must be in the format of an acronym (e.g., BSIT, BSTM, BSE-MATH).";
        return;
    }

    // Validate admin password
    if (!validateAdminPassword($adminPassword)) {
        $errorMessages[] = "Invalid admin password.";
        return;
    }

    $con = getDbConnection();

    // Check if the course already exists
    $stmt = $con->prepare("SELECT course_id FROM tbl_course WHERE course_name = ?");
    $stmt->bind_param("s", $courseName);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errorMessages[] = "Course '$courseName' already exists.";
        $stmt->close();
        $con->close();
        return;
    }
    $stmt->close();

    // Insert course into the database
    $stmt = $con->prepare("INSERT INTO tbl_course (course_name) VALUES (?)");
    $stmt->bind_param("s", $courseName);
    if ($stmt->execute()) {
        $successMessage = "New course '$courseName' added successfully!";
    } else {
        $errorMessages[] = "Error adding course: " . $stmt->error;
    }
    $stmt->close();
    $con->close();
}

// Function to validate and add a new academic year
function addNewAcademicYear($academicYear, $adminPassword) {
    global $errorMessages, $successMessage;

    // Convert input to uppercase
    $academicYear = strtoupper($academicYear);

    // Validate input (format yyyy-yyyy)
    if (!preg_match("/^\d{4}-\d{4}$/", $academicYear)) {
        $errorMessages[] = "Academic Year must be in the format yyyy-yyyy.";
        return;
    }

    // Validate admin password
    if (!validateAdminPassword($adminPassword)) {
        $errorMessages[] = "Invalid admin password.";
        return;
    }

    $con = getDbConnection();

    // Check if the academic year already exists
    $stmt = $con->prepare("SELECT academicYr_id FROM tbl_academicyr WHERE Academic_Year = ?");
    $stmt->bind_param("s", $academicYear);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errorMessages[] = "Academic Year '$academicYear' already exists.";
        $stmt->close();
        $con->close();
        return;
    }
    $stmt->close();

    // Insert academic year into the database
    $stmt = $con->prepare("INSERT INTO tbl_academicyr (Academic_Year) VALUES (?)");
    $stmt->bind_param("s", $academicYear);
    if ($stmt->execute()) {
        $successMessage = "New academic year '$academicYear' added successfully!";
    } else {
        $errorMessages[] = "Error adding academic year: " . $stmt->error;
    }
    $stmt->close();
    $con->close();
}

// Function to validate admin password
function validateAdminPassword($password) {
    $con = getDbConnection();

    // Fetch the hashed password of the system admin
    $stmt = $con->prepare("SELECT Password FROM tbl_user WHERE Role = 'System Admin'");
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();
    $con->close();

    // Verify the password
    return password_verify($password, $hashedPassword);
}

// Handle form submission for "Add New Course"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $courseName = trim($_POST['course_name']);
    $adminPassword = trim($_POST['admin_password']);
    addNewCourse($courseName, $adminPassword);
}

// Handle form submission for "Add New Academic Year"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_academic_year'])) {
    $academicYear = trim($_POST['academic_year']);
    $adminPassword = trim($_POST['admin_password']);
    addNewAcademicYear($academicYear, $adminPassword);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Course</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .error-input {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }
    </style>
</head>
<body>
    <!-- Modal for Action Selection -->
    <div class="modal fade" id="actionSelectionModal" tabindex="-1" aria-labelledby="actionSelectionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionSelectionModalLabel">Choose Action</h5>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                            Add New Course
                        </button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAcademicYearModal">
                            Add New Academic Year
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="courseForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCourseModalLabel">Add New Course</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="courseInput" class="form-label">Course Name</label>
                            <input type="text" class="form-control" id="courseInput" name="course_name" placeholder="Enter new course (e.g., BSIT, BSTM, BSE-MATH)" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminPassword" class="form-label">Admin Password</label>
                            <input type="password" class="form-control" id="adminPassword" name="admin_password" placeholder="Enter admin password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="add_course">Add Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add New Academic Year Modal -->
    <div class="modal fade" id="addAcademicYearModal" tabindex="-1" aria-labelledby="addAcademicYearModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="academicYearForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAcademicYearModalLabel">Add New Academic Year</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="academicYearInput" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" id="academicYearInput" name="academic_year" placeholder="Enter academic year (e.g., 2023-2024)" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminPassword" class="form-label">Admin Password</label>
                            <input type="password" class="form-control" id="adminPassword" name="admin_password" placeholder="Enter admin password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" name="add_academic_year">Add Academic Year</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert Notifications -->
    <?php if (!empty($successMessage)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo addslashes($successMessage); ?>',
                    showConfirmButton: false,
                    timer: 3000
                });
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($errorMessages)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    html: '<?php echo implode("<br>", array_map("addslashes", $errorMessages)); ?>',
                    showConfirmButton: true
                });

                // Highlight invalid inputs
                <?php if (in_array("Course name must be in the format of an acronym (e.g., BSIT, BSTM, BSE-MATH).", $errorMessages)): ?>
                    document.getElementById('courseInput').classList.add('error-input');
                <?php endif; ?>

                <?php if (in_array("Academic Year must be in the format yyyy-yyyy.", $errorMessages)): ?>
                    document.getElementById('academicYearInput').classList.add('error-input');
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>
</body>
</html>