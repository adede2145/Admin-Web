<?php
// Test script to check if employee photos can be accessed

$employeeId = 1;
$storagePath = __DIR__ . '/../storage/app/private/employees/photos';

echo "=== Employee Photo API Debug ===\n\n";

// Check if storage directory exists
echo "1. Storage Directory Check:\n";
echo "   Path: $storagePath\n";
echo "   Exists: " . (is_dir($storagePath) ? "YES" : "NO") . "\n\n";

// List files in directory
echo "2. Files in directory:\n";
if (is_dir($storagePath)) {
    $files = scandir($storagePath);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $storagePath . '/' . $file;
            if (is_file($filePath)) {
                echo "   - $file (" . filesize($filePath) . " bytes)\n";
            } elseif (is_dir($filePath)) {
                echo "   - $file/ (directory)\n";
            }
        }
    }
} else {
    echo "   Directory does not exist!\n";
}

// Check for specific employee photo
echo "\n3. Looking for employee_{$employeeId}.*:\n";
$extensions = ['jpg', 'jpeg', 'png', 'gif'];
foreach ($extensions as $ext) {
    $photoPath = $storagePath . "/employee_{$employeeId}.{$ext}";
    if (file_exists($photoPath)) {
        echo "   FOUND: employee_{$employeeId}.{$ext} (" . filesize($photoPath) . " bytes)\n";
        echo "   Readable: " . (is_readable($photoPath) ? "YES" : "NO") . "\n";
    }
}

// Test Laravel Storage facade
echo "\n4. Testing Laravel Storage:\n";
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Support\Facades\Storage;

foreach ($extensions as $ext) {
    $path = "private/employees/photos/employee_{$employeeId}.{$ext}";
    if (Storage::exists($path)) {
        echo "   Storage::exists('$path'): YES\n";
        echo "   File size: " . Storage::size($path) . " bytes\n";
        echo "   MIME type: " . Storage::mimeType($path) . "\n";
        break;
    }
}

echo "\n=== End Debug ===\n";
