<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] !== 'Staff') {
    header("Location: index.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
global $conn;

// Query to get the most recent academic year with archived students
$currentYearQuery = "
    SELECT ay.Academic_Year 
    FROM tbl_archive AS a
    JOIN tbl_academicyr AS ay ON a.AcademicYr_Id = ay.AcademicYr_Id
    ORDER BY ay.AcademicYr_Id DESC 
    LIMIT 1
";
$currentYearResult = $conn->query($currentYearQuery);
$currentAcademicYear = ($currentYearResult->num_rows > 0) ? $currentYearResult->fetch_assoc()['Academic_Year'] : "No Archived Students";
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
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
        }
        .container {
            margin-top: 50px;
        }
        .card {
            border: none;
            border-radius: 15px;
            background: linear-gradient(145deg, #ffffff, #f1f3f6);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.6);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15), inset 0 2px 0 rgba(255, 255, 255, 0.7);
        }
        .card-body {
            padding: 30px 20px;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 15px;
            color: #343a40;
        }
        .folder-icon {
            font-size: 4rem;
        }
        .folder-archived {
            color: #ffc107; /* Yellow for Archived */
        }
        .folder-current {
            color: #007bff; /* Blue for Current Year */
        }
        .header-title {
            font-weight: bold;
            font-size: 2rem;
        }
        .header-subtitle {
            font-size: 1rem;
            color: #6c757d;
        }
        .card-description {
            font-size: 0.875rem;
            color: #6c757d;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <div>
        <?php include 'topnav.php'; ?>
    </div>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'sidebar.php'; ?>
        </div>
        <div id="layoutSidenav_content">
        <main>
        <div class="container text-center">
        <h1 class="header-title">Archive Documents</h1>
        <p class="header-subtitle">Manage and view archived documents for students.</p>
        <div class="row justify-content-center mt-5">
            <!-- Archived Folder -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <a href="archive_list.php" class="text-decoration-none">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-archive folder-icon folder-archived"></i>
                            <h5 class="card-title">Archived</h5>
                            <p class="card-description">View all archived records organized by academic years.</p>
                        </div>
                    </div>
                </a>
            </div>
            <!-- Current Year Archived Folder -->
            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                <a href="archive_current.php" class="text-decoration-none">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-calendar-alt folder-icon folder-current"></i>
                            <h5 class="card-title">In-Progress Year Archived</h5>
                            <p class="card-description">Academic Year: <?php echo $currentAcademicYear; ?></p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
        </main>
        <div>
                <?php include 'footer.php'; ?>
                </div>
        </div>
    </div>
    
<!-- button -->
<script src="js/jquery-3.6.0.min.js" ></script>

<script src="js/datatables-simple-demo.js"></script>

    <!-- SweetAlert2 -->
<script src="js/sweetalert.js" defer></script>

<!-- Bootstrap -->
<script src="js/bootsrap.js" defer></script>

<!-- Simple Datatables -->
<script src="js/tables.js" defer></script>

<!-- Custom Scripts -->
<script src="js/scripts.js" defer></script>

</body>
</html>
