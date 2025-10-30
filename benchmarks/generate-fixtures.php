<?php

declare(strict_types=1);

/**
 * Fixture Generator for Benchmarks
 *
 * Generates JSON test files of various sizes for performance benchmarking.
 */

// Increase memory limit for large file generation
ini_set('memory_limit', '512M');

// Generate a random user object
function generateUser(int $id): array
{
    return [
        'id' => $id,
        'name' => 'User '.$id,
        'email' => 'user'.$id.'@example.com',
        'age' => rand(18, 80),
        'isActive' => (bool) rand(0, 1),
        'balance' => round(rand(0, 100000) / 100, 2),
        'registeredAt' => date('Y-m-d H:i:s', time() - rand(0, 31536000)),
        'address' => [
            'street' => rand(1, 999).' Main St',
            'city' => 'City '.rand(1, 100),
            'state' => chr(65 + rand(0, 25)).chr(65 + rand(0, 25)),
            'zipCode' => str_pad((string) rand(10000, 99999), 5, '0', STR_PAD_LEFT),
            'country' => 'USA',
        ],
        'tags' => array_slice(['premium', 'verified', 'new', 'vip', 'sponsor'], 0, rand(1, 3)),
        'metadata' => [
            'lastLogin' => date('Y-m-d H:i:s'),
            'loginCount' => rand(1, 1000),
            'preferences' => [
                'theme' => rand(0, 1) ? 'dark' : 'light',
                'language' => 'en',
                'notifications' => (bool) rand(0, 1),
            ],
        ],
    ];
}

// Generate a collection of users
function generateUsers(int $count): array
{
    $users = [];
    for ($i = 1; $i <= $count; $i++) {
        $users[] = generateUser($i);
    }

    return $users;
}

// Calculate approximate size of JSON
function getApproximateSize(array $data): int
{
    return strlen(json_encode($data));
}

// Write JSON to file
function writeFixture(string $filename, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT);
    $fixturesDir = __DIR__.'/fixtures';

    // Create fixtures directory if it doesn't exist
    if (! is_dir($fixturesDir)) {
        mkdir($fixturesDir, 0755, true);
    }

    $path = $fixturesDir.'/'.$filename;
    file_put_contents($path, $json);

    $size = filesize($path);
    $sizeFormatted = $size < 1024 * 1024
        ? round($size / 1024, 2).' KB'
        : round($size / (1024 * 1024), 2).' MB';

    echo "Generated: {$filename} ({$sizeFormatted})\n";
}

echo "Generating benchmark fixtures...\n\n";

// Small dataset (~1KB) - Simple structure
echo "Generating small dataset...\n";
$smallData = [
    'user' => generateUser(1),
    'timestamp' => time(),
    'version' => '1.0.0',
];
writeFixture('small.json', $smallData);

// Medium datasets
echo "\nGenerating medium datasets...\n";

// ~1MB - approximately 500 users
$users = generateUsers(500);
writeFixture('medium-1mb.json', ['users' => $users]);

// ~5MB - approximately 2500 users
$users = generateUsers(2500);
writeFixture('medium-5mb.json', ['users' => $users]);

// ~10MB - approximately 5000 users
$users = generateUsers(5000);
writeFixture('medium-10mb.json', ['users' => $users]);

// Large datasets
echo "\nGenerating large datasets...\n";

// ~50MB - approximately 25000 users
$users = generateUsers(25000);
writeFixture('large-50mb.json', ['users' => $users]);

// ~100MB - approximately 50000 users
$users = generateUsers(50000);
writeFixture('large-100mb.json', ['users' => $users]);

echo "\nFixture generation complete!\n";
echo "All fixtures saved to benchmarks/fixtures/\n";
