<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Optional: Enforce role-based access
if ($_SESSION['role'] !== 'System Admin') {
    header("Location: index.php");
    exit();
}

include_once("database/db_connection.php");
$con = getDbConnection();

// Default query to fetch all records if no date filter is applied
$dateFilter = isset($_GET['filter_date']) ? $_GET['filter_date'] : null;
$query = "
    SELECT 
        d.Student_Id, 
        dt.Type_Name, 
        d.FileName, 
        d.File_Path, 
        d.Date_Uploaded, 
        CONCAT(u.First_Name, ' ', u.Last_Name) AS Uploaded_By,
        CONCAT(s.FirstName, ' ', IFNULL(s.MiddleName, ''), ' ', s.LastName) AS Student_Name
    FROM 
        tbl_documents d
    INNER JOIN 
        tbl_documenttype dt ON d.DocumentType_Id = dt.DocumentType_Id
    LEFT JOIN 
        tbl_user u ON d.Uploaded_By = u.User_id
    LEFT JOIN 
        tbl_student s ON d.Student_Id = s.StudentNumber
";

if ($dateFilter) {
    $query .= " WHERE DATE(d.Date_Uploaded) = '" . mysqli_real_escape_string($con, $dateFilter) . "'";
}
$query .= " ORDER BY d.Date_Uploaded DESC";

$result = mysqli_query($con, $query);

if (!$result) {
    die("Error in query: " . mysqli_error($con));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Archiving System</title>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <link href="css/33styles.css" rel="stylesheet" />
    <link href="css/all.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="images/ccticon.png">
</head>
<body class="sb-nav-fixed">
<div >
            <?php include 'topnav.php'; ?>
        </div>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'sidebar1.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main class="container mt-5">
                <h1 class="mb-3">Recent Document Uploads</h1>
                <div class="card">
                    <div class="card-header">
                        Filter by Date
                        <input type="date" name="filter_date" class="form-control d-inline w-auto" value="<?= htmlspecialchars($dateFilter) ?>" onchange="applyFilter(this.value)">
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <table id="datatablesSimple" class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Student Number</th>
                                        <th scope="col">Document Owner</th>
                                        <th scope="col">Type of Document</th>
                                        <th scope="col">Document Name</th>
                                        <th scope="col">Date Uploaded</th>
                                        <th scope="col">Uploaded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['Student_Id']) ?></td>
                                        <td><?= htmlspecialchars($row['Student_Name']) ?></td>
                                        <td><?= htmlspecialchars($row['Type_Name']) ?></td>
                                        <td><?= htmlspecialchars($row['FileName']) ?></td>
                                        <td><?= htmlspecialchars($row['Date_Uploaded']) ?></td>
                                        <td><?= htmlspecialchars($row['Uploaded_By'] ?: 'System') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted">No uploads found for the selected date.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
            <div>
                <?php include 'footer.php'; ?>
                </div>
        </div>
    </div>

    <script>
        function applyFilter(date) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('filter_date', date);
            window.location.search = urlParams.toString();
        }
    </script>

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