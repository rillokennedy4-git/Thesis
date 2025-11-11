<?php
session_start();

// Ensure the user is logged in and is a Staff member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'System Admin') {
    header("Location: index.php");
    exit();
}

// Define the path to the archive folder
$archiveFolder = $_SERVER['DOCUMENT_ROOT'] . "/NEW/archive";

// Define the path for the temporary ZIP file
$zipFileName = 'archive_' . date('Y-m-d_H-i-s') . '.zip';
$zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;

// Create a new ZipArchive instance
$zip = new ZipArchive();

if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    // Add all files in the archive folder to the ZIP
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($archiveFolder),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($archiveFolder) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Close the ZIP file
    $zip->close();

    // Set headers for file download
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
    header('Content-Length: ' . filesize($zipFilePath));

    // Read the file and send it to the browser
    readfile($zipFilePath);

    // Delete the temporary ZIP file
    unlink($zipFilePath);
    exit();
} else {
    // Handle ZIP creation error
    die("Failed to create ZIP file.");
}