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
$con = getDbConnection();

// Handle AJAX request for filtering
if (isset($_POST['yearValue']) || isset($_POST['semesterValue']) || isset($_POST['programValue']) || isset($_POST['statusValue']) || isset($_POST['genderValue']) || isset($_POST['recordStatusValue'])) {
    $yearValue = $_POST['yearValue'];
    $semesterValue = $_POST['semesterValue'];
    $programValue = $_POST['programValue'];
    $statusValue = $_POST['statusValue'];
    $genderValue = $_POST['genderValue'];
    $recordStatusValue = $_POST['recordStatusValue'];

    $sql = "
    SELECT 
        tbl_student.StudentNumber,
        tbl_student.FirstName,
        tbl_student.MiddleName,
        tbl_student.LastName,
        tbl_student.Gender,
        tbl_course.course_name,
        tbl_academicyr.Academic_Year,
        tbl_semester.Semester_Name,
        tbl_student.Status,
        tbl_record_status.Record_Status
    FROM 
        tbl_student
    JOIN 
        tbl_course ON tbl_student.Course_id = tbl_course.Course_id
    JOIN 
        tbl_academicyr ON tbl_student.AcademicYr_id = tbl_academicyr.AcademicYr_id
    JOIN 
        tbl_semester ON tbl_student.Semester_id = tbl_semester.Semester_id
    JOIN
        tbl_record_status ON tbl_student.StudentNumber = tbl_record_status.StudentNumber
    ";

    if ($yearValue !== "") {
        $sql .= " AND tbl_academicyr.Academic_Year = '$yearValue'";
    }
    if ($semesterValue !== "") {
        $sql .= " AND tbl_semester.Semester_Name = '$semesterValue'";
    }
    if ($programValue !== "") {
        $sql .= " AND tbl_course.course_name = '$programValue'";
    }
    if ($statusValue !== "") {
        $sql .= " AND tbl_student.Status = '$statusValue'";
    }
    if ($genderValue !== "") {
        $sql .= " AND tbl_student.Gender = '$genderValue'";
    }
    if ($recordStatusValue !== "") {
        $sql .= " AND tbl_record_status.Record_Status = '$recordStatusValue'";
    }

    $sql .= " ORDER BY tbl_student.LastName ASC";
    $students = $con->query($sql) or die($con->error);

    $result = '';

    while ($row = $students->fetch_assoc()) {
        $result .= "<tr class='clickable'>
                        <td>{$row['StudentNumber']}</td>
                        <td>{$row['LastName']}, {$row['FirstName']} {$row['MiddleName']}</td>
                        <td>{$row['Gender']}</td>
                        <td>{$row['course_name']}</td>
                        <td>{$row['Academic_Year']}</td>
                        <td>{$row['Semester_Name']}</td>
                        <td>{$row['Status']}</td>
                        <td>{$row['Record_Status']}</td>
                    </tr>";
    }
    echo $result;
    exit;
}

// Query to get distinct academic years, semesters, and programs for the dropdown
$yearQuery = "SELECT DISTINCT Academic_Year FROM tbl_academicyr ORDER BY Academic_Year ASC";
$yearResults = $con->query($yearQuery) or die($con->error);

$semesterQuery = "SELECT DISTINCT Semester_Name FROM tbl_semester ORDER BY Semester_Name ASC";
$semesterResults = $con->query($semesterQuery) or die($con->error);

$programQuery = "SELECT DISTINCT course_name FROM tbl_course ORDER BY course_name ASC";
$programResults = $con->query($programQuery) or die($con->error);

// New Queries for Status, Gender, and Record Status
$statusQuery = "SELECT DISTINCT Status FROM tbl_student ORDER BY Status ASC";
$statusResults = $con->query($statusQuery) or die($con->error);

$genderQuery = "SELECT DISTINCT Gender FROM tbl_student ORDER BY Gender ASC";
$genderResults = $con->query($genderQuery) or die($con->error);

