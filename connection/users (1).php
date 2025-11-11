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

$conn = new mysqli("localhost", "root", "", "db_archive");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT User_Id, First_Name, Last_Name, Username, Role, Status, Create_At, Picture FROM tbl_user";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error); // Display the exact SQL error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>User Management - SB Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="css/33styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="sb-nav-fixed">
            <div >
            <?php include 'topnav.php'; ?>
            </div>


            <div id="layoutSidenav_nav">
            <?php include 'sidebar.php'; ?>
            </div>
            
            <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">User Management</h1>
                    <br>
                    <div class="d-flex justify-content-between align-items-center my-4">
                        <h5>All Users: <?php echo $result->num_rows; ?></h5>
                        
                        <button class="btn btn-success" data-toggle="modal" data-target="#addStaffModal">
    <i class="fas fa-user-plus"></i> Add User
</button>

                    </div>

                    <table class="table table-hover">
    <thead>
        <tr>
            <th>Username</th>
            <th>Role</th>
            <th>Status</th>
            <th>Create Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="userTable">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="d-flex align-items-center">
                    <img src="<?php echo !empty($row['Picture']) ? 'upload/profile_pictures/' . $row['Picture'] : 'https://via.placeholder.com/40'; ?>" 
     class="rounded-circle mr-2" style="width: 40px; height: 40px;">

                        <div>
                            <strong><?php echo $row['First_Name'] . ' ' . $row['Last_Name']; ?></strong><br>
                            
                        </div>
                    </td>
                    <td><?php echo ucfirst($row['Role']); ?></td>
                    <td>
                        <span class="badge <?php echo ($row['Status'] == 'Active') ? 'badge-success' : 'badge-secondary'; ?>">
                            <?php echo ucfirst($row['Status']); ?>
                        </span>
                    </td>
                    <td><?php echo date("M d, Y", strtotime($row['Create_At'])); ?></td>
                    <td>
                    <div class="dropdown">
        <button class="btn btn-link text-secondary" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-right">
            <!-- Update Account Link -->
            <a class="dropdown-item" href="javascript:void(0);" onclick="openUpdateModal(<?php echo $row['User_Id']; ?>);">Update Account</a>
            <!-- Delete Account Link -->
            <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="confirmDeleteAccountWithPassword(<?php echo $row['User_Id']; ?>);">Delete Account</a>
        </div>
    </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" class="text-center">No users found</td></tr>
        <?php endif; ?>
    </tbody>
</table>

                </div>
            </main>
        </div>
    </div>

   <!-- Add User Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" role="dialog" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="addStaffModalLabel">Add New User</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <form id="addStaffForm" action="connection/users.php" method="post" enctype="multipart/form-data">
    <!-- Profile Picture Section -->
    <div class="text-center mb-4">
        <div id="profile-picture-container" style="position: relative;">
            <img id="profilePicturePreview" src="https://via.placeholder.com/120" class="rounded-circle border border-secondary" 
                 style="width: 120px; height: 120px; object-fit: cover;" alt="Profile Picture Preview">
            <label for="picture" class="btn btn-sm btn-outline-secondary" style="position: absolute; bottom: 0; right: 0;">
                <i class="fas fa-camera"></i>
            </label>
            <input type="file" class="form-control-file d-none" id="picture" name="picture" accept="image/*" onchange="previewProfilePicture(event)">
        </div>
        <small class="form-text text-muted">Click on the camera icon to select a profile picture.</small>
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
                <input type="text" class="form-control" id="username" name="username" required>
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

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="password" class="font-weight-bold">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="confirmPassword" class="font-weight-bold">Confirm Password</label>
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
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
<!-- Script For Password Verification Before Deleting a User  -->
<script>
    function confirmDeleteAccountWithPassword(userId) {
    Swal.fire({
        title: 'Enter Password',
        input: 'password',
        inputAttributes: {
            autocapitalize: 'off',
            placeholder: 'Enter the account password'
        },
        showCancelButton: true,
        confirmButtonText: 'Verify and Delete',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        preConfirm: (password) => {
            if (!password) {
                Swal.showValidationMessage('Password is required');
                return false;
            }
            // Verify password and send userId + password
            return verifyPassword(userId, password);
        }
    }).then((result) => {
        if (result.isConfirmed && result.value.status === 'success') {
            // Proceed with deletion if verification succeeds
            deleteAccount(userId);
        } else if (result.value && result.value.status === 'error') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.value.message,
            });
        }
    });
}

