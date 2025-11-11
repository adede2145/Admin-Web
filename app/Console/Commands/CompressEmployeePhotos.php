<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CompressEmployeePhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:compress {--force : Force recompression even if blob exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compress existing employee photos and store as blobs in database';

    /**
     * Compression settings
     */
    private const COMPRESSED_QUALITY = 70;
    private const MAX_WIDTH = 800;
    private const PHOTO_PATH = 'private/employees/photos';
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $this->info('Starting employee photo compression...');
        
        // Get all employees
        $employees = Employee::all();
        $totalEmployees = $employees->count();
        
        $this->info("Found {$totalEmployees} employees");
        
        $bar = $this->output->createProgressBar($totalEmployees);
        $bar->start();
        
        $compressed = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($employees as $employee) {
            try {
                // Skip if blob already exists and not forcing
                if (!$force && $employee->photo_data) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Try to find photo file
                $photoPath = $this->findEmployeePhoto($employee->employee_id);
                
                if (!$photoPath) {
                    // Try photo_path from database
                    if ($employee->photo_path && Storage::exists($employee->photo_path)) {
                        $photoPath = $employee->photo_path;
                    } else {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }
                }
                
                // Compress and store blob
                $compressedBlob = $this->createCompressedBlob($photoPath);
                
                $employee->update([
                    'photo_path' => $photoPath,
                    'photo_data' => $compressedBlob,
                    'photo_content_type' => 'image/jpeg'
                ]);
                
                $compressed++;
            } catch (\Exception $e) {
                $this->error("\nError compressing photo for employee {$employee->employee_id}: " . $e->getMessage());
                $errors++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->newLine(2);
        $this->info("Compression complete!");
        $this->info("Compressed: {$compressed}");
        $this->info("Skipped: {$skipped}");
        $this->info("Errors: {$errors}");
        
        return Command::SUCCESS;
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
     * Create compressed image blob
     *
     * @param string $photoPath
     * @return string
     */
    private function createCompressedBlob($photoPath)
    {
        $manager = new ImageManager(new Driver());
        
        // Read the image from storage
        $imageData = Storage::get($photoPath);
        $image = $manager->read($imageData);
        
        // Resize if image is too large
        if ($image->width() > self::MAX_WIDTH) {
            $image->scale(width: self::MAX_WIDTH);
        }
        
        // Compress and encode as JPEG
        return $image->toJpeg(quality: self::COMPRESSED_QUALITY)->toString();
    }
}
