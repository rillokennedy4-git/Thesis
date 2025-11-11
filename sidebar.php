<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    
    .sb-sidenav {
        background-color: #820D0D; 
    }

    .nav-link {
    color: #ffffff; /* Default text color */
    font-weight: 500;
    text-decoration: none;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.nav-link:hover {
    color: #ffc107; /* Yellow on hover */
    background-color: rgba(255, 255, 255, 0.1);
}

.nav-link.active {
    color: #ffc107 !important; /* Yellow text for active links */
    background-color: rgba(255, 255, 255, 0.1); /* Subtle background highlight */
    font-weight: bold;
   
}

.imglogo{
    text-align: center;  /* Horizontally center the image */
}
/*
.imglogo img {
    width: 100px;          Set the width 
    height: 100px;          Set the height to match width for a perfect circle 
    border-radius: 50%;     This makes the image round 
    object-fit: cover;      Ensures the image fits nicely within the circle 
}
*/
    .sb-sidenav-menu-heading {
        color: #ffffff; 
        font-size: 13px;
        text-transform: uppercase;
        padding: 10px 20px;
        font-weight: bold;
        letter-spacing: 0.05em;
    }
</style>


<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">
                <div class="imglogo">
                    <img src="images/cctbg.png" alt="CCT Logo" style="width: 100px; height: auto;">
                </div>
            </div>

            <div class="sb-sidenav-menu-heading">Core</div>
            <a class="nav-link <?= ($current_page == 'staff.php') ? 'active' : '' ?>" href="staff.php">
                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                Dashboard
            </a>

            <div class="sb-sidenav-menu-heading">Documents</div>

            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseStudents" aria-expanded="false" aria-controls="collapseStudents">
                <div class="sb-nav-link-icon"><i class="fas fa-user-graduate"></i></div>
                Students
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse <?= ($current_page == 'allstudents.php' || $current_page == 'addingofstudents.php') ? 'show' : '' ?>" id="collapseStudents">

                <nav class="sb-sidenav-menu-nested nav">
                <a class="nav-link <?= ($current_page == 'addingofstudents.php') ? 'active' : '' ?>" href="addingofstudents.php">Add Students</a>
                    <a class="nav-link <?= ($current_page == 'allstudents.php') ? 'active' : '' ?>" href="allstudents.php">List of Active Students</a>

                </nav>
            </div>
            
            <a class="nav-link <?= ($current_page == 'upload.php') ? 'active' : '' ?>" href="upload.php">
                <div class="sb-nav-link-icon"><i class="fas fa-upload"></i></div>
                Upload 201 Files
            </a>

            <div class="sb-sidenav-menu-heading">Records</div>
            <a class="nav-link <?= ($current_page == 'archived.php') ? 'active' : '' ?>" href="archived.php">
                <div class="sb-nav-link-icon"><i class="fas fa-archive"></i></div>
                Archives
            </a>
            <div class="sb-sidenav-menu-heading">Monitoring</div>
            <a class="nav-link <?= ($current_page == 'checklist.php') ? 'active' : '' ?>" href="checklist.php">
                <div class="sb-nav-link-icon"><i class="fas fa-file-alt"></i></div>
                Checklist
            </a>

            <a class="nav-link <?= ($current_page == 'history.php') ? 'active' : '' ?>" href="history.php">
                <div class="sb-nav-link-icon"><i class="fas fa-history"></i></div>
                History
            </a>

            <div class="sb-sidenav-menu-heading">Report</div>
            <a class="nav-link <?= ($current_page == 'report.php') ? 'active' : '' ?>" href="report.php">
                <div class="sb-nav-link-icon"><i class="fas fa-file-alt"></i></div>
                Generate Report
            </a>



        </div>
    </div>
</nav>