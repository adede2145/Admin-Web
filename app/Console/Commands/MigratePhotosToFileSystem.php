<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MigratePhotosToFileSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate employee photos from database BLOBs to file system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting photo migration from database to file system...');
        
        // Get all employees with photo_data (BLOBs)
        $employees = Employee::whereNotNull('photo_data')->get();
        $total = $employees->count();
        
        if ($total === 0) {
            $this->info('No photos to migrate.');
            return 0;
        }
        
        $this->info("Found {$total} employees with photos to migrate.");
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        $success = 0;
        $failed = 0;
        $totalSaved = 0;
        
        foreach ($employees as $employee) {
            try {
                $originalSize = strlen($employee->photo_data);
                
                // Create ImageManager instance with GD driver
                $manager = new ImageManager(new Driver());
                
                // Create image from BLOB data
                $image = $manager->read($employee->photo_data);
                
                // Resize if needed
                if ($image->width() > 800 || $image->height() > 800) {
                    $image->scale(width: 800, height: 800);
                }
                
                // Save full-size with compression
                $filename = 'employee_' . $employee->employee_id . '.jpg';
                $fullPath = storage_path('app/private/employees/photos/' . $filename);
                $image->toJpeg(quality: 75)->save($fullPath);
                
                // Create thumbnail
                $thumbnail = $manager->read($employee->photo_data);
                $thumbnail->cover(40, 40);
                $thumbPath = storage_path('app/private/employees/photos/thumbs/' . $filename);
                $thumbnail->toJpeg(quality: 70)->save($thumbPath);
                
                $compressedSize = filesize($fullPath);
                $savedBytes = $originalSize - $compressedSize;
                $totalSaved += $savedBytes;
                
                // Update database
                $employee->update([
                    'photo_path' => $filename,
                    'photo_content_type' => 'image/jpeg',
                    'photo_data' => null  // Clear BLOB
                ]);
                
                $success++;
                
            } catch (\Exception $e) {
                $this->error("\nFailed to migrate photo for employee {$employee->employee_id}: " . $e->getMessage());
                Log::error('Photo migration failed', [
                    'employee_id' => $employee->employee_id,
                    'error' => $e->getMessage()
                ]);
                $failed++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Migration complete!");
        $this->info("Successful: {$success}");
        $this->info("Failed: {$failed}");
        $this->info("Total space saved: " . $this->formatBytes($totalSaved));
        
        if ($success > 0) {
            // Optimize database to reclaim space
            $this->info("\nOptimizing database table to reclaim disk space...");
            try {
                DB::statement('OPTIMIZE TABLE employees');
                $this->info("Database optimized! Disk space has been reclaimed.");
            } catch (\Exception $e) {
                $this->warn("Could not optimize table: " . $e->getMessage());
            }
        }
        
        return 0;
    }
    
    /**
     * Format bytes to human-readable string
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
