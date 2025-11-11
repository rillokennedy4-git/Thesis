<div class="d-flex justify-content-between align-items-center">
    <h1 class="mt-4">ADDING FORM</h1>
    <!-- Trigger Modal Button -->
    <button type="button" class="btn btn-success text-white mt-4" data-bs-toggle="modal" data-bs-target="#excelModal">
        UPLOAD
    </button>
</div>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item active">ADD STUDENTS USING THE FORM BELOW</li>
</ol>

<!-- Success Message -->
<?php if (!empty($successMessage)): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '<?php echo htmlspecialchars($successMessage); ?>'
        });
    </script>
<?php endif; ?>

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: '<ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>'
        });
    </script>
<?php endif; ?>

<!-- Loading Screen -->
<div id="loadingScreen" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; text-align: center; color: white; font-size: 24px; padding-top: 20%;">
    Processing... Please wait.
</div>

<!-- Modal Structure for Excel Upload and Download -->
<div class="modal fade" id="excelModal" tabindex="-1" aria-labelledby="excelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="excelModalLabel">Excel Upload and Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Form to Download Excel Template -->
                <form method="POST">
                    <button type="submit" name="download_template" class="btn btn-primary mb-3">Download Excel Template</button>
                </form>

                <!-- Form to Upload Excel File -->
                <form method="POST" enctype="multipart/form-data" id="excelUploadForm">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Upload Excel File</label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" required>
                    </div>
                    <button type="submit" name="upload_excel" class="btn btn-success">Upload Excel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('excelUploadForm').addEventListener('submit', function() {
    // Show loading screen
    document.getElementById('loadingScreen').style.display = 'block';

    // Hide modal
    let modal = bootstrap.Modal.getInstance(document.getElementById('excelModal'));
    modal.hide();
});
</script>
