<?php
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel via artisan
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// First, delete existing backfill data to start fresh
DB::table('kiosk_heartbeats')->whereBetween('created_at', [
    Carbon::parse('2025-11-13')->startOfDay('Asia/Manila')->setTimezone('UTC')->toDateTimeString(),
    Carbon::parse('2025-11-20')->endOfDay('Asia/Manila')->setTimezone('UTC')->toDateTimeString()
])->delete();

echo "✓ Cleared existing heartbeat records" . PHP_EOL;

$inserted = 0;

// Nov 13-18: Mostly kiosk_id 7 (IT Office) active, except Nov 14 none and Nov 15 kiosk_id 1
for ($d = Carbon::parse('2025-11-13'); $d->lte(Carbon::parse('2025-11-18')); $d->addDay()) {
    // According to attendance logs, there were 0 running kiosks on 2025-11-14 — skip that day
    if ($d->toDateString() === '2025-11-14') {
        continue;
    }

    // Nov 15: kiosk_id 1 is active; other days (13,16,17,18) kiosk_id 7 is active
    if ($d->toDateString() === '2025-11-15') {
        $kioskId = 1;
        $location = 'IT Department - Ground Floor';
    } else {
        $kioskId = 7;
        $location = 'IT Office';
    }

    DB::table('kiosk_heartbeats')->insert([
        'kiosk_id' => $kioskId,
        'last_seen' => $d->copy()->toDateTimeString(),
        'location' => $location,
        'created_at' => $d->copy()->startOfDay('Asia/Manila')->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
    $inserted++;
}

echo "✓ Nov 13-18: Inserted 5 rows (kiosk_id 7 active except 2025-11-14)" . PHP_EOL;

// Nov 18: Both kiosks active (1 & 7)
DB::table('kiosk_heartbeats')->insert([
    'kiosk_id' => 1,
    'last_seen' => Carbon::parse('2025-11-18')->toDateTimeString(),
    'location' => 'IT Department - Ground Floor',
    'created_at' => Carbon::parse('2025-11-18')->startOfDay('Asia/Manila')->toDateTimeString(),
    'updated_at' => now()->toDateTimeString(),
]);
$inserted++;

echo "✓ Nov 18: Added kiosk_id 1 (now 2 kiosks active)" . PHP_EOL;

// Nov 19: Only kiosk_id 7 active again
DB::table('kiosk_heartbeats')->insert([
    'kiosk_id' => 7,
    'last_seen' => Carbon::parse('2025-11-19')->toDateTimeString(),
    'location' => 'IT Office',
    'created_at' => Carbon::parse('2025-11-19')->startOfDay('Asia/Manila')->toDateTimeString(),
    'updated_at' => now()->toDateTimeString(),
]);
$inserted++;

echo "✓ Nov 19: Only kiosk_id 7 active (1 kiosk active)" . PHP_EOL;

// Nov 20: Both kiosks active (1 & 7)
DB::table('kiosk_heartbeats')->insert([
    'kiosk_id' => 1,
    'last_seen' => Carbon::parse('2025-11-20')->toDateTimeString(),
    'location' => 'IT Department - Ground Floor',
    'created_at' => Carbon::parse('2025-11-20')->startOfDay('Asia/Manila')->toDateTimeString(),
    'updated_at' => now()->toDateTimeString(),
]);
DB::table('kiosk_heartbeats')->insert([
    'kiosk_id' => 7,
    'last_seen' => Carbon::parse('2025-11-20')->toDateTimeString(),
    'location' => 'IT Office',
    'created_at' => Carbon::parse('2025-11-20')->startOfDay('Asia/Manila')->toDateTimeString(),
    'updated_at' => now()->toDateTimeString(),
]);
$inserted += 2;

echo "✓ Nov 20: Both kiosks active (2 kiosks active)" . PHP_EOL;

echo "\n=== SUMMARY ===" . PHP_EOL;
echo "Total rows inserted: $inserted" . PHP_EOL;
echo "Nov 13: 1 active (kiosk_id 7)" . PHP_EOL;
echo "Nov 14: 0 active" . PHP_EOL;
echo "Nov 15-17: 1 active each (kiosk_id 7)" . PHP_EOL;
echo "Nov 18: 2 active (kiosk_id 1 + 7)" . PHP_EOL;
echo "Nov 19: 1 active (kiosk_id 7)" . PHP_EOL;
echo "Nov 20: 2 active (kiosk_id 1 + 7)" . PHP_EOL;
