<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Check if the user is logged in; if not, redirect to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Optional: Enforce role-based access control
if ($_SESSION['role'] !== 'System Admin') {
    header("Location: index.php");
    exit();
}

// Check and display success message
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear success message
} else {
    $successMessage = null;
}

// Include the database connection file with the correct path
include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

// Fetch data from the database for Students Per Year
function getStudentsPerYear() {
    $con = getDbConnection();
    $query = "
        SELECT tbl_academicyr.Academic_Year AS year, 
               COUNT(tbl_student.Student_Id) AS student_count, 
               tbl_academicyr.AcademicYr_Id AS year_id
        FROM tbl_academicyr
        LEFT JOIN tbl_student ON tbl_student.AcademicYr_Id = tbl_academicyr.AcademicYr_Id
        GROUP BY tbl_academicyr.Academic_Year, tbl_academicyr.AcademicYr_Id
    ";
    $result = $con->query($query);

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $con->close();
    return $data;
}

// Fetch the most recent and second-most recent academic years from the database
function getCurrentAndPreviousYears() {
    $con = getDbConnection();
    $query = "
        SELECT Academic_Year
        FROM tbl_academicyr
        ORDER BY AcademicYr_Id DESC
        LIMIT 2
    ";
    $result = $con->query($query);

    $years = [];
    while ($row = $result->fetch_assoc()) {
        $years[] = $row['Academic_Year'];
    }

    $con->close();
    return $years;
}

// Get the current and previous years
$recentYears = getCurrentAndPreviousYears();
$currentYear = $recentYears[0] ?? null; // Most recent academic year
$previousYear = $recentYears[1] ?? null; // Second most recent academic year

// Fetch data for Students Per Status by Year
function getStudentsPerStatusByYear($year_id = null) {
    $con = getDbConnection();
    $query = "
        SELECT tbl_student.Status AS status, 
               COUNT(CASE WHEN ? IS NULL OR tbl_student.AcademicYr_Id = ? THEN tbl_student.Student_Id END) AS student_count
        FROM tbl_student
        GROUP BY tbl_student.Status
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $year_id, $year_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    $con->close();
    return $data;
}

// Fetch data for Students Per Record Status by Year
function getStudentsPerRecordStatusByYear($year_id = null) {
    $con = getDbConnection();
    $query = "
        SELECT tbl_record_status.record_status AS status, 
               COUNT(CASE WHEN ? IS NULL OR tbl_student.AcademicYr_Id = ? THEN tbl_student.Student_Id END) AS student_count
        FROM tbl_record_status
        LEFT JOIN tbl_student ON tbl_record_status.StudentNumber = tbl_student.StudentNumber
        GROUP BY tbl_record_status.record_status
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $year_id, $year_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    $con->close();
    return $data;
}

// Fetch data for dropdowns
$studentsPerYear = getStudentsPerYear();
$studentsPerStatusAllYears = getStudentsPerStatusByYear();
$studentsPerRecordStatusAllYears = getStudentsPerRecordStatusByYear();

// Handle AJAX requests
if (isset($_GET['year_id'])) {
    $year_id = intval($_GET['year_id']);
    $year_id = $year_id === 0 ? null : $year_id;

    $chart = $_GET['chart'] ?? '';
    $data = ($chart === 'record_status')
        ? getStudentsPerRecordStatusByYear($year_id)
        : getStudentsPerStatusByYear($year_id);

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
    <head>
    <title>Archiving System</title>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <link href="css/33styles.css" rel="stylesheet" />
        <link href="css/all.min.css" rel="stylesheet" />
        <link rel="shortcut icon" href="images/ccticon.png">
        <style>
            .chart-container {
                width: 100%;
                height: 300px;
            }
            .chart-container1 {
                width: 100%;
                height: 250px;
            }
            .navbar h1.navbar-brand {
                font-size: 20px; /* Increase font size */
                font-weight: bold; /* Make text bold */
                text-align: center; /* Center-align text */
                color: #fff; /* Set the text color to white */
                margin: 0 auto; /* Center the element within the navbar */
                line-height: 1.5; /* Adjust line height for readability */
                padding: 10px 0; /* Add some padding around the text */
                flex: 1; /* Allow the h1 to take up remaining space for centering */
                }

                .navbar {
                    display: flex; /* Use flexbox for navbar layout */
                    flex-wrap: wrap; /* Allow content to wrap */
                    align-items: center; /* Vertically align items */
                    justify-content: space-between; /* Space out items */
                }
       
        </style>
        
    </head>
<body class="sb-nav-fixed">
    <div>
        <?php include 'topnav.php'; ?>
    </div>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'sidebar1.php'; ?>
        </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Dashboard</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                        <div class="row">
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-primary text-white mb-4">
                                    <div class="card-body">My profile</div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="profile.php">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
    
                            <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4" id="actionCard" style="cursor: pointer;">
                                <div class="card-body">Add Course/Academic Year <br></div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link">View Details</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                            </div>
                            <?php include 'addcourse&academicyr.php'; ?>

                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-warning text-white mb-4">
                                    <div class="card-body">Users</div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="users.php">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card bg-danger text-white mb-4">
                                    <div class="card-body">Archives</div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a class="small text-white stretched-link" href="SA_archived.php">View Details</a>
                                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4"></h1>


 <!-- Row 1: Area Chart (Students Per Year) -->
 <div class="col-md-12">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-area me-1"></i> Current and Previous Year
                                    <select id="areaYearDropdown" class="form-select" style="width: auto; display: inline-block; margin-left: 10px;">
                                        <option value="0">Default</option>
                                        <?php foreach ($studentsPerYear as $year): ?>
                                            <option value="<?php echo $year['year']; ?>"><?php echo $year['year']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="card-body chart-container">
                                    <canvas id="myAreaChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Bar and Pie Charts (Side by Side) -->
                        <div class="row">
                            <!-- Bar Chart -->
                            <?php include 'barchart.php'; ?>

                            <!-- Pie Chart -->
                            <?php include 'piechart.php'; ?>
                        </div>
                </main>
                <div>
                <?php include 'footer.php'; ?>
                </div>
                
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                fetch('/csrf-token')
                .then(response => response.json())
                .then(data => {
                document.querySelector('input[name="_token"]').value = data.csrfToken;
               });
            });
        </script>



