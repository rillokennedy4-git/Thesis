<?php
include_once("connections/connection.php");
$con = getDbConnection();

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

// Function to get the next academic year ID
function getNextAcademicYearId() {
    $con = getDbConnection();
    $sql = "SELECT MAX(academicYr_id) AS max_id FROM tbl_academicyr";
    $result = $con->query($sql);
    $maxId = 0;
    
    if ($row = $result->fetch_assoc()) {
        $maxId = $row['max_id'];
    }
    
    $con->close();
    return $maxId + 1;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $studentNumber = $_POST['StudentNumber'];
    $lastName = $_POST['LastName'];
    $firstName = $_POST['FirstName'];
    $middleName = $_POST['MiddleName'];
    $gender = $_POST['Gender'];
    $courseId = $_POST['Course_id'];
    $status = $_POST['Status']; // This will be captured from the dropdown
    $academicYrId = $_POST['AcademicYr_id'];
    $academicYr = $_POST['AcademicYr'];
    $semesterId = $_POST['Semester_id'];

    // Default Category ID
    $categoryId = 1;

    // Validate Status field
    if ($status !== "Regular" && $status !== "Irregular") {
        $error = "Invalid status. Please select either Regular or Irregular.";
    }

    // Create database connection
    $con = getDbConnection();

    // Check if a new academic year was added
    if (!$error && $academicYr && $academicYrId == 'new') {
        // Check if the academic year already exists
        $stmt = $con->prepare("SELECT academicYr_id FROM tbl_academicyr WHERE Academic_Year = ?");
        $stmt->bind_param("s", $academicYr);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 0) {
            $academicYrId = getNextAcademicYearId();
            $stmt = $con->prepare("INSERT INTO tbl_academicyr (academicYr_id, Academic_Year) VALUES (?, ?)");
            $stmt->bind_param("is", $academicYrId, $academicYr);
            $stmt->execute();
        } else {
            $error = "The academic year already exists. Please select it from the dropdown.";
        }
        
        $stmt->close();
    }

    if (!$error) {
        // Prepare SQL statement with correct table name
        $stmt = $con->prepare("INSERT INTO tbl_student (StudentNumber, LastName, FirstName, MiddleName, Gender, Course_id, Status, AcademicYr_id, Semester_id, Category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt === false) {
            die("Error preparing query: " . $con->error);
        }

        // Bind parameters (ensure Status is treated as a string)
        $stmt->bind_param("sssssiisii", $studentNumber, $lastName, $firstName, $middleName, $gender, $courseId, $status, $academicYrId, $semesterId, $categoryId);

        // Execute statement
        if ($stmt->execute()) {
            echo "<script>alert('Record added successfully!');</script>";
            echo "<script>document.getElementById('studentForm').reset();</script>"; // Reset the form
        } else {
            echo "Error adding record: " . $stmt->error;
        }

        // Close statement and connection
        $stmt->close();
        $con->close();
    }
}
?>

<!-- HTML form to add student data -->
<form method="POST" id="studentForm">
    Student Number: <input type="text" name="StudentNumber" required><br>
    Last Name: <input type="text" name="LastName" required><br>
    First Name: <input type="text" name="FirstName" required><br>
    Middle Name: <input type="text" name="MiddleName"><br>

    <!-- Gender dropdown -->
    Gender: 
    <select name="Gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
    </select><br>

    <!-- Course dropdown populated from tbl_course -->
    Course: 
    <select name="Course_id" required>
        <option value="">Select Course</option>
        <?php
        $courses = getCourses();
        foreach ($courses as $course) {
            echo "<option value=\"{$course['course_id']}\">{$course['course_name']}</option>";
        }
        ?>
    </select><br>

    <!-- Status dropdown -->
    Status: 
    <select name="Status" required>
        <option value="">Select Status</option>
        <option value="Regular">Regular</option>
        <option value="Irregular">Irregular</option>
    </select><br>

    <!-- Academic Year -->
    Academic Year: 
    <div id="academicYearContainer">
        <select name="AcademicYr_id" id="academicYr_id" required>
            <option value="">Select Academic Year</option>
            <?php
            $academicYears = getAcademicYears();
            foreach ($academicYears as $year) {
                echo "<option value=\"{$year['academicYr_id']}\">{$year['Academic_Year']}</option>";
            }
            ?>
            <option value="new">Add New Academic Year</option>
        </select>
        <input type="text" name="AcademicYr" id="academicYr" placeholder="Enter new academic year" style="display:none;" pattern="[0-9\-]*" title="Please enter only numbers and hyphens (e.g., 2024-2025)">
    </div>
    <br>

    <!-- Semester -->
    Semester: 
    <select name="Semester_id" id="semester_id" required>
        <option value="">Select Semester</option>
        <?php
        $semesters = getSemesters();
        foreach ($semesters as $semester) {
            echo "<option value=\"{$semester['semester_id']}\">{$semester['semester_name']}</option>";
        }
        ?>
    </select><br>

    <?php
    if ($error) {
        echo "<p style='color: red;'>$error</p>";
    }
    ?>
    
    <input type="submit" value="Add Student">
</form>

<script>
// Script to handle adding a new academic year dynamically
document.getElementById('academicYr_id').addEventListener('change', function() {
    var selectedValue = this.value;
    var newYearInput = document.getElementById('academicYr');
    var container = document.getElementById('academicYearContainer');
    
    if (selectedValue === 'new') {
        newYearInput.style.display = 'inline';
        newYearInput.required = true;
        container.querySelector('select').style.display = 'none';
    } else {
        newYearInput.style.display = 'none';
        newYearInput.required = false;
        container.querySelector('select').style.display = 'inline';
    }
});
</script>
