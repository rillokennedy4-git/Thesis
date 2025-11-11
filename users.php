<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] !== 'System Admin') {
    header("Location: index.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch all users
$sql = "SELECT User_Id, First_Name, Last_Name, Username, Role, Status, Create_At, Picture FROM tbl_user";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error); // Display the exact SQL error
}

// Count active users excluding System Admin
$activeCountQuery = "SELECT COUNT(*) AS count 
                     FROM tbl_user 
                     WHERE Status = 'Active' AND Role != 'System Admin'";
$activeCount = $conn->query($activeCountQuery)->fetch_assoc()['count'];

// Count archived users excluding System Admin
$archiveCountQuery = "SELECT COUNT(*) AS count 
                      FROM tbl_user 
                      WHERE Status = 'Inactive' AND Role != 'System Admin'";
$archiveCount = $conn->query($archiveCountQuery)->fetch_assoc()['count'];

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Archiving System</title>
    <link href="css/33styles.css" rel="stylesheet" />
    <link href="css/all.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="images/ccticon.png">
    <style>
        /* Cards Container */
        .cards-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 50px;
        }

        .card {
            height: 150px;
            width: 350px;
            text-align: center;
            border-radius: 10px;
        }

        .add-user-button {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 1.2rem;
            border: none;
            outline: none;
        }

        /* Hover Effects */
        .card[style*="background-color:rgb(0, 108, 224);"]:hover {
            background-color: #0056b3 !important;
        }

        .card[style*="background-color: #dc3545;"]:hover {
            background-color: rgb(151, 47, 47) !important;
        }

        .card[style*="background-color: #28a745;"]:hover {
            background-color: rgb(35, 107, 37) !important;
        }

        .card .btn-block {
            font-size: 1rem;
            font-weight: bold;
        }

        .ml-3 {
            margin-left: 15px !important;
        }

        .mx-3 {
            margin-left: 10px;
            margin-right: 10px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .cards-container {
                flex-direction: column;
            }

            .card {
                width: 100%;
            }
        }

        /* Modal Styles */
        .swal2-popup {
            z-index: 1060 !important;
        }

        .modal-content {
            border-radius: 8px;
        }

        .modal-header {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-group label {
            color: #343a40;
            font-size: 1rem;
        }

        .form-control {
            border-radius: 4px;
        }

        #profile-picture-container label {
            cursor: pointer;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        #changePasswordBtn {
            background-color: #dc3545;
            color: #fff;
        }

        #changePasswordBtn:hover {
            background-color: #c82333;
        }

        .position-relative .btn-outline-secondary {
            border: none;
            padding: 0;
            color: #6c757d;
        }

        .position-relative .btn-outline-secondary:hover {
            color: #343a40;
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
<!-- Add User Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" role="dialog" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="addStaffModalLabel">Add New User</h5>

            </div>
            <div class="modal-body">
                <form id="addStaffForm" action="connection/add_users.php" method="post" enctype="multipart/form-data">
                    <!-- Profile Picture Section -->
                    <div class="text-center mb-4">
                        <div id="profile-picture-container" style="position: relative; display: inline-block;">
                            <img id="profilePicturePreview" 
                                 src="https://via.placeholder.com/150" 
                                 class="rounded-circle border border-secondary mb-2" 
                                 style="width: 100px; height: 100px; object-fit: cover;" 
                                 alt="Profile Picture Preview">
                            <br><label for="profilePicture" class="btn btn-sm btn-outline-secondary mt-2">
                                <i class="fas fa-camera"></i> Upload Picture
                            </label>
                            <input type="file" id="profilePicture" name="picture" accept="image/*" onchange="previewProfilePicture(event)" style="display: none;">
                        </div>
                    </div>

                    <!-- User Information Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="firstName" class="font-weight-bold">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lastName" class="font-weight-bold">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username" class="font-weight-bold">Username</label>
                                <input type="text" class="form-control" id="username" name="username" readonly required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role" class="font-weight-bold">Role</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="Staff">Staff</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Password Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group position-relative">
                                <label for="password" class="font-weight-bold">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" 
                                        style="top: 35px; right: 10px;" 
                                        onclick="togglePasswordVisibility('password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mt-4">
                                <input type="checkbox" class="form-check-input" id="autoGeneratePassword">
                                <label class="form-check-label" for="autoGeneratePassword">Auto-generate password</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group position-relative">
                                <label for="confirmPassword" class="font-weight-bold">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" 
                                        style="top: 35px; right: 10px;" 
                                        onclick="togglePasswordVisibility('confirmPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

                <!-- Cards Section -->
                <div class="container-fluid mt-4">
                    <div class="cards-container">
                        <!-- Add User Button -->
                        <div class="card shadow-sm border-0 text-white" style="background-color:rgb(7, 109, 218); border-radius: 10px;">
                            <button class="btn btn-primary btn-block h-100 text-white add-user-button" data-toggle="modal" data-target="#addStaffModal">
                                <i class="fas fa-user-plus fa-2x"></i>
                                <h5 class="mt-2 font-weight-bold">Add User</h5>
                            </button>
                        </div>

                        <!-- Active Accounts Card -->
                        <div class="card shadow-sm border-0 text-white" style="background-color: #28a745; border-radius: 10px;">
                            <div class="card-body d-flex align-items-center justify-content-center">
                                <i class="fas fa-users fa-3x text-white"></i>
                                <div class="ml-3">
                                    <h5 class="card-title font-weight-bold">Active Accounts</h5>
                                    <h2 class="card-text font-weight-bold mb-0"><?php echo $activeCount; ?></h2>
                                </div>
                            </div>
                            <a href="active_accounts.php" class="btn btn-block text-white" style="background-color: #218838; border-radius: 0 0 10px 10px;">View Details</a>
                        </div>

                        <!-- Inactive Accounts Card -->
                        <div class="card shadow-sm border-0 text-white" style="background-color: #dc3545; border-radius: 10px;">
                            <div class="card-body d-flex align-items-center justify-content-center">
                                <i class="fas fa-database fa-3x text-white"></i>
                                <div class="ml-3">
                                    <h5 class="card-title font-weight-bold">Inactive Accounts</h5>
                                    <h2 class="card-text font-weight-bold mb-0"><?php echo $archiveCount; ?></h2>
                                </div>
                            </div>
                            <a href="archived_accounts.php" class="btn btn-block text-white" style="background-color: #c82333; border-radius: 0 0 10px 10px;">View Details</a>
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
    // Preview Profile Picture during Upload
    function previewProfilePicture(event) {
        const reader = new FileReader();
        reader.onload = function () {
            const output = document.getElementById("profilePicturePreview");
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    // Toggle Password Visibility
    function togglePasswordVisibility(inputId, toggleButton) {
        const passwordInput = document.getElementById(inputId);
        const icon = toggleButton.querySelector('i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // Auto-generate password functionality
    document.getElementById('autoGeneratePassword').addEventListener('change', function () {
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirmPassword');
        if (this.checked) {
            const autoGeneratedPassword = generateRandomPassword(8);
            passwordField.value = autoGeneratedPassword;
            confirmPasswordField.value = autoGeneratedPassword;
            passwordField.setAttribute('readonly', true);
            confirmPasswordField.setAttribute('readonly', true);
        } else {
            passwordField.value = '';
            confirmPasswordField.value = '';
            passwordField.removeAttribute('readonly');
            confirmPasswordField.removeAttribute('readonly');
        }
    });

    // Generate a random password of specified length
    function generateRandomPassword(length) {
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        let password = '';
        for (let i = 0; i < length; i++) {
            password += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        return password;
    }
</script>
<script>
    // Auto-generate username based on First Name and Last Name
    document.getElementById('firstName').addEventListener('input', generateUsername);
    document.getElementById('lastName').addEventListener('input', generateUsername);

    function generateUsername() {
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const username = `${firstName}.${lastName}`.toLowerCase().replace(/\s+/g, '');
        document.getElementById('username').value = username;
    }

   // Auto-generate password functionality
document.getElementById('autoGeneratePassword').addEventListener('change', function () {
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirmPassword');
    if (this.checked) {
        const autoGeneratedPassword = generateRandomPassword(8);
        passwordField.value = autoGeneratedPassword;
        confirmPasswordField.value = autoGeneratedPassword;
        passwordField.setAttribute('readonly', true);
        confirmPasswordField.setAttribute('readonly', true);
    } else {
        passwordField.value = '';
        confirmPasswordField.value = '';
        passwordField.removeAttribute('readonly');
        confirmPasswordField.removeAttribute('readonly');
    }
});

// Generate a random password of specified length
function generateRandomPassword(length) {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < length; i++) {
        password += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    return password;
}

// Toggle password visibility
function togglePasswordVisibility(inputId, toggleButton) {
    const passwordInput = document.getElementById(inputId);
    const icon = toggleButton.querySelector('i');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}



</script>
<script>
    function togglePasswordVisibility(inputId, toggleButton) {
    const passwordInput = document.getElementById(inputId);
    const icon = toggleButton.querySelector('i');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

    </script>


<!-- JavaScript for Profile Picture Preview -->
<script>
    function previewProfilePicture(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('profilePicturePreview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>




<script>
    function previewProfilePicture(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('profilePicturePreview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
<script>
    document.getElementById('addStaffForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting the traditional way

        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Regular expression to validate at least 8 characters (allowing special characters)
        const passwordRegex = /^.{8,}$/; // At least 8 characters, any type

        // Check if passwords match
        if (password !== confirmPassword) {
            Swal.fire({
                icon: 'error',
                title: 'Password Mismatch',
                text: 'Passwords do not match. Please ensure they are identical.',
            });
            return; // Stop further execution
        }

        // Check if password meets the 8-character minimum requirement
        if (!passwordRegex.test(password)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Password',
                text: 'Password must be at least 8 characters long.',
            });
            return; // Stop further execution
        }

        // If validation passes, proceed with AJAX submission
        const formData = new FormData(this);

        fetch('connection/add_users.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'error') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                });
            } else if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'User Added',
                    text: data.message,
                }).then(() => {
                    window.location.href = 'users.php'; // Redirect after success
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Unexpected Error',
                text: 'An unexpected error occurred. Please try again later.',
            });
        });
    });
</script>
<!-- JavaScript for Toggling Password Section and Profile Picture Preview -->
<script>
    // Handle Save Changes Button
    // script for archiving accounts
document.getElementById("saveChangesButton").addEventListener("click", function () 
    const status = document.getElementById("updateStatus").value;

    if (status === "Inactive") {
    $('#updateUserModal').modal('hide'); // Hide Bootstrap modal
    Swal.fire({
        title: "Confirm Archive",
        text: "Enter your password to confirm archiving this account.",
        input: "password",
        inputAttributes: {
            autocapitalize: "off",
            autocomplete: "new-password",
            placeholder: "Enter your admin password",
        },
        showCancelButton: true,
        confirmButtonText: "Confirm",
        cancelButtonText: "Cancel",
        preConfirm: (password) => {
            if (!password) {
                Swal.showValidationMessage("Password is required.");
                return false;
            }
            return password; // Return password for further processing
        },
    }).then((result) => {
        if (result.isConfirmed) {
            console.log("Password entered:", result.value); // Debugging log
            saveChanges(result.value); // Pass password to saveChanges
        }
    });
} else {
    saveChanges(); // Normal save without password
})

    // Function to preview profile picture
    function previewProfilePicture(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const output = document.getElementById('profilePicturePreview');
            output.src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    // Toggle visibility for the password change section
    function togglePasswordChange() {
        const userInfoForm = document.getElementById('userInfoForm');
        const passwordChangeForm = document.getElementById('passwordChangeForm');
        const changePasswordBtn = document.getElementById('changePasswordBtn');

        const isPasswordFormHidden = passwordChangeForm.style.display === 'none';
        userInfoForm.style.display = isPasswordFormHidden ? 'none' : 'block';
        passwordChangeForm.style.display = isPasswordFormHidden ? 'block' : 'none';
        changePasswordBtn.innerHTML = isPasswordFormHidden 
            ? '<i class="fas fa-arrow-left"></i> Back' 
            : '<i class="fas fa-key"></i> Change Password';
    }

    // Toggle visibility for password fields
    function togglePasswordVisibility(inputId, toggleButton) {
        const passwordInput = document.getElementById(inputId);
        const icon = toggleButton.querySelector('i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // Reset fields when the modal is closed
    $('#updateUserModal').on('hidden.bs.modal', function () {
        // Clear password fields and reset form view
        document.getElementById('oldPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmNewPassword').value = '';
        document.getElementById('userInfoForm').style.display = 'block';
        document.getElementById('passwordChangeForm').style.display = 'none';
    });

    // Open the Update Account Modal with fetched user data
    // Open Update Modal
function openUpdateModal(userId) {
    fetch(`update_user.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                document.getElementById("userId").value = data.user.User_Id;
                document.getElementById("updateFirstName").value = data.user.First_Name;
                document.getElementById("updateLastName").value = data.user.Last_Name;
                document.getElementById("updateUsername").value = data.user.Username;
                document.getElementById("updateStatus").value = data.user.Status;

                // Update profile picture preview
                document.getElementById("profilePicturePreview").src = data.user.Picture || "https://via.placeholder.com/120";
                document.getElementById("userFullName").textContent = `${data.user.First_Name} ${data.user.Last_Name}`;
                $('#updateUserModal').modal('show');
            } else {
                console.error(data.message);
                alert(data.message);
            }
        })
        .catch(error => {
            console.error("Error fetching user data:", error);
            alert("An error occurred while fetching user data.");
        });
}

// Close Modal on Cancel or 'X' Click
$('#updateUserModal').on('hidden.bs.modal', function () {
    // Reset all modal fields
    document.getElementById("userId").value = "";
    document.getElementById("updateFirstName").value = "";
    document.getElementById("updateLastName").value = "";
    document.getElementById("updateUsername").value = "";
    document.getElementById("updateStatus").value = "Active";
    document.getElementById("profilePicturePreview").src = "https://via.placeholder.com/120";
    document.getElementById("userFullName").textContent = "Full Name";
});


    // JavaScript to handle form submission with debugging
    document.addEventListener('DOMContentLoaded', function() {
        const saveButton = document.getElementById('saveChangesButton');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                console.log('Save Changes button clicked'); // Debugging line
                saveChanges();
            });
        } else {
            console.error('Save Changes button not found in DOM'); // Debugging line
        }
    });

    function saveChanges(adminPassword = null) {
    const formData = new FormData();
    formData.append("userId", document.getElementById("userId").value);
    formData.append("status", document.getElementById("updateStatus").value);

    if (adminPassword) {
        formData.append("adminPassword", adminPassword); // Include admin password if archiving
    }

    fetch("update_user.php", {
        method: "POST",
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            console.log("Response from server:", data); // Log server response for debugging
            if (data.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: data.message,
                }).then(() => {
                    location.reload(); // Reload the page after successful update
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                });
            }
        })
        .catch((error) => {
            console.error("Error updating user:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An error occurred while updating the user.",
            });
        });
}

</script>
<script>
    function previewProfilePicture(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const output = document.getElementById('profilePicturePreview');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}

</script>

    <!-- JavaScript -->
    <script src="js/sweetalert.js" defer></script>
    <script src="js/bootsrap.js" defer></script>
    <script src="js/scripts.js" defer></script>
    <script src="js/jquery-3.5.1.min.js"></script>
    <script src="js/usersbootstrap4,3.js"></script>
</body>
</html>