<script>
    const currentYear = <?php echo json_encode($currentYear); ?>;
    const previousYear = <?php echo json_encode($previousYear); ?>;
    const studentsPerYear = <?php echo json_encode($studentsPerYear); ?>;
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctxArea = document.getElementById("myAreaChart").getContext("2d");
        let areaChart;

        // Helper function to get data for the current academic year and the selected previous academic year
        const getComparisonData = (students, selectedPreviousYear = previousYear) => {
            const currentYearData = students.find(item => item.year === currentYear) || { year: currentYear, student_count: 0 };
            const previousYearData = students.find(item => item.year === selectedPreviousYear) || { year: selectedPreviousYear, student_count: 0 };

            return {
                labels: [previousYearData.year, currentYearData.year],
                datasets: [
                    {
                        label: `Students (${previousYearData.year})`,
                        data: [previousYearData.student_count, 0],
                        borderColor: "rgba(255, 99, 132, 1)", 
                        backgroundColor: "rgba(255, 99, 132, 0.2)", 
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: `Students (${currentYearData.year})`,
                        data: [0, currentYearData.student_count],
                        borderColor: "rgba(54, 162, 235, 1)", 
                        backgroundColor: "rgba(54, 162, 235, 0.2)", 
                        fill: true,
                        tension: 0.4
                    }
                ]
            };
        };

        // Function to update the Area Chart
        const updateAreaChart = (chart, ctx, data) => {
            if (chart) chart.destroy();

            return new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                align: 'center', 
                                crossAlign: 'center', 
                                padding: 10 
                            },
                            title: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: "Number of Students" }
                        }
                    }
                }
            });
        };

        // Initialize chart with default data
        const initialData = getComparisonData(<?php echo json_encode($studentsPerYear); ?>);
        areaChart = updateAreaChart(null, ctxArea, initialData);

        // Dropdown change event listener
        document.getElementById('areaYearDropdown').addEventListener('change', function () {
            const selectedYear = this.value || previousYear;
            const updatedData = getComparisonData(<?php echo json_encode($studentsPerYear); ?>, selectedYear);
            areaChart = updateAreaChart(areaChart, ctxArea, updatedData);
        });
    });
</script>
<script>
        // JavaScript to handle the card click event
        document.getElementById('actionCard').addEventListener('click', function() {
            // Open the action selection modal
            var actionSelectionModal = new bootstrap.Modal(document.getElementById('actionSelectionModal'));
            actionSelectionModal.show();
        });
    </script>


<!-- SweetAlert2 -->
<script src="js/sweetalert.js" defer></script>

<!-- Bootstrap -->
<script src="js/bootsrap.js" defer></script>

<!-- Chart.js -->
<script src="js/chart.js" defer></script>

<!-- Simple Datatables -->
<script src="js/tables.js" defer></script>

<!-- Custom Scripts -->
<script src="js/scripts.js" defer></script>
<!-- Custom Scripts -->
<script>        // Disable Alt + Arrow key combination (Go Back and Go Forward)
        document.addEventListener('keydown', function(event) {
            // Check if the Alt key is pressed along with the Left or Right Arrow key
            if (event.altKey && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
                // Prevent the default behavior (navigation)
                event.preventDefault();
                console.log('Alt + Arrow key combination disabled.');
            }
        });

        // Disable history navigation using popstate event
        window.addEventListener('popstate', function(event) {
            // Prevent the default behavior (navigation)
            event.preventDefault();
            console.log('History navigation disabled.');
        });</script>

    
    </body>
</html>
