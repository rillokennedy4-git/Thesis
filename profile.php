<?php
session_start();

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/NEW/database/db_connection.php");

// Process profile update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['userId'] ?? null;

    if (!$userId) {
        echo json_encode(['status' => 'error', 'message' => 'User ID is missing.']);
        exit();
    }

    // Initialize response
    $response = ['status' => 'error', 'message' => 'Invalid request.'];

    // Handle profile picture update
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'upload/profile_pictures/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
        }
        $fileName = uniqid() . '-' . basename($_FILES['picture']['name']);
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['picture']['tmp_name'], $uploadFile)) {
            $query = "UPDATE tbl_user SET Picture = ? WHERE User_Id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $fileName, $userId);

            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Profile picture updated successfully.'];
            } else {
                $response['message'] = 'Failed to update profile picture.';
            }

            $stmt->close();
            echo json_encode($response);
            exit();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload profile picture.']);
            exit();
        }
    }

    // Handle individual field updates
    $fieldMap = [
        'firstName' => 'First_Name',
        'lastName' => 'Last_Name',
        'username' => 'Username',
        'newPassword' => 'Password'
    ];

    foreach ($fieldMap as $postField => $dbColumn) {
        if (isset($_POST[$postField])) {
            $value = $_POST[$postField];

            // Special case for password hashing
            if ($postField === 'newPassword') {
                $value = password_hash($value, PASSWORD_BCRYPT);
            }

            $query = "UPDATE tbl_user SET $dbColumn = ? WHERE User_Id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $value, $userId);

            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => ucfirst($postField) . ' updated successfully.'];
            } else {
                $response['message'] = 'Failed to update ' . $postField . '.';
            }

            $stmt->close();
            echo json_encode($response);
            exit();
        }
    }

    // If no recognized fields are sent
    echo json_encode($response);
    exit();
}

// Fetch user information for display
$userId = $_SESSION['user_id'];
$query = "SELECT User_Id, First_Name, Last_Name, Username, Role, Picture FROM tbl_user WHERE User_Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) {
    die("User not found.");
}

