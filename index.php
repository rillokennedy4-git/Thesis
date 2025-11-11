<?php
session_start();

// Prevent caching of the login page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect authenticated users to their respective dashboards
// Only redirect if there is no "success" parameter in the URL
if (isset($_SESSION['user_id']) && !isset($_GET['success'])) {
    if ($_SESSION['role'] === 'Staff') {
        header("Location: staff.php");
        exit();
    } elseif ($_SESSION['role'] === 'System Admin') {
        header("Location: systemadmin.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Archiving System</title>
    <link href="css/33styles.css" rel="stylesheet" />
    <link href="css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="images/ccticon.png">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: url('images/testbg.png') no-repeat center center/cover;
        }

        .container {
    display: flex;
    width: 800px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    position: absolute; /* Fixed positioning */
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}
        /* Left side - Title */
        .left-section {
            width: 50%;
            background: #d9534f;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: white;
            text-align: center;
            
        }

        
        .left-section h2 {
            font-size: 28px;
            font-weight: 600;
        }

        /* Right side - Login Form */
        .right-section {
            width: 50%;
            padding: 40px;
            text-align: center;
        }

        .logo img {
            width: 70px;
            margin-bottom: 10px;
        }

        .login-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            position: relative;
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            font-weight: 500;
            font-size: 14px;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #d9534f;
            box-shadow: 0 0 5px rgba(217, 83, 79, 0.5);
        }

        /* Password visibility toggle */
        .password-container {
            display: flex;
            align-items: center;
            position: relative;
        }

        .password-container input {
            width: 100%;
            padding-right: 35px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            cursor: pointer;
            color: #777;
        }

        .toggle-password:hover {
            color: #333;
        }

        .login-btn {
            width: 100%;
            padding: 10px;
            background: #d9534f;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-btn:hover {
            background: #c9302c;
        }

        .bottom-links {
            margin-top: 10px;
            font-size: 13px;
        }

        .bottom-links a {
            color: #d9534f;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Left Side -->
        <div class="left-section">
            <h2>Web Based Archiving System for Registrar's Office of City College of Tagaytay</h2>
        </div>

        <!-- Right Side (Login Form) -->
        <div class="right-section">
            <div class="logo">
                <img src="images/cctbg.png" alt="CCT Logo">
            </div>
            <div class="login-title">LOGIN</div>
            <form action="connection/login.php" method="POST">
                <div class="form-group">
                    <label for="user-name">Username</label>
                    <input type="text" id="user-name" name="username" placeholder="Enter your username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fa fa-eye-slash toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                <button type="submit" class="login-btn">Sign In</button>
            </form>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const successMessage = urlParams.get('success');
            const errorMessage = urlParams.get('error');
            const role = urlParams.get('role');

            // Show error message if login fails
            if (errorMessage) {
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: decodeURIComponent(errorMessage),
                });
            }

            // Show success message and redirect after login
            if (successMessage) {
                Swal.fire({
                    icon: 'success',
                    title: 'Login Successful',
                    text: decodeURIComponent(successMessage),
                    timer: 2000,
                    showConfirmButton: false,
                }).then(() => {
                    // Clear the query parameters from the URL
                    window.history.replaceState({}, document.title, window.location.pathname);

                    // Redirect to the appropriate dashboard
                    if (role === 'System Admin') {
                        window.location.replace('systemadmin.php');
                    } else if (role === 'Staff') {
                        window.location.replace('staff.php');
                    }
                });
            }

            // Password visibility toggle
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });

        function validatePassword() {
            // Your password validation logic here
            return true; // Return true to allow form submission
        }
    </script>
    
    <!-- Custom Scripts disable zooming-->
    <script src="js/disable.js" defer></script>
    <!-- SweetAlert2 -->
<script src="js/sweetalert.js" defer></script>
<!-- Bootstrap -->
<script src="js/bootsrap.js" defer></script>
</body>
</html>