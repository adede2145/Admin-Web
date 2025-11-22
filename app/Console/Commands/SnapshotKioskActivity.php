<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kiosk;
use App\Models\KioskHeartbeat;
use Carbon\Carbon;

class SnapshotKioskActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kiosk:snapshot-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create daily snapshot of active kiosks for historical data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now('Asia/Manila');
        $snapshotTime = $now->copy()->setTime(23, 59, 0); // End of day snapshot
        
        // Get all online kiosks (seen within last 5 minutes)
        $onlineKiosks = Kiosk::online()->get();
        
        if ($onlineKiosks->isEmpty()) {
            $this->info('No online kiosks to snapshot.');
            return 0;
        }
        
        $count = 0;
        foreach ($onlineKiosks as $kiosk) {
            try {
                KioskHeartbeat::create([
                    'kiosk_id' => $kiosk->kiosk_id,
                    'last_seen' => $snapshotTime->toDateTimeString(),
                    'location' => $kiosk->location
                ]);
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to snapshot kiosk {$kiosk->kiosk_id}: " . $e->getMessage());
            }
        }
        
        $this->info("Successfully snapshotted {$count} active kiosk(s) for {$now->toDateString()}");
        return 0;
    }
}
