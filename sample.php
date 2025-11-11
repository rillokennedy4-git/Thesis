<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Archives - SB Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="staff.php">Welcome, Staff!</a>
    </nav>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <!-- Sidebar as in staff.php -->
        </div>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Archived Students by Academic Year</h1>
                    <div class="row">
                        <?php
                        include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
                        global $conn;

                        // Query to get distinct academic years with archived students
                        $yearQuery = "SELECT DISTINCT AcademicYr_Id, Academic_Year FROM tbl_academicyr ORDER BY Academic_Year ASC";
                        $yearResults = $conn->query($yearQuery);

                        if ($yearResults->num_rows > 0) {
                            $counter = 0;
                            while ($row = $yearResults->fetch_assoc()) {
                                if ($counter % 5 === 0 && $counter !== 0) {
                                    // Close row and start a new one every 5 items
                                    echo '</div><div class="row">';
                                }
                                $academicYear = $row['Academic_Year'];
                                $academicYearId = $row['AcademicYr_Id'];
                                ?>
                                
                                <div class="col-lg-2 col-md-4 col-sm-6 mb-4">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <i class="fas fa-folder fa-3x text-warning"></i>
                                            <h5 class="card-title mt-2"><?php echo $academicYear; ?></h5>
                                        </div>
                                        <div class="card-footer">
                                            <a href="archived_students.php?academicYearId=<?php echo $academicYearId; ?>" class="stretched-link text-decoration-none">View Students</a>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php
                                $counter++;
                            }
                        } else {
                            echo "<p>No archived students found for any academic year.</p>";
                        }
                        ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>
</html>
