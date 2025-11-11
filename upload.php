<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Enforce role-based access
if ($_SESSION['role'] !== 'Staff') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archiving System</title>
    
    <!-- Bootstrap CSS -->
    <link href="css/all.min.css" rel="stylesheet" />
    <link href="css/33styles.css" rel="stylesheet">
    <link rel="shortcut icon" href="images/ccticon.png">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .upload-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .upload-container h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .upload-container label {
            font-weight: bold;
            color: #555;
        }

        .upload-container input[type="text"],
        .upload-container input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .upload-container input[type="file"] {
            padding: 5px;
        }

        .upload-container .btn-remove {
            color: #ff4d4d;
            cursor: pointer;
            margin-left: 10px;
        }

        .upload-container .btn-remove:hover {
            color: #cc0000;
        }

        .upload-container .btn-success {
            background-color: #28a745;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
        }

        .upload-container .btn-success:hover {
            background-color: #218838;
        }

        #studentList {
            position: absolute;
            z-index: 999;
            width: 37%;
            max-height: 200px;
            overflow-y: auto;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: none;
        }

        #studentList li {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            color: #333;
        }

        #studentList li:hover {
            background-color: #f8f9fa;
        }

        #studentList li:last-child {
            border-bottom: none;
        }

        #documentUploads {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        #documentUploads h5 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .file-input-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .file-input-row label {
            flex: 1;
            margin-right: 10px;
        }

        .file-input-row input {
            flex: 2;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <div>
        <?php include 'topnav.php'; ?>
    </div>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'sidebar.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="upload-container">
                    <h1>Upload Student Documents</h1>

                    <!-- Upload Section -->
                    <form id="uploadForm" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="studentNumber" class="form-label">Student Number</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="studentNumber" 
                                name="studentNumber" 
                                placeholder="Enter Student Number" 
                                required>
                            <ul id="studentList" class="list-group"></ul>
                        </div>
                    </form>

                    <!-- Document Upload Section -->
                    <div id="documentUploads">
                         <!-- <h5>Document Attachments</h5>  -->
                        <div id="documentList"></div>
                    </div>
                </div>
            </main>
            <div>
                <?php include 'footer.php'; ?>
                </div>
        </div>
    </div>

    <script src="js/jquery-3.6.0.min.js"></script>
<script>
    // Define documentTypes globally
    const documentTypes = {
        "NEW STUDENT": ["FORM137", "FORM138", "COA", "GOOD MORAL", "2x2 PICTURE", "BARANGAY CLEARANCE", "BIRTH CERTIFICATE"],
        "TRANSFEREE": ["COA", "TOR", "TRANSFER CREDENTIALS", "GOOD MORAL", "2x2 PICTURE", "APPLICATION ADMISSION", "BARANGAY CLEARANCE", "BIRTH CERTIFICATE"]
    };

    $(document).ready(function () {
        let currentIndex = -1; // Track the current suggestion index
        let suggestions = [];

        // Fetch student details
        $('#studentNumber').on('input', function () {
            const query = $(this).val();

            if (query.length > 1) {
                $.ajax({
                    url: '/NEW/get_students.php',
                    method: 'POST',
                    data: { query: query },
                    success: function (data) {
                        $('#studentList').html(data).show();
                        currentIndex = -1; // Reset index
                        suggestions = $('#studentList li'); // Cache the suggestions list
                    },
                    error: function () {
                        $('#studentList').hide(); // Hide suggestions if there's an error
                    }
                });
            } else if (query.length === 0) {
                // Hide the document section when input is cleared
                $('#documentUploads').hide();
                $('#documentList').html('');
                $('#studentList').hide();
            } else {
                $('#studentList').hide();
            }
        });

        // Handle arrow keys and Enter key for the suggestion list
        $('#studentNumber').on('keydown', function (e) {
            if ($('#studentList').is(':visible')) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    currentIndex = (currentIndex + 1) % suggestions.length;
                    updateSuggestionHighlight();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentIndex = (currentIndex - 1 + suggestions.length) % suggestions.length;
                    updateSuggestionHighlight();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentIndex > -1) {
                        $(suggestions[currentIndex]).trigger('click');
                    }
                }
            }
        });

        function updateSuggestionHighlight() {
            suggestions.removeClass('active');
            if (currentIndex > -1) {
                $(suggestions[currentIndex]).addClass('active');
            }
        }

        // Populate document list based on status and display student information
        $(document).on('click', '#studentList li', function () {
            const studentNumber = $(this).data('student-number');
            const studentName = $(this).data('student-name');
            const studentStatus = $(this).data('status');
            const categoryId = $(this).data('category');

            $('#studentNumber').val(studentNumber);
            $('#studentList').hide();

            // Reset the document upload section
            $('#documentUploads').html(`
                <div id="studentInfo" class="mb-4">
                    <h6 style="font-weight: bold; font-size: 1.25rem;">Student Information:</h6>
                    <p style="margin: 0; font-size: 1rem; color: #555;">
                        <span style="font-weight: bold; color: #333;">Name:</span> ${studentName}
                    </p>
                    <p style="margin: 0; font-size: 1rem; color: #555;">
                        <span style="font-weight: bold; color: #333;">Student Number:</span> ${studentNumber}
                    </p>
                    <p style="margin: 0; font-size: 1rem; color: #555;">
                        <span style="font-weight: bold; color: #333;">Status:</span> ${studentStatus}
                    </p>
                </div>
                <h5 style="font-weight: bold; margin-top: 1.5rem;">Document Attachments</h5>
                <div id="documentList"></div>
            `).show();

            if (categoryId == 2) {
                $('#documentUploads').html(`
                    <div class="alert alert-danger text-center fw-bold fs-5" style="padding: 15px;">
                        ALREADY ARCHIVED
                    </div>
                `);
            } else {
                // Fetch uploaded documents for the student
                $.ajax({
                    url: '/NEW/get_uploaded_documents.php',
                    method: 'POST',
                    data: { studentNumber: studentNumber },
                    success: function (response) {
                        const uploadedDocs = JSON.parse(response); // Parse uploaded document data
                        const docs = documentTypes[studentStatus] || [];
                        let documentHTML = '<div><h6>Upload Required Documents:</h6></div>';

                        docs.forEach((doc) => {
                            const uploaded = uploadedDocs.find((d) => d.DocumentType === doc);
                            if (uploaded) {
                                // Show already uploaded documents in a disabled input
                                documentHTML += `
                                    <div class="file-input-row mb-2">
                                        <label class="form-label" style="font-weight: bold; color: #333;">${doc}</label>
                                        <input type="text" class="form-control" value="${uploaded.FileName}" disabled />
                                    </div>
                                `;
                            } else {
                                // Show input for documents not yet uploaded
                                documentHTML += `
                                    <div class="file-input-row mb-2">
                                        <label class="form-label" style="font-weight: bold; color: #333;">${doc}</label>
                                        <input type="file" name="documents[]" accept=".pdf" class="form-control file-input" required>
                                        <span class="btn-remove" title="Remove Attachment" style="cursor: pointer; color: red;">&times;</span>
                                    </div>
                                `;
                            }
                        });

                        documentHTML += `
                            <div class="text-end mt-3">
                                <button id="verifyButton" class="btn btn-success">
                                    <i class="fas fa-check-circle"></i> Verify
                                </button>
                            </div>
                        `;

                        console.log("Updated Document List:", documentHTML); // ✅ See if the document list is being generated
                        setTimeout(() => {
    $('#documentList').html(documentHTML).show();
}, 100);


                    },
                    error: function () {
                        alert("Failed to load documents.");
                    }
                });
            }
        });

        // Ensure only PDF files are selected
        $(document).on('change', '.file-input', function () {
            const file = this.files[0];
            if (file && file.type !== "application/pdf") {
                alert("Only PDF files are allowed.");
                $(this).val(''); // Clear the invalid file
            }
        });

        // Cancel file attachment
        $(document).on('click', '.btn-remove', function () {
            $(this).siblings('input[type="file"]').val(''); // Clear file input value
        });

        // Hide suggestions when clicking outside
        $(document).click(function (event) {
            if (!$(event.target).closest('#studentNumber, #studentList').length) {
                $('#studentList').hide();
            }
        });
    });
