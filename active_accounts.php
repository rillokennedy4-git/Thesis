<?php
session_start();

// Check user authentication and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'System Admin') {
    header("Location: index.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

// Query to fetch active accounts excluding System Admin
$sql = "SELECT User_Id, First_Name, Last_Name, Username, Role, Status, Create_At, Picture 
        FROM tbl_user 
        WHERE Status = 'Active' AND Role != 'System Admin'";
$result = $conn->query($sql);

// Check if query executed successfully
if (!$result) {
    die("Error fetching data: " . $conn->error);
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
    <div>
        <?php include 'topnav2.php'; ?>
    </div>

    <div>
        <main>
            <div class="container-fluid px-4 mt-4">
                <br><br><br><br>
                <h1 class="mt-4">Active Accounts</h1>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Create Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($row['Picture']) ? 'upload/profile_pictures/' . $row['Picture'] : 'https://via.placeholder.com/40'; ?>" 
                                                 class="rounded-circle mr-2" style="width: 40px; height: 40px;">
                                            <strong><?php echo $row['First_Name'] . ' ' . $row['Last_Name']; ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo ucfirst($row['Role']); ?></td>
                                    <td><?php echo date("M d, Y", strtotime($row['Create_At'])); ?></td>
                                    <td>
                                        <a class="btn btn-primary btn-sm" href="javascript:void(0);" onclick="openUpdateModal(<?php echo $row['User_Id']; ?>);">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No active accounts found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Update modal-->
    <div class="modal fade" id="updateUserModal" tabindex="-1" role="dialog" aria-labelledby="updateUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateUserModalLabel">Edit Account</h5>
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
                                
                                <div class="custom-file-upload">
                                    <label for="updatePicture" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-camera"></i> Upload Picture
                                    </label>
                                    <input type="file" id="updatePicture" name="picture" accept="image/*" onchange="previewProfilePicture(event)" style="display: none;">
                                </div>
                            </div>
                            <strong id="userFullName">Full Name</strong>
                        </div>
                        <!-- Right Side: User Details and Password Change -->
                        <div class="col-md-8">
                            <!-- User Information Form -->
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
                                <button id="changePasswordBtn" class="btn btn-danger mt-3" onclick="togglePasswordChange()">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                            <!-- Password Change Form -->
                            <div id="passwordChangeForm" style="display: none;">
                                <div class="form-group position-relative">
                                    <label for="newPassword">New Password</label>
                                    <input type="password" class="form-control" id="newPassword" name="newPassword" style="padding-right: 40px;">
                                    <button type="button" class="btn btn-sm position-absolute" onclick="togglePasswordVisibility('newPassword', this)" 
                                            style="right: 10px; top: 70%; transform: translateY(-50%); background: none; border: none;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-group position-relative">
                                    <label for="confirmNewPassword">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirmNewPassword" name="confirmNewPassword" style="padding-right: 40px;">
                                    <button type="button" class="btn btn-sm position-absolute" onclick="togglePasswordVisibility('confirmNewPassword', this)" 
                                            style="right: 10px; top: 70%; transform: translateY(-50%); background: none; border: none;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <button class="btn btn-secondary mt-3" onclick="togglePasswordChange()">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Admin Password Modal -->
                <div class="modal fade" id="adminPasswordModal" tabindex="-1" aria-labelledby="adminPasswordModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="adminPasswordModalLabel">System Admin Password Required</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Please enter the System Admin's password to Deactivate this account.</p>
                                <div class="form-group position-relative">
                                    <label for="adminPasswordInput">System Admin Password</label>
                                    <input type="password" class="form-control" id="adminPasswordInput" placeholder="Enter System Admin Password" style="padding-right: 40px;">
                                    <button type="button" class="btn btn-sm position-absolute" onclick="togglePasswordVisibility('adminPasswordInput', this)" 
                                            style="right: 10px; top: 70%; transform: translateY(-50%); background: none; border: none;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-danger" id="adminPasswordError" style="display: none;">Password is required.</small>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" id="saveChangesButton1">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveChangesButton">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to toggle password visibility
        function togglePasswordVisibility(inputId, toggleButton) {
            const input = document.getElementById(inputId);
            const icon = toggleButton.querySelector("i");

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>

    <script>
        function togglePasswordChange() {
            const passwordChangeForm = document.getElementById('passwordChangeForm');
            const changePasswordBtn = document.getElementById('changePasswordBtn');
            
            // Toggle visibility
            if (passwordChangeForm.style.display === 'none') {
                passwordChangeForm.style.display = 'block';
                changePasswordBtn.style.display = 'none'; // Hide the "Change Password" button
            } else {
                passwordChangeForm.style.display = 'none';
                changePasswordBtn.style.display = 'block'; // Show the "Change Password" button
            }
        }

        function togglePasswordVisibility(inputId, toggleButton) {
            const input = document.getElementById(inputId);
            const icon = toggleButton.querySelector("i");

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>

    <script>
        function openUpdateModal(userId) {
            fetch(`update_user.php?id=${userId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Failed to fetch user data.");
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === "success") {
                        document.getElementById("userId").value = data.user.User_Id;
                        document.getElementById("updateFirstName").value = data.user.First_Name;
                        document.getElementById("updateLastName").value = data.user.Last_Name;
                        document.getElementById("updateUsername").value = data.user.Username;
                        document.getElementById("updateStatus").value = data.user.Status;

                        const profilePicturePreview = document.getElementById("profilePicturePreview");
                        profilePicturePreview.src = data.user.Picture || "https://via.placeholder.com/120";

                        document.getElementById("userFullName").textContent = `${data.user.First_Name} ${data.user.Last_Name}`;

                        // Show the modal
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
    </script>

    <script>
        // Function to toggle between User Info and Change Password
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

        // Function to preview profile picture
        function previewProfilePicture(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('profilePicturePreview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        // Function to fetch and populate modal data
        function openUpdateModal(userId) {
            fetch(`update_user.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        // Populate the modal fields with fetched data
                        document.getElementById("userId").value = data.user.User_Id;

                        const updateFirstName = document.getElementById("updateFirstName");
                        updateFirstName.value = data.user.First_Name;
                        updateFirstName.dataset.initialValue = data.user.First_Name;

                        const updateLastName = document.getElementById("updateLastName");
                        updateLastName.value = data.user.Last_Name;
                        updateLastName.dataset.initialValue = data.user.Last_Name;

                        const updateUsername = document.getElementById("updateUsername");
                        updateUsername.value = data.user.Username;
                        updateUsername.dataset.initialValue = data.user.Username;

                        const updateStatus = document.getElementById("updateStatus");
                        updateStatus.value = data.user.Status;
                        updateStatus.dataset.initialValue = data.user.Status;

                        const profilePicturePreview = document.getElementById("profilePicturePreview");
                        profilePicturePreview.src = data.user.Picture || "https://via.placeholder.com/120";

                        document.getElementById("userFullName").textContent = `${data.user.First_Name} ${data.user.Last_Name}`;

                        // Show the modal
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

        document.getElementById("saveChangesButton").addEventListener("click", function () {
    console.log("Save Changes button clicked"); // Debugging

    const userId = document.getElementById("userId").value.trim();
    const firstName = document.getElementById("updateFirstName").value.trim();
    const lastName = document.getElementById("updateLastName").value.trim();
    const username = document.getElementById("updateUsername").value.trim();
    const status = document.getElementById("updateStatus").value; // Get the status value
    const newPassword = document.getElementById("newPassword").value.trim();
    const confirmNewPassword = document.getElementById("confirmNewPassword").value.trim();
    const pictureInput = document.getElementById("updatePicture").files[0];

    console.log("Form data collected"); // Debugging

    // Initial value checks
    const initialFirstName = document.getElementById("updateFirstName").dataset.initialValue;
    const initialLastName = document.getElementById("updateLastName").dataset.initialValue;
    const initialUsername = document.getElementById("updateUsername").dataset.initialValue;
    const initialStatus = document.getElementById("updateStatus").dataset.initialValue;

    if (
        firstName === initialFirstName &&
        lastName === initialLastName &&
        username === initialUsername &&
        status === initialStatus &&
        !newPassword &&
        !pictureInput
    ) {
        Swal.fire({
            icon: "info",
            title: "No Changes",
            text: "You haven't made any changes.",
            timer: 2000,
            showConfirmButton: false,
        });
        return;
    }

    // Validate new password (if provided)
    if (newPassword) {
        // Check if the new password is at least 8 characters long
        if (newPassword.length < 8) {
            Swal.fire({
                icon: "error",
                title: "Invalid Password",
                text: "Password must be at least 8 characters long.",
            });
            return;
        }

        // Check if the new password and confirm password match
        if (newPassword !== confirmNewPassword) {
            Swal.fire({
                icon: "error",
                title: "Password Mismatch",
                text: "The new password and confirm password do not match.",
            });
            return;
        }
    }

    const formData = new FormData();
    formData.append("userId", userId);
    formData.append("firstName", firstName);
    formData.append("lastName", lastName);
    formData.append("username", username);
    formData.append("status", status);
    if (newPassword) {
        formData.append("newPassword", newPassword);
    }
    if (pictureInput) {
        formData.append("picture", pictureInput);
    }

    // Check if the status is being set to "Inactive"
    if (status === "Inactive") {
        // Show the admin password modal
        const adminPasswordModal = new bootstrap.Modal(document.getElementById("adminPasswordModal"));
        adminPasswordModal.show();

        // Handle admin password submission
        document.getElementById("saveChangesButton1").addEventListener("click", function () {
            const adminPassword = document.getElementById("adminPasswordInput").value.trim();

            if (!adminPassword) {
                document.getElementById("adminPasswordError").style.display = "block";
                return;
            }

            // Hide error and modal, add admin password to formData
            document.getElementById("adminPasswordError").style.display = "none";
            formData.append("adminPassword", adminPassword);
            adminPasswordModal.hide();

            // Submit form data after adding admin password
            submitFormData(formData);
        });

        return; // Wait for admin password before submitting the form
    }

    // If status is not "Inactive", submit the form data directly
    console.log("Submitting form data"); // Debugging
    submitFormData(formData);
});
        // Keep only this function
        function submitFormData(formData) {
            console.log("Submitting form data to the server..."); // Debugging

            fetch("update_user.php", {
                method: "POST",
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    console.log("Server response:", data); // Debugging

                    if (data.status === "success") {
                        Swal.fire({
                            icon: "success",
                            title: "Saved",
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false,
                        }).then(() => {
                            location.reload();
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
                    console.error("Error submitting form:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "An unexpected error occurred while saving changes.",
                    });
                });
        }

        document.getElementById("submitAdminPassword").addEventListener("click", function () {
            const adminPassword = document.getElementById("adminPasswordInput").value;

            if (!adminPassword) {
                document.getElementById("adminPasswordError").style.display = "block";
                return;
            }

            // Hide error and modal, process form data
            document.getElementById("adminPasswordError").style.display = "none";
            const adminPasswordModal = bootstrap.Modal.getInstance(document.getElementById("adminPasswordModal"));
            adminPasswordModal.hide();

            // Add the admin password to formData and submit
            const formData = new FormData();
            formData.append("adminPassword", adminPassword);

            // Continue with the rest of your form submission logic here
        });

        // Close the modal when Cancel or X is clicked
        document.querySelectorAll('[data-bs-dismiss="modal"]').forEach((button) => {
            button.addEventListener("click", () => {
                const adminPasswordModal = bootstrap.Modal.getInstance(document.getElementById("adminPasswordModal"));
                if (adminPasswordModal) {
                    adminPasswordModal.hide();
                }
            });
        });
    </script>

    <!-- SweetAlert2 -->
    <script src="js/sweetalert.js" defer></script>

    <!-- Bootstrap -->
    <script src="js/bootsrap.js" defer></script>
    <script src="js/jquery-3.5.1.min.js"></script>
</body>
</html>
