<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;

$id = 77;
$ticket = Ticket::find($id);

if (!$ticket) {
    die("Ticket $id not found.\n");
}

echo "--- BEFORE UPDATE ---\n";
echo "Indikasi: " . ($ticket->indikasi ?? '-') . "\n";
echo "Kendala: " . ($ticket->kendala ?? '-') . "\n";
echo "Solusi: " . ($ticket->solusi ?? '-') . "\n";

// Simulate the Controller update logic
// Controller receives: indikasi, masalah, solusi
// Controller maps: 'kendala' => 'masalah'

$testData = [
    'indikasi' => 'TEST_INDIKASI_' . date('His'),
    'masalah' => 'TEST_MASALAH_' . date('His'), // Should map to kendala
    'solusi' => 'TEST_SOLUSI_' . date('His'),
];

echo "\n--- UPDATING ---\n";
try {
    $ticket->update([
        'indikasi' => $testData['indikasi'],
        'kendala' => $testData['masalah'],
        'solusi' => $testData['solusi'],
    ]);
    echo "Update executed.\n";
} catch (\Exception $e) {
    echo "Update FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

$ticket->refresh();

echo "\n--- AFTER UPDATE ---\n";
echo "Indikasi: " . $ticket->indikasi . "\n";
echo "Kendala: " . $ticket->kendala . "\n";
echo "Solusi: " . $ticket->solusi . "\n";

if (
    $ticket->indikasi === $testData['indikasi'] &&
    $ticket->kendala === $testData['masalah'] &&
    $ticket->solusi === $testData['solusi']
) {
    echo "\n[SUCCESS] Data saved correctly to DB.\n";
} else {
    echo "\n[FAILURE] Data mismatch.\n";
}