// Full name for display
$userFullName = htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archiving System</title>
    <link href="css/33styles.css" rel="stylesheet" />
    <link href="css/all.min.css" rel="stylesheet" />
    <link rel="shortcut icon" href="images/ccticon.png">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .profile-page {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .profile-container {
            flex: 1;
            max-width: 30%;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .edit-profile-container {
            flex: 2;
            max-width: 65%;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #6c757d;
        }

        .btn-edit-profile {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="sb-nav-fixed">
        <div >
            <?php include 'topnav2.php'; ?>
        </div>

        <div >
            <main class="container mt-4">
                <div class="container-fluid px-4">
                    <br><br><br>
                        <h1 class="mt-4">PROFILE</h1>

                <div class="profile-page">
                    <!-- Profile Section -->
                    <div class="profile-container">
                        <img src="<?php echo !empty($user['Picture']) ? 'upload/profile_pictures/' . $user['Picture'] : 'https://via.placeholder.com/150'; ?>" 
                            alt="Profile Picture" 
                            class="profile-picture">
                        <h2 class="mt-3"><?php echo $userFullName; ?></h2>
                        <h5 class="text-muted"><?php echo htmlspecialchars($user['Role']); ?></h5>
                    </div>

            <!-- Edit Profile Section -->
            <div class="edit-profile-container">
                <form id="editProfileForm" enctype="multipart/form-data">
            <input type="hidden" name="userId" id="userId" value="<?php echo $user['User_Id']; ?>">
            <div class="row">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm" enctype="multipart/form-data">
                    <input type="hidden" name="userId" value="<?php echo $user['User_Id']; ?>">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div>
                                <img id="profilePicturePreview" 
                                     src="<?php echo !empty($user['Picture']) ? 'upload/profile_pictures/' . $user['Picture'] : 'https://via.placeholder.com/150'; ?>" 
                                     class="rounded-circle border mb-3"
                                     style="width: 120px; height: 120px; object-fit: cover;">
                                <input type="file" id="profilePictureInput" name="picture" class="form-control" accept="image/*" onchange="previewProfilePicture(event)">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user['First_Name']); ?>" required>
                            </div>
                            <div class="form-group mt-3">
                                <label for="lastName">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user['Last_Name']); ?>" required>
                            </div>
                            <div class="form-group mt-3">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
                            </div>
                            <div class="form-group mt-3">
                                <label for="newPassword">New Password (Optional)</label>
                                <input type="password" class="form-control" id="newPassword" name="newPassword">
                            </div>
                            <div class="form-group mt-3">
                                <label for="confirmPassword">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveProfileChanges()">Save Changes</button>
            </div>
                </form>
    </main>
    <div>
    <?php include 'footer.php'; ?>

    </div>
    </div>
    </div>

<script>
    function clearDefaultText(input) {
        // Clear the text box if the current value matches the default
        if (input.value === input.defaultValue) {
            input.value = '';
        }
    }

    function restoreDefaultText(input, defaultValue) {
        // Restore the default value if the text box is empty
        if (input.value === '') {
            input.value = defaultValue;
        }
    }
</script>
<script>
    // Preview Profile Picture
    function previewProfilePicture(event) {
        const reader = new FileReader();
        reader.onload = function () {
            document.getElementById('profilePicturePreview').src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    // Function to update the profile picture
    function updateProfilePicture() {
        const pictureInput = document.getElementById('profilePictureInput').files[0];
        const userId = document.getElementById('userId').value;

        if (!pictureInput) {
            Swal.fire({
                icon: 'warning',
                title: 'No Picture Selected',
                text: 'Please select a picture to upload.',
            });
            return;
        }

        Swal.fire({
            title: 'Update Profile Picture?',
            text: 'Are you sure you want to update your profile picture?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, update it!',
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('userId', userId);
                formData.append('picture', pictureInput);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Profile Picture Updated',
                                text: data.message,
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                            });
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    }

    // Function to update a specific field
    function updateField(fieldId, fieldName) {
        const fieldInput = document.getElementById(fieldId);
        const fieldValue = fieldInput.value;
        const defaultValue = fieldInput.defaultValue;

        // Check if the input value is the same as the default value or empty
        if (fieldValue === defaultValue || !fieldValue) {
            Swal.fire({
                icon: 'warning',
                title: 'No New Input',
                text: `Please enter a new ${fieldName} before updating.`,
            });
            return;
        }

        // Validation for username length
        if (fieldId === 'username' && fieldValue.length < 8) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Username must be at least 8 characters long.',
            });
            return;
        }

        Swal.fire({
            title: `Update ${fieldName}?`,
            text: `Are you sure you want to update your ${fieldName}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, update it!',
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('userId', document.getElementById('userId').value);
                formData.append(fieldId, fieldValue);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: `${fieldName} Updated`,
                                text: data.message,
                                confirmButtonText: 'OK',
                            });
                            // Optionally, update the UI dynamically
                            fieldInput.defaultValue = fieldValue;
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                            });
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    }

    // Function to update the password
    function updatePassword() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const userId = document.getElementById('userId').value;

        if (!newPassword || !confirmPassword) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Both password fields are required.',
            });
            return;
        }

        // Validation for password length
        if (newPassword.length < 8) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Password must be at least 8 characters long.',
            });
            return;
        }

        if (newPassword !== confirmPassword) {
            Swal.fire({
                icon: 'error',
                title: 'Password Mismatch',
                text: 'The passwords do not match. Please try again.',
            });
            return;
        }

        Swal.fire({
            title: 'Update Password?',
            text: 'Are you sure you want to update your password?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, update it!',
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('userId', userId);
                formData.append('newPassword', newPassword);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Password Updated',
                                text: data.message,
                                confirmButtonText: 'OK',
                            });
                            // Optionally clear the password fields
                            document.getElementById('newPassword').value = '';
                            document.getElementById('confirmPassword').value = '';
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                            });
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    }
</script>


        <!-- SweetAlert2 -->
<script src="js/sweetalert.js" defer></script>

<!-- Bootstrap -->
<script src="js/bootsrap.js" defer></script>


<!-- Custom Scripts -->
<script src="js/scripts.js" defer></script>

<!-- button -->
<script src="js/jquery-3.6.0.min.js" ></script>
</body>
</html>
