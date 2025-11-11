
<style>
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


<?php


$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Unknown User'; // Retrieve full name
?>

<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3"><?php echo htmlspecialchars($userName); ?></a>
    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0 ms-lg-3" id="sidebarToggle"><i class="fas fa-bars"></i></button>

    <!-- Centered Title -->
    <h1 class="navbar-brand ps-3">WEB-BASED ARCHIVING SYSTEM FOR REGISTRAR'S OFFICE OF CITY COLLEGE OF TAGAYTAY</h1>
    <!-- Navbar-->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user fa-fw"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <!-- Add the ID 'logoutBtn' here -->
                <li><a class="dropdown-item" id="logoutBtn" href="logout.php">Log Out</a></li>
            </ul>
        </li>
    </ul>
</nav>




<script>
document.addEventListener('DOMContentLoaded', function () {
    const logoutBtn = document.getElementById('logoutBtn'); // Ensure this matches the ID in the HTML
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (event) {
            event.preventDefault(); // Prevent the default link action
            Swal.fire({
                title: 'Are you sure you want to logout?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php'; // Redirect after confirmation
                }
            });
        });
    } else {
        console.error('Logout button not found in the DOM.');
    }
});


</script>
<!-- Custom Scripts disable zooming-->
<script src="js/disable.js" defer></script>
<!-- Custom Scripts -->
<script src="js/goback.js" defer></script>
<!-- Custom Scripts -->
<script src="js/preload.js" defer></script>