</script>




<script>
    $(document).on('click', '#verifyButton', function () {
    const attachedFiles = $('.file-input').map(function () {
        return this.files[0];
    }).get();

    if (attachedFiles.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Files Attached',
            text: 'Please attach files before verifying.',
        });
        return;
    }

    let docIndex = 0;

    function showVerificationModal() {
        const file = attachedFiles[docIndex];

        const modalHtml = `
            <div id="verifyModal" class="modal fade show" tabindex="-1" style="display: block;">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Verify Document (${docIndex + 1} of ${attachedFiles.length})</h5>
                            <button type="button" class="btn-close" id="closeModal"></button>
                        </div>
                        <div class="modal-body">
                            <iframe src="${URL.createObjectURL(file)}" width="100%" height="500px"></iframe>
                        </div>
                        <div class="modal-footer">
                            <button id="nextDoc" class="btn btn-primary">Next</button>
                            <button id="confirmUpload" class="btn btn-success">Upload</button>
                            <button id="cancelUpload" class="btn btn-danger">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);

        $('#nextDoc').toggle(docIndex < attachedFiles.length - 1);

        $('#nextDoc').click(function () {
            docIndex++;
            $('#verifyModal').remove();
            showVerificationModal();
        });

        $('#cancelUpload, #closeModal').click(function () {
            $('#verifyModal').remove();
            Swal.fire({
                icon: 'info',
                title: 'Verification Cancelled',
                text: 'You can modify or replace files before verifying again.',
            });
        });

        $('#confirmUpload').click(function () {
            Swal.fire({
                icon: 'question',
                title: 'Are you sure?',
                text: 'Do you want to upload all documents?',
                showCancelButton: true,
                confirmButtonText: 'Yes, Upload',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('StudentNumber', $('#studentNumber').val());
                    formData.append('Status', $('#studentInfo').find('p:nth-child(3)').text().split(': ')[1]);

                    $('.file-input').each(function (index, input) {
                        if (input.files.length > 0) {
                            formData.append('documents[]', input.files[0]);
                            formData.append('DocumentType[]', $(this).prev('label').text());
                        }
                    });

                    $.ajax({
                        url: '/NEW/upload_documents.php',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;

                            if (result.success) {
                                $('#verifyModal').remove(); // Ensure modal is completely removed

                                if (result.shouldArchive) {
                                    Swal.fire({
                                        icon: 'info',
                                        title: 'This student will be archived',
                                        text: 'All required documents have been uploaded.',
                                    }).then(() => {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Upload Successful',
                                            text: 'Documents uploaded successfully!',
                                        }).then(() => {
                                            // Refresh the document list for the current student
                                            const studentNumber = $('#studentNumber').val();
                                            $('#studentList li[data-student-number="' + studentNumber + '"]').trigger('click');
                                        });
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Upload Successful',
                                        text: 'Documents uploaded successfully!',
                                    }).then(() => {
                                        // Refresh the document list for the current student
                                        const studentNumber = $('#studentNumber').val();
                                        $('#studentList li[data-student-number="' + studentNumber + '"]').trigger('click');
                                    });
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: result.message || 'An error occurred during upload.',
                                });
                            }
                        },
                        error: function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Upload Failed',
                                text: 'An error occurred during upload. Please try again.',
                            });
                        },
                    });
                }
            });
        });
    }

    showVerificationModal();
});
</script>








<!-- SweetAlert2 -->
<script src="js/sweetalert.js" defer></script>

<!-- Custom Scripts -->
<script src="js/scripts.js" defer></script>
<!-- Bootstrap -->
<script src="js/bootsrap.js" defer></script>
</body>
</html>