function verifyPassword(userId, password) {
    return fetch('delete_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ userId: userId, password: password })
    })
        .then(response => response.json())
        .then(data => {
            return data;
        })
        .catch(error => {
            console.error('Error during password verification:', error);
            return { status: 'error', message: 'An error occurred while verifying the password.' };
        });
}

function deleteAccount(userId) {
    fetch('delete_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ userId: userId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'The account has been deleted.',
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message,
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Unexpected Error',
                text: 'An error occurred while deleting the account.',
            });
        });
}

</script>

<!-- Script for Delete User Action -->
<script>
    function confirmDeleteAccount(userId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action will permanently delete the account.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteAccount(userId);
            }
        });
    }

    function deleteAccount(userId) {
        fetch('delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ userId: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'The account has been deleted.',
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message,
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Unexpected Error',
                text: 'An error occurred while deleting the account.',
            });
        });
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
    document.getElementById('addStaffForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting the traditional way

        var formData = new FormData(this); // Capture the form data

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






<div class="modal fade" id="updateUserModal" tabindex="-1" role="dialog" aria-labelledby="updateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateUserModalLabel">Update Account</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="userId" name="userId">
                
                <div class="row">
                    <!-- Left Side: Profile Picture and Name -->
                    <div class="col-md-4 text-center">
                        <div id="profile-picture-container" style="position: relative;">
                            <img id="profilePicturePreview" 
                                 src="https://via.placeholder.com/120" 
                                 class="rounded-circle border border-secondary mb-2" 
                                 style="width: 120px; height: 120px; object-fit: cover;" 
                                 alt="Profile Picture Preview">
                            <label for="updatePicture" class="btn btn-sm btn-outline-secondary" style="position: absolute; bottom: 0; right: 10px;">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="updatePicture" name="picture" accept="image/*" onchange="previewProfilePicture(event)">
                        </div>
                        <strong id="userFullName">Full Name</strong>
                    </div>

                    <!-- Right Side: User Details or Change Password -->
                    <div class="col-md-8" id="userDetailsSection">
                        <!-- Default User Information Form -->
                        <div id="userInfoForm">
                            <div class="form-group">
                                <label for="updateUsername">Username</label>
                                <input type="text" class="form-control" id="updateUsername" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="updateFirstName">First Name</label>
                                <input type="text" class="form-control" id="updateFirstName" name="firstName" required>
                            </div>
                            <div class="form-group">
                                <label for="updateLastName">Last Name</label>
                                <input type="text" class="form-control" id="updateLastName" name="lastName" required>
                            </div>
                            <div class="form-group">
                                <label for="updateStatus">Status</label>
                                <select class="form-control" id="updateStatus" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                            <!-- Change Password Button -->
                            <button id="changePasswordBtn" class="btn btn-danger mt-3" onclick="togglePasswordChange()">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>

                        <!-- Change Password Form (Initially Hidden) -->
<div id="passwordChangeForm" style="display: none;">
    <div class="form-group position-relative">
        <label for="oldPassword">Old Password</label>
        <input type="password" class="form-control" id="oldPassword" name="oldPassword">
        <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="top: 35px; right: 10px;" onclick="togglePasswordVisibility('oldPassword', this)">
            <i class="fas fa-eye"></i>
        </button>
    </div>
    <div class="form-group position-relative">
        <label for="newPassword">New Password</label>
        <input type="password" class="form-control" id="newPassword" name="newPassword">
        <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="top: 35px; right: 10px;" onclick="togglePasswordVisibility('newPassword', this)">
            <i class="fas fa-eye"></i>
        </button>
    </div>
    <div class="form-group position-relative">
        <label for="confirmNewPassword">Confirm New Password</label>
        <input type="password" class="form-control" id="confirmNewPassword" name="confirmNewPassword">
        <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="top: 35px; right: 10px;" onclick="togglePasswordVisibility('confirmNewPassword', this)">
            <i class="fas fa-eye"></i>
        </button>
    </div>
    <!-- Back Button to Return to User Information -->
    <button class="btn btn-secondary mt-3" onclick="togglePasswordChange()">
        <i class="fas fa-arrow-left"></i> Back
    </button>
</div>

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveChangesButton">Save Changes</button>
            </div>
        </div>
    </div>
</div>
<!-- Password Matching -->
<script>
    document.getElementById('addStaffForm').addEventListener('submit', function(event) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (password !== confirmPassword) {
            event.preventDefault(); // Stop form submission
            Swal.fire({
                icon: 'error',
                title: 'Password Mismatch',
                text: 'Passwords do not match. Please try again.',
            });
        }
    });
</script>





