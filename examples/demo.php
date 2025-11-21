<?php

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Validator.php';
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/SalesManager.php';
require_once __DIR__ . '/../classes/PayoutCalculator.php';

echo "Multi-Level Affiliate Payout System - Demo\n";
echo str_repeat("=", 60) . "\n\n";

try {

    $userManager = new UserManager();
    $salesManager = new SalesManager();
    $payoutCalculator = new PayoutCalculator();

    echo "STEP 1: Creating User Hierarchy\n";
    echo str_repeat("-", 60) . "\n\n";

    $users = [];

    echo "Creating 6-level affiliate hierarchy...\n\n";
    
    $users['alice'] = $userManager->createUser('alice_demo', 'alice@demo.com', null);
    echo "Alice (ID: {$users['alice']})\n";

    $users['bob'] = $userManager->createUser('bob_demo', 'bob@demo.com', $users['alice']);
    echo "Bob (ID: {$users['bob']}) - Parent: Alice\n";

    $users['charlie'] = $userManager->createUser('charlie_demo', 'charlie@demo.com', $users['bob']);
    echo "Charlie (ID: {$users['charlie']}) - Parent: Bob\n";

    $users['diana'] = $userManager->createUser('diana_demo', 'diana@demo.com', $users['charlie']);
    echo "Diana (ID: {$users['diana']}) - Parent: Charlie\n";

    $users['eve'] = $userManager->createUser('eve_demo', 'eve@demo.com', $users['diana']);
    echo "Eve (ID: {$users['eve']}) - Parent: Diana\n";

    $users['frank'] = $userManager->createUser('frank_demo', 'frank@demo.com', $users['eve']);
    echo "Frank (ID: {$users['frank']}) - Parent: Eve\n";

    echo "\nHierarchy created successfully.\n\n";

    echo "STEP 2: Verifying Upline Chain\n";
    echo str_repeat("-", 60) . "\n\n";

    echo "Getting upline chain for Frank...\n\n";
    $upline = $userManager->getUplineChain($users['frank']);

    echo "Frank's upline (up to 5 levels):\n";
    foreach ($upline as $member) {
        echo "  {$member['username']} (ID: {$member['user_id']})\n";
    }

    echo "\nUpline chain retrieved successfully.\n\n";

    echo "STEP 3: Recording Sale and Calculating Payouts\n";
    echo str_repeat("-", 60) . "\n\n";

    $saleAmount = 1000.00;
    echo "Frank makes a sale of \$" . number_format($saleAmount, 2) . "\n\n";

    $result = $salesManager->recordSaleWithPayouts($users['frank'], $saleAmount);

    if ($result['success']) {
        echo "Sale recorded successfully.\n";
        echo "Sale ID: {$result['sale_id']}\n";
        echo "Amount: $" . number_format($result['amount'], 2) . "\n";
        echo "Payouts created: {$result['payout_count']}\n\n";

        echo "Commission Distribution:\n";
        echo str_repeat("-", 60) . "\n";
        printf("%-15s %-12s %-15s\n", "Recipient", "Rate", "Commission");
        echo str_repeat("-", 60) . "\n";

        $totalCommission = 0;
        foreach ($result['payouts'] as $payout) {
            printf(
                "%-15s %-12s $%-14s\n",
                $payout['username'],
                $payout['rate'] . "%",
                number_format($payout['amount'], 2)
            );
            $totalCommission += $payout['amount'];
        }

        echo str_repeat("-", 60) . "\n";
        printf("%-28s $%-14s\n", "Total Commissions:", number_format($totalCommission, 2));
        echo str_repeat("-", 60) . "\n\n";

    } else {
        echo "Error recording sale: {$result['error']}\n\n";
    }

    echo "STEP 4: Querying User Payouts\n";
    echo str_repeat("-", 60) . "\n\n";

    echo "Eve's commission earnings:\n";
    $evePayouts = $payoutCalculator->getUserPayouts($users['eve']);
    
    if (!empty($evePayouts)) {
        foreach ($evePayouts as $payout) {
            echo "  $" . number_format($payout['amount'], 2) . 
                 " (commission from {$payout['seller_username']}'s sale)\n";
        }
        
        $eveTotal = $payoutCalculator->getUserTotalPayouts($users['eve']);
        echo "  Total earnings: $" . number_format($eveTotal, 2) . "\n";
    } else {
        echo "  No payouts yet.\n";
    }

    echo "\n";

    echo "Alice's commission earnings:\n";
    $alicePayouts = $payoutCalculator->getUserPayouts($users['alice']);
    
    if (!empty($alicePayouts)) {
        foreach ($alicePayouts as $payout) {
            echo "  $" . number_format($payout['amount'], 2) . 
                 " (commission from {$payout['seller_username']}'s sale)\n";
        }
        
        $aliceTotal = $payoutCalculator->getUserTotalPayouts($users['alice']);
        echo "  Total earnings: $" . number_format($aliceTotal, 2) . "\n";
    } else {
        echo "  No payouts yet.\n";
    }

    echo "\nPayout queries completed.\n\n";

    echo "STEP 5: Additional Sale Example\n";
    echo str_repeat("-", 60) . "\n\n";

    echo "Charlie makes a sale of $500.00\n\n";
    
    $result2 = $salesManager->recordSaleWithPayouts($users['charlie'], 500.00);

    if ($result2['success']) {
        echo "Sale recorded successfully.\n";
        echo "Sale ID: {$result2['sale_id']}\n";
        echo "Payouts created: {$result2['payout_count']}\n\n";

        echo "Commission Distribution:\n";
        foreach ($result2['payouts'] as $payout) {
            echo "  {$payout['username']}: $" . number_format($payout['amount'], 2) . 
                 " ({$payout['rate']}%)\n";
        }
    }

    echo "\nAdditional sale processed.\n\n";

    echo "Demo Summary\n";
    echo str_repeat("=", 60) . "\n\n";

    echo "Completed operations:\n";
    echo "- Created 6-level user hierarchy\n";
    echo "- Recorded sales with automatic commission calculation\n";
    echo "- Distributed commissions up to 5 levels\n";
    echo "- Queried user payout history\n\n";

    echo "Features demonstrated:\n";
    echo "- Hierarchical user relationships\n";
    echo "- Automatic upline traversal (up to 5 levels)\n";
    echo "- Commission calculation with different rates per level\n";
    echo "- Transaction-based operations for data integrity\n";
    echo "- Comprehensive payout tracking and reporting\n\n";

    echo "Cleaning up demo data...\n";
    $pdo = Database::getConnection();
    
    $pdo->exec("DELETE FROM payouts WHERE sale_id IN (SELECT id FROM sales WHERE user_id IN (" . implode(',', $users) . "))");
    $pdo->exec("DELETE FROM sales WHERE user_id IN (" . implode(',', $users) . ")");
    
    foreach (array_reverse($users) as $userId) {
        $pdo->exec("DELETE FROM users WHERE id = $userId");
    }
    
    echo "Demo data cleaned up.\n\n";

    echo "Demo completed successfully.\n";

} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
