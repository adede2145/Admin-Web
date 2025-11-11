<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class EmployeePhotoController extends Controller
{
    /**
     * Supported image extensions
     */
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

    /**
     * Photo directory path
     */
    private const PHOTO_PATH = 'private/employees/photos';

    /**
     * Compression settings
     */
    private const COMPRESSED_QUALITY = 70; // Blob compression quality (0-100)
    private const MAX_WIDTH = 800; // Maximum width for compressed blob

    /**
     * Display the employee photo (compressed blob - optimized for kiosk)
     *
     * @param int $employeeId
     * @return \Illuminate\Http\Response
     */
    public function show($employeeId)
    {
        try {
            $employee = Employee::find($employeeId);

            // Return compressed blob from database
            if ($employee && $employee->photo_data) {
                $mimeType = $employee->photo_content_type ?? 'image/jpeg';
                
                return Response::make($employee->photo_data, 200, [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'public, max-age=86400', // Cache for 24 hours
                    'Expires' => gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT',
                    'Pragma' => 'public',
                ]);
            }

            // No photo found
            Log::info("Employee photo not found for ID: {$employeeId}");
            return $this->returnDefaultPhoto();
        } catch (\Exception $e) {
            Log::error("Error serving employee photo for ID {$employeeId}: " . $e->getMessage());
            return $this->returnDefaultPhoto();
        }
    }

    /**
     * Display the original photo (from file - high quality for admin)
     *
     * @param int $employeeId
     * @return \Illuminate\Http\Response
     */
    public function showOriginal($employeeId)
    {
        // Since we're only storing blobs now, return the same compressed image
        // Or you can remove this endpoint entirely
        return $this->show($employeeId);
    }

    /**
     * Upload and store employee photo (blob only - no file storage)
     *
     * @param Request $request
     * @param int $employeeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request, $employeeId)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240' // 10MB max
        ]);

        try {
            $employee = Employee::findOrFail($employeeId);
            $photo = $request->file('photo');

            // Delete old photo file if it exists (for migration from old system)
            if ($employee->photo_path && Storage::exists($employee->photo_path)) {
                Storage::delete($employee->photo_path);
            }

            // Create compressed blob only
            $compressedBlob = $this->createCompressedBlob($photo);

            // Update employee record (no path, only blob)
            $employee->update([
                'photo_path' => null, // No file storage
                'photo_data' => $compressedBlob,
                'photo_content_type' => 'image/jpeg'
            ]);

            Log::info("Photo uploaded successfully for employee ID: {$employeeId}");

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'photo_url' => url("/api/employee-photos/{$employeeId}")
            ]);
        } catch (\Exception $e) {
            Log::error("Error uploading employee photo for ID {$employeeId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create compressed image blob for database storage
     *
     * @param \Illuminate\Http\UploadedFile $photo
     * @return string
     */
    private function createCompressedBlob($photo)
    {
        // Create ImageManager with GD driver
        $manager = new ImageManager(new Driver());
        
        // Read the image
        $image = $manager->read($photo);
        
        // Resize if image is too large
        if ($image->width() > self::MAX_WIDTH) {
            $image->scale(width: self::MAX_WIDTH);
        }

        // Compress and encode as JPEG
        return $image->toJpeg(quality: self::COMPRESSED_QUALITY)->toString();
    }

    /**
     * Find employee photo with supported extensions
     *
     * @param int $employeeId
     * @return string|null
     */
    private function findEmployeePhoto($employeeId)
    {
        foreach (self::SUPPORTED_EXTENSIONS as $extension) {
            $photoPath = self::PHOTO_PATH . "/employee_{$employeeId}.{$extension}";
            
            if (Storage::exists($photoPath)) {
                return $photoPath;
            }
        }

        return null;
    }

    /**
     * Get MIME type based on file extension
     *
     * @param string $filePath
     * @return string
     */
    private function getMimeType($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];

        return $mimeTypes[$extension] ?? 'image/jpeg';
    }

    /**
     * Return a default placeholder photo or 404
     *
     * @return \Illuminate\Http\Response
     */
    private function returnDefaultPhoto()
    {
        // Option 1: Return 404
        abort(404, 'Employee photo not found');

        // Option 2: Return a default placeholder image (uncomment if you have one)
        // $defaultPhotoPath = 'private/employees/photos/default.jpg';
        // if (Storage::exists($defaultPhotoPath)) {
        //     $file = Storage::get($defaultPhotoPath);
        //     return Response::make($file, 200, [
        //         'Content-Type' => 'image/jpeg',
        //         'Cache-Control' => 'public, max-age=86400',
        //     ]);
        // }
        
        // abort(404, 'Employee photo not found');
    }
}