<!-- JavaScript for Toggling Password Section and Profile Picture Preview -->
<script>
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

    function saveChanges() {
        console.log('saveChanges function started'); // Debugging line
        const userId = document.getElementById('userId').value;
        const formData = new FormData();

        formData.append('userId', userId);
        formData.append('firstName', document.getElementById('updateFirstName').value);
        formData.append('lastName', document.getElementById('updateLastName').value);
        formData.append('username', document.getElementById('updateUsername').value);
        formData.append('status', document.getElementById('updateStatus').value);
        formData.append('existingPicture', document.getElementById('profilePicturePreview').src.split('/').pop());

        const picture = document.getElementById('updatePicture').files[0];
        if (picture) {
            formData.append('picture', picture);
        }

        const oldPassword = document.getElementById('oldPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmNewPassword = document.getElementById('confirmNewPassword').value;

        if (newPassword && oldPassword && confirmNewPassword) {
            formData.append('oldPassword', oldPassword);
            formData.append('newPassword', newPassword);
            formData.append('confirmNewPassword', confirmNewPassword);
        }

        console.log('Sending form data to server'); // Debugging line
        fetch('update_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response received'); // Debugging line
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debugging line
            if (data.status === 'success') {
                console.log('User updated successfully'); // Debugging line
                location.reload();
            } else {
                console.error('Error:', data.message); // Debugging line
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error); // Debugging line
            alert('An error occurred while updating user.');
        });
    }
</script>


<!-- Custom Styles -->
<style>
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


<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>


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
<!-- Script for Update Account Modal -->
<script>
    // Open the Update User Modal
    // Function to open the Update User Modal
    function openUpdateModal(userId) {
    console.log(`Fetching data for userId: ${userId}`); // Debugging

    fetch(`update_user.php?id=${userId}`)
        .then(response => {
            console.log(`Response status: ${response.status}`); // Debugging
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }
            return response.json();
        })
        .then(data => {
            console.log("Response data:", data); // Debugging

            if (data.status === "success") {
                document.getElementById("userId").value = data.user.User_Id;
                document.getElementById("updateFirstName").value = data.user.First_Name;
                document.getElementById("updateLastName").value = data.user.Last_Name;
                document.getElementById("updateUsername").value = data.user.Username;
                document.getElementById("updateStatus").value = data.user.Status;

                const profilePicturePreview = document.getElementById("profilePicturePreview");
                profilePicturePreview.src = data.user.Picture || "https://via.placeholder.com/120";

                document.getElementById("userFullName").textContent = `${data.user.First_Name} ${data.user.Last_Name}`;

                $('#updateUserModal').modal("show");
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                });
            }
        })
        .catch(error => {
            console.error("Error fetching user data:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An error occurred while fetching user data.",
            });
        });
}


// Preview Profile Picture during Upload
function previewProfilePicture(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function () {
            const output = document.getElementById("profilePicturePreview");
            output.src = reader.result;
        };
        reader.readAsDataURL(file);
    }
}

// Handle Form Submission for Updating User
document.getElementById("saveChangesButton").addEventListener("click", function () {
    const formData = new FormData();
    formData.append("userId", document.getElementById("userId").value);
    formData.append("firstName", document.getElementById("updateFirstName").value);
    formData.append("lastName", document.getElementById("updateLastName").value);
    formData.append("username", document.getElementById("updateUsername").value);
    formData.append("status", document.getElementById("updateStatus").value);

    const pictureInput = document.getElementById("updatePicture");
    if (pictureInput.files[0]) {
        formData.append("picture", pictureInput.files[0]);
    }

    console.log([...formData]); // Debugging: Log the form data

    fetch("update_user.php", {
        method: "POST",
        body: formData,
    })
        .then(response => response.json())
        .then(data => {
            console.log("Response:", data); // Debugging: Log the response
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
        .catch(error => {
            console.error("Error updating user:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An error occurred while updating the user.",
            });
        });
});


// Reset Modal on Close
$('#updateUserModal').on('hidden.bs.modal', function () {
    document.getElementById("userId").value = "";
    document.getElementById("updateFirstName").value = "";
    document.getElementById("updateLastName").value = "";
    document.getElementById("updateUsername").value = "";
    document.getElementById("updateStatus").value = "Active";
    document.getElementById("profilePicturePreview").src = "https://via.placeholder.com/120";
    document.getElementById("userFullName").textContent = "Full Name";
    document.getElementById("oldPassword").value = "";
    document.getElementById("newPassword").value = "";
    document.getElementById("confirmNewPassword").value = "";
});
</script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
            <script src="js/datatables-simple-demo.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
            <script src="js/scripts.js"></script>
        </div>
    </div>
</body>
</html>
