<?php
// charts.php

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
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Archiving System</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="shortcut icon" href="images/ccticon.png">
    <style>
        .chart-container {
            width: 100%;
            height: 400px;
        }
    </style>
</head>
<body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="staff.php">Welcome, Staff!</a>
            
            <!-- Navbar Search-->
            <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
                
            </form>
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="#" id="logoutBtn">Log Out</a></li>
                        

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                fetch('/csrf-token')
                                    .then(response => response.json())
                                    .then(data => {
                                        document.querySelector('input[name="_token"]').value = data.csrfToken;
                                    });
                            });
                        </script>

                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 script -->

                    </ul>
                </li>
            </ul>
        </nav>

        <script>
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
</script>



        
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <div class="sb-sidenav-menu-heading">Core</div>
                            <a class="nav-link" href="staff.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            


                            <div class="sb-sidenav-menu-heading">Documents</div>
                
                            
                            
                        


                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseStudents" aria-expanded="false" aria-controls="collapseStudents">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Students
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseStudents" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="allstudents.php">List of All Students</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapseStudents" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="addingofstudents.php">Upload Student</a>
                                </nav>
                            </div>




                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapse201" aria-expanded="false" aria-controls="collapse201">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                201 Files
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapse201" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/201upload.php">Upload 201 Files</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapse201" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/COA.php">COA</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapse201" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/form137.php">Form 137</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapse201" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/form138.php">Form 138</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapse201" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/GoodMoral.php">Good Moral</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapse201" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/2x2picture.php">2x2 Picture</a>
                                </nav>
                            </div>
                            
                            

                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTransferees" aria-expanded="false" aria-controls="collapseTransferees">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Transferees
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseTransferees" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/transfereesupload.php">Upload Transferees Files</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapseTransferees" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/T_COA.php">COA</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapseTransferees" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href=section_staff/T_TOR.php">TOR</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapseTransferees" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/T_TransferCredentials.php">Transfer Credentials</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapseTransferees" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/T_GoodMoral.php">Good Moral</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapseTransferees" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/T_2x2picture.php">2x2 Picture</a>
                                </nav>
                            </div>
                            <div class="collapse" id="collapseTransferees" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="section_staff/T_ApplicationAdmission.php">Application Admission</a>
                                </nav>
                            </div>

                                
                               
                           
                           
                            
                            
                            <div class="sb-sidenav-menu-heading">Records</div>
                            <a class="nav-link" href="archived.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Archives
                             </a>
                        


                            <div class="sb-sidenav-menu-heading">Monitoring</div>
                            <a class="nav-link" href="history.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                History
                            </a>

                            
                            

                            
                          

                            
                            <div class="sb-sidenav-menu-heading">Reports</div>
                            <a class="nav-link" href="charts.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Charts
                            </a>



                            <a class="nav-link" href="report.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Generate Report
                            </a>


                            
                            
                        
                        
                        
                        
                        
                        
                        
                        </div>
                    </div>
                   
                </nav>
            </div>
            
            
            
            <div id="layoutSidenav_content">
<div id="layoutAuthentication">
            <div id="layoutAuthentication_content">
                <main>
                    <div class="container-fluid px-4">
            <h1 class="mt-4">Charts</h1>

            <!-- Row 1: Area Chart (Students Per Year) -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-chart-area me-1"></i> Students Per Year
                        </div>
                        <div class="card-body chart-container">
                            <canvas id="myAreaChart"></canvas>
                        </div>
                        <div class="card-footer text-muted">
                            Updated yesterday at 11:59 PM
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: Bar and Pie Charts (Side by Side) -->
            <div class="row">
                <!-- Bar Chart -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-chart-bar me-1"></i> Students Per Record Status
                            <select id="recordStatusYearDropdown" class="form-select" style="width: auto; display: inline-block;">
                                <option value="0">All Years</option>
                                <?php foreach ($studentsPerYear as $year): ?>
                                    <option value="<?php echo $year['year_id']; ?>"><?php echo $year['year']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="card-body chart-container">
                            <canvas id="recordStatusBarChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Pie Chart -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-chart-pie me-1"></i> Students Per Status
                            <select id="statusYearDropdown" class="form-select" style="width: auto; display: inline-block;">
                                <option value="0">All Years</option>
                                <?php foreach ($studentsPerYear as $year): ?>
                                    <option value="<?php echo $year['year_id']; ?>"><?php echo $year['year']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="card-body chart-container">
                            <canvas id="myPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const studentsPerYear = <?php echo json_encode($studentsPerYear); ?>;
            const ctxArea = document.getElementById("myAreaChart").getContext("2d");

           // Area Chart Initialization with styling to match your design
    new Chart(ctxArea, {
        type: 'line',
        data: {
            labels: studentsPerYear.map(item => item.year),
            datasets: [{
                label: "Students",
                data: studentsPerYear.map(item => item.student_count),
                fill: true,
                borderColor: "rgba(2,117,216,1)", // Line color
                backgroundColor: "rgba(2,117,216,0.2)", // Filled area color
                tension: 0.4, // Smoother curves
                pointRadius: 5, // Bigger points
                pointBackgroundColor: "rgba(2,117,216,1)",
                pointBorderColor: "#ffffff",
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Hides legend to match the cleaner design
                }
            },
            scales: {
                x: {
                    grid: { display: false }, // Removes vertical grid lines
                    title: {
                        display: true,
                        text: "Year"
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: "Number of Students"
                    }
                }
            }
        }
            });

            const ctxPie = document.getElementById('myPieChart').getContext('2d');
            let studentsStatusChart;

            function updateChart(chart, ctx, data, type) {
                if (chart) chart.destroy();

                const labels = data.map(item => item.status);
                const chartData = data.map(item => item.student_count);
                const colors = labels.map((_, i) => `hsl(${i * 360 / labels.length}, 70%, 60%)`);

                return new Chart(ctx, {
                    type: type,
                    data: {
                        labels: labels,
                        datasets: [{ data: chartData, backgroundColor: colors }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }

            studentsStatusChart = updateChart(null, ctxPie, <?php echo json_encode($studentsPerStatusAllYears); ?>, 'pie');

            document.getElementById('statusYearDropdown').addEventListener('change', function () {
                fetch(`?year_id=${this.value}`).then(res => res.json()).then(data => {
                    studentsStatusChart = updateChart(studentsStatusChart, ctxPie, data, 'pie');
                });
            });

            const ctxBar = document.getElementById('recordStatusBarChart').getContext('2d');
            let recordStatusBarChart = updateChart(null, ctxBar, <?php echo json_encode($studentsPerRecordStatusAllYears); ?>, 'bar');

            document.getElementById('recordStatusYearDropdown').addEventListener('change', function () {
                fetch(`?year_id=${this.value}&chart=record_status`).then(res => res.json()).then(data => {
                    recordStatusBarChart = updateChart(recordStatusBarChart, ctxBar, data, 'bar');
                });
            });
        });
    </script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
</body>

</html>
<style>
        .chart-container {
            width: 100%;
            height: 400px;
        }
    </style>