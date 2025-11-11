<?php
session_start();

// Check user authentication and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'System Admin') {
    header("Location: index.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

// Query to fetch archived accounts
$sql = "SELECT User_Id, First_Name, Last_Name, Username, Role, Status, Create_At, Picture 
        FROM tbl_user 
        WHERE Status = 'Inactive'";
$result = $conn->query($sql);
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
        <div>
            <main>
                <div class="container-fluid px-4 mt-4">
                    <br><br><br><br>
                    <h1 class="mt-4">Inactive Accounts</h1>
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
                            <?php if ($result->num_rows > 0): ?>
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
                                            <a href="javascript:void(0);" class="btn btn-warning btn-sm" onclick="openRestoreModal(<?php echo $row['User_Id']; ?>);">Restore</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No archived accounts found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Restore Modal -->
    <div class="modal fade" id="restoreModal" tabindex="-1" role="dialog" aria-labelledby="restoreModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restoreModalLabel">Restore Account</h5>
                    <!-- Remove the 'x' button -->
                </div>
                <div class="modal-body">
                    <p>Enter the System Administrator password to restore this account:</p>
                    <input type="hidden" id="restoreUserId">
                    <div class="form-group" style="position: relative;">
                        <label for="adminPassword">System Administrator Password</label>
                        <input type="password" class="form-control" id="adminPassword" required style="padding-right: 40px;">
                        <span id="togglePassword" style="position: absolute; right: 10px; top: 65%; transform: translateY(-50%); cursor: pointer;">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- Cancel button with data-dismiss attribute -->
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmRestoreButton">Restore</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="js/jquery-3.6.0.min.js"></script>
    <script src="js/bootstrap.bundle.min.js" defer></script>

    <script>
        // Toggle password visibility
        document.getElementById("togglePassword").addEventListener("click", function () {
            const passwordInput = document.getElementById("adminPassword");
            const icon = this.querySelector("i");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        });

        // Restore account functionality
        document.getElementById("confirmRestoreButton").addEventListener("click", function () {
            const userId = document.getElementById("restoreUserId").value;
            const adminPassword = document.getElementById("adminPassword").value;

            if (!adminPassword) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Please enter the System Administrator password.",
                });
                return;
            }

            fetch("restore_account.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ userId, adminPassword }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === "success") {
                        Swal.fire({
                            icon: "success",
                            title: "Restored",
                            text: data.message,
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
                .catch((error) => {
                    console.error("Error restoring account:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "An unexpected error occurred.",
                    });
                });
        });

        // Open the restore modal
        function openRestoreModal(userId) {
            document.getElementById("restoreUserId").value = userId;
            document.getElementById("adminPassword").value = "";
            $('#restoreModal').modal('show');
        }
        document.querySelector('[data-dismiss="modal"]').addEventListener("click", function () {
    $('#restoreModal').modal('hide');
});

    </script>

    <!-- SweetAlert2 -->
    <script src="js/sweetalert.js" defer></script>
</body>
</html>