$recordStatusQuery = "SELECT DISTINCT Record_Status FROM tbl_record_status ORDER BY Record_Status ASC";
$recordStatusResults = $con->query($recordStatusQuery) or die($con->error);


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
        <link href="css/33styles.css" rel="stylesheet" />
        <link href="css/all.min.css" rel="stylesheet" />
        <link rel="shortcut icon" href="images/ccticon.png">
    <!--<link href="css/select2.min.css" rel="stylesheet" />
    <script src="js/select2.min.js" defer></script>-->


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
                    <h1 class="mt-4">GENERATE REPORT</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active"></li>
                    </ol>

                    <!-- Filter Panel -->
                    <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <label for="yearFilter" class="form-label">Year Enrolled:</label>
                                <select id="yearFilter" class="form-select" >
                                    <option value="">All</option>
                                    <?php while ($row = $yearResults->fetch_assoc()) { ?>
                                        <option value="<?php echo $row['Academic_Year']; ?>"><?php echo $row['Academic_Year']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="semesterFilter" class="form-label">Semester:</label>
                                <select id="semesterFilter" class="form-select">
                                    <option value="">All</option>
                                    <?php while ($row = $semesterResults->fetch_assoc()) { ?>
                                        <option value="<?php echo $row['Semester_Name']; ?>"><?php echo $row['Semester_Name']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="programFilter" class="form-label">Program:</label>
                                <select id="programFilter" class="form-select">
                                    <option value="">All</option>
                                    <?php while ($row = $programResults->fetch_assoc()) { ?>
                                        <option value="<?php echo $row['course_name']; ?>"><?php echo $row['course_name']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
    <label for="statusFilter" class="form-label">Status:</label>
    <select id="statusFilter" class="form-select">
        <option value="">All</option>
        <?php while ($row = $statusResults->fetch_assoc()) { ?>
            <option value="<?php echo $row['Status']; ?>"><?php echo $row['Status']; ?></option>
        <?php } ?>
    </select>
</div>

<div class="col-md-2 mb-3">
    <label for="genderFilter" class="form-label">Gender:</label>
    <select id="genderFilter" class="form-select">
        <option value="">All</option>
        <?php while ($row = $genderResults->fetch_assoc()) { ?>
            <option value="<?php echo $row['Gender']; ?>"><?php echo $row['Gender']; ?></option>
        <?php } ?>
    </select>
</div>

<div class="col-md-2 mb-3">
    <label for="recordStatusFilter" class="form-label">Record Status:</label>
    <select id="recordStatusFilter" class="form-select">
        <option value="">All</option>
        <?php while ($row = $recordStatusResults->fetch_assoc()) { ?>
            <option value="<?php echo $row['Record_Status']; ?>"><?php echo $row['Record_Status']; ?></option>
        <?php } ?>
    </select>
</div>


                        <!-- Search and Export Buttons -->
                        <div class="mt-3">
    <button id="searchButton" class="btn btn-primary">Search</button>
    <a id="exportButton" href="export.php" class="btn btn-success">Download to Excel</a>
    <a id="printButton" class="btn btn-secondary" >Print</a>
</div>
                    </div>
               
                       
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>Student Number</th>
                                        <th>Name</th>
                                        <th>Gender</th>
                                        <th>Program</th>
                                        <th>Year Enrolled</th>
                                        <th>Semester Enrolled</th>
                                        <th>Status</th>
                                        <th>Record Status</th>
                                    </tr>
                                </thead>
                                <tbody id="studentTableBody">
                                    <!-- Results will be appended here after filtering -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <div>
                <?php include 'footer.php'; ?>
                </div>
        </div>
    </div>
    <script>
    $('#printButton').on('click', function () {
        
        const yearValue = $('#yearFilter').val();
        const semesterValue = $('#semesterFilter').val();
        const programValue = $('#programFilter').val();
        const statusValue = $('#statusFilter').val();
        const genderValue = $('#genderFilter').val();
        const recordStatusValue = $('#recordStatusFilter').val();

        // Redirect to print.php with filter parameters
        $(this).attr('href', `SA_print.php?yearValue=${yearValue}&semesterValue=${semesterValue}&programValue=${programValue}&statusValue=${statusValue}&genderValue=${genderValue}&recordStatusValue=${recordStatusValue}`);
    });
</script>
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
    
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const datatablesSimple = document.getElementById('datatablesSimple');

        // Initialize Simple-DataTables only once
        let dataTable;
        if (datatablesSimple) {
            dataTable = new simpleDatatables.DataTable(datatablesSimple, {
                perPage: 5,
                searchable: true,
                labels: {
                    placeholder: "Search...",
                    perPage: "entries per page",
                    noRows: "No entries found",
                    info: "Showing {start} to {end} of {rows} entries"
                }
            });
        }

        $('#searchButton').on('click', function () {
            const yearValue = $('#yearFilter').val();
            const semesterValue = $('#semesterFilter').val();
            const programValue = $('#programFilter').val();
            const statusValue = $('#statusFilter').val();
            const genderValue = $('#genderFilter').val();
            const recordStatusValue = $('#recordStatusFilter').val();

            $.ajax({
                type: 'POST',
                url: '', // Current page URL for AJAX
                data: {
                    yearValue,
                    semesterValue,
                    programValue,
                    statusValue,
                    genderValue,
                    recordStatusValue
                },
                success: function (response) {
                    // Destroy the existing Simple-DataTables instance before updating
                    if (dataTable) {
                        dataTable.destroy();
                    }

                    // Update the table with new data from AJAX
                    $('#studentTableBody').html(response);

                    // Reinitialize Simple-DataTables after updating the table content
                    dataTable = new simpleDatatables.DataTable(datatablesSimple, {
                        perPage: 5,
                        searchable: true,
                        labels: {
                            placeholder: "Search...",
                            perPage: " entries per page",
                            noRows: "No entries found",
                            info: "Showing {start} to {end} of {rows} entries"
                        }
                    });
                }
            });
        });

        $('#exportButton').on('click', function (e) {
            const yearValue = $('#yearFilter').val();
            const semesterValue = $('#semesterFilter').val();
            const programValue = $('#programFilter').val();
            const statusValue = $('#statusFilter').val();
            const genderValue = $('#genderFilter').val();
            const recordStatusValue = $('#recordStatusFilter').val();

            $(this).attr('href', `export.php?yearValue=${yearValue}&semesterValue=${semesterValue}&programValue=${programValue}&statusValue=${statusValue}&genderValue=${genderValue}&recordStatusValue=${recordStatusValue}`);
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
    const datatablesSimple = document.getElementById('datatablesSimple');
    let dataTable;
    let dataLoaded = false; // Flag to track if data is available

    if (datatablesSimple) {
        dataTable = new simpleDatatables.DataTable(datatablesSimple, {
            perPage: 5,
            searchable: true,
            labels: {
                placeholder: "Search...",
                perPage: " entries per page",
                noRows: "No entries found",
                info: "Showing {start} to {end} of {rows} entries"
            }
        });
    }

    function getFilterValues() {
        return {
            yearValue: $('#yearFilter').val(),
            semesterValue: $('#semesterFilter').val(),
            programValue: $('#programFilter').val(),
            statusValue: $('#statusFilter').val(),
            genderValue: $('#genderFilter').val(),
            recordStatusValue: $('#recordStatusFilter').val()
        };
    }

    $('#searchButton').on('click', function () {
        const filters = getFilterValues();

        Swal.fire({
            title: 'Loading...',
            text: 'Please wait while the data is being generated.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            type: 'POST',
            url: '', // Your server URL here
            data: filters,
            success: function (response) {
                Swal.close(); // Close loading alert as soon as data loads

                if (response.trim() === '') {
                    dataLoaded = false; // Set flag to false if no data is returned
                    Swal.fire({
                        title: 'No Results Found',
                        text: 'No data matches your search criteria.',
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                } else {
                    dataLoaded = true; // Set flag to true if data is loaded
                    $('#studentTableBody').html(response);
                    dataTable.refresh(); // Refresh Simple-DataTables without reinitializing
                }
            },
            error: function () {
                Swal.close();
                dataLoaded = false; // Set flag to false in case of error
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to generate data. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    $('#exportButton').on('click', function (e) {
        e.preventDefault();

        if (!dataLoaded) {
            Swal.fire({
                title: 'No Data Available',
                text: 'There is no data to download based on the current filters.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        Swal.fire({
            title: 'Are you sure you want to download?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, download'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Preparing your download...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading()
                });

                const filters = getFilterValues();
                
                // Start the download with the filter values
                window.location.href = `export.php?yearValue=${filters.yearValue}&semesterValue=${filters.semesterValue}&programValue=${filters.programValue}&statusValue=${filters.statusValue}&genderValue=${filters.genderValue}&recordStatusValue=${filters.recordStatusValue}`;

                setTimeout(() => Swal.close(), 2000);
            }
        });
    });
});

</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const datatablesSimple = document.getElementById('datatablesSimple');
    let dataTable;
    let dataLoaded = false; // Flag to track if data is available

    if (datatablesSimple) {
        dataTable = new simpleDatatables.DataTable(datatablesSimple, {
            perPage: 5,
            searchable: true,
            labels: {
                placeholder: "Search...",
                perPage: " entries per page",
                noRows: "No entries found",
                info: "Showing {start} to {end} of {rows} entries"
            }
        });
    }

    function getFilterValues() {
        return {
            yearValue: $('#yearFilter').val(),
            semesterValue: $('#semesterFilter').val(),
            programValue: $('#programFilter').val(),
            statusValue: $('#statusFilter').val(),
            genderValue: $('#genderFilter').val(),
            recordStatusValue: $('#recordStatusFilter').val()
        };
    }

    $('#searchButton').on('click', function () {
        const filters = getFilterValues();

        Swal.fire({
            title: 'Loading...',
            text: 'Please wait while the data is being generated.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            type: 'POST',
            url: '', // Your server URL here
            data: filters,
            success: function (response) {
                Swal.close(); // Close loading alert as soon as data loads

                if (response.trim() === '') {
                    dataLoaded = false; // Set flag to false if no data is returned
                    $('#printButton').prop('disabled', true); // Disable Print button
                    Swal.fire({
                        title: 'No Results Found',
                        text: 'No data matches your search criteria.',
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                } else {
                    dataLoaded = true; // Set flag to true if data is loaded
                    $('#printButton').prop('disabled', false); // Enable Print button
                    $('#studentTableBody').html(response);
                    dataTable.refresh(); // Refresh Simple-DataTables without reinitializing
                }
            },
            error: function () {
                Swal.close();
                dataLoaded = false; // Set flag to false in case of error
                $('#printButton').prop('disabled', true); // Disable Print button
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to generate data. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    $('#printButton').on('click', function (e) {
        e.preventDefault();

        if (!dataLoaded) {
            Swal.fire({
                title: 'No Data Available',
                text: 'There is no data to print based on the current filters.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        const filters = getFilterValues();

        // Redirect to print.php with filter parameters
        window.location.href = `SA_print.php?yearValue=${filters.yearValue}&semesterValue=${filters.semesterValue}&programValue=${filters.programValue}&statusValue=${filters.statusValue}&genderValue=${filters.genderValue}&recordStatusValue=${filters.recordStatusValue}`;
    });

    // Initialize buttons
    $('#printButton').prop('disabled', true); // Disable Print button by default

    
});
</script>
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
