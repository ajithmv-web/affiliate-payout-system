<?php

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Validator.php';
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/SalesManager.php';
require_once __DIR__ . '/../classes/PayoutCalculator.php';

$withSampleData = false;
if (php_sapi_name() === 'cli') {
    $withSampleData = in_array('--with-sample-data', $argv);
} else {

    $withSampleData = isset($_GET['with-sample-data']) || isset($_GET['sample']);
}

try {
    $schemaFile = __DIR__ . '/schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new RuntimeException('Schema file not found: ' . $schemaFile);
    }

    echo "Reading schema file";
    $schema = file_get_contents($schemaFile);
    echo "Connecting to database";
    $pdo = Database::getConnection();

    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );

    echo "Executing schema statements";
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }

    echo "Database schema created successfully.\n\n";

    if ($withSampleData) {
        echo "Inserting sample data...\n";
        insertSampleData($pdo);
        echo "Sample data inserted successfully.\n\n";
    }

    echo "=== Setup Complete ===\n";
    echo "\nYou can now:\n";
    echo "- Run tests: php tests/UserManagerTest.php\n";
    echo "- Run example: php examples/demo.php\n";
    echo "- View sample data: php examples/view_data.php\n";

} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    exit(1);
}

function insertSampleData(PDO $pdo): void
{
    $userManager = new UserManager($pdo);
    $salesManager = new SalesManager($pdo);
    $payoutCalculator = new PayoutCalculator($pdo);

    echo "  Creating user hierarchy...\n";

    $users = [];
    $users[1] = $userManager->createUser('john_root', 'john@example.com', null);
    echo "    Created Level 1: john_root (ID: {$users[1]})\n";

    $users[2] = $userManager->createUser('sarah_l2', 'sarah@example.com', $users[1]);
    echo "    Created Level 2: sarah_l2 (ID: {$users[2]})\n";

    $users[3] = $userManager->createUser('mike_l3', 'mike@example.com', $users[2]);
    echo "    Created Level 3: mike_l3 (ID: {$users[3]})\n";

    $users[4] = $userManager->createUser('emma_l4', 'emma@example.com', $users[3]);
    echo "    Created Level 4: emma_l4 (ID: {$users[4]})\n";

    $users[5] = $userManager->createUser('david_l5', 'david@example.com', $users[4]);
    echo "    Created Level 5: david_l5 (ID: {$users[5]})\n";

    $users[6] = $userManager->createUser('lisa_l6', 'lisa@example.com', $users[5]);
    echo "    Created Level 6: lisa_l6 (ID: {$users[6]})\n";

    $users[7] = $userManager->createUser('tom_l2b', 'tom@example.com', $users[1]);
    echo "    Created Level 2 (branch): tom_l2b (ID: {$users[7]})\n";

    $users[8] = $userManager->createUser('anna_l3b', 'anna@example.com', $users[7]);
    echo "    Created Level 3 (branch): anna_l3b (ID: {$users[8]})\n";

    echo "\n  Recording sales...\n";

    $sales = [];

    $result1 = $salesManager->recordSaleWithPayouts($users[6], 1000.00);
    if ($result1['success']) {
        $sales[] = $result1['sale_id'];
        echo "    Sale 1: \$1000.00 by lisa_l6 (ID: {$result1['sale_id']}) - {$result1['payout_count']} payouts created\n";
    }


    $result2 = $salesManager->recordSaleWithPayouts($users[3], 500.00);
    if ($result2['success']) {
        $sales[] = $result2['sale_id'];
        echo "    Sale 2: \$500.00 by mike_l3 (ID: {$result2['sale_id']}) - {$result2['payout_count']} payouts created\n";
    }


    $result3 = $salesManager->recordSaleWithPayouts($users[8], 750.00);
    if ($result3['success']) {
        $sales[] = $result3['sale_id'];
        echo "    Sale 3: \$750.00 by anna_l3b (ID: {$result3['sale_id']}) - {$result3['payout_count']} payouts created\n";
    }


    $result4 = $salesManager->recordSaleWithPayouts($users[1], 300.00);
    if ($result4['success']) {
        $sales[] = $result4['sale_id'];
        echo "    Sale 4: \$300.00 by john_root (ID: {$result4['sale_id']}) - {$result4['payout_count']} payouts created\n";
    }

    echo "\n  Sample data summary:\n";
    echo "    Total users created: " . count($users) . "\n";
    echo "    Total sales recorded: " . count($sales) . "\n";
    echo "    Total payouts created: " . ($result1['payout_count'] + $result2['payout_count'] + $result3['payout_count']) . "\n";
}
