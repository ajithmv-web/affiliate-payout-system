<?php

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Validator.php';
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/SalesManager.php';
require_once __DIR__ . '/../classes/PayoutCalculator.php';

class IntegrationTest
{
    private UserManager $userManager;
    private SalesManager $salesManager;
    private PayoutCalculator $payoutCalculator;
    private array $testUserIds = [];
    private array $testSaleIds = [];
    private array $testPayoutIds = [];

    public function __construct()
    {
        $this->userManager = new UserManager();
        $this->salesManager = new SalesManager();
        $this->payoutCalculator = new PayoutCalculator();
    }


    public function runAll(): void
    {
        echo "=== Integration Tests ===\n\n";

        $this->testEndToEndFlow6LevelHierarchy();
        $this->testMultipleChildrenScenario();
        $this->testRootUserSale();
        $this->testSingleLevelHierarchy();
        $this->testTransactionRollback();
        $this->testComplexTreeStructure();

        $this->cleanup();
        
        echo "\n=== All Integration Tests Completed ===\n";
    }

    private function testEndToEndFlow6LevelHierarchy(): void
    {
        echo "Test: End-to-end flow with 6-level hierarchy... ";
        
        try {

            $users = [];
            $users[0] = $this->userManager->createUser('e2e1_' . time(), 'e2e1' . time() . '@test.com', null);
            
            for ($i = 1; $i < 6; $i++) {
                $users[$i] = $this->userManager->createUser(
                    'e2e' . ($i + 1) . '_' . time(),
                    'e2e' . ($i + 1) . time() . '@test.com',
                    $users[$i - 1]
                );
            }
            
            foreach ($users as $userId) {
                $this->testUserIds[] = $userId;
            }

    
            $result = $this->salesManager->recordSaleWithPayouts($users[5], 1000.00);
            
            if (!$result['success']) {
                echo "FAILED: Sale recording failed\n";
                return;
            }

            $this->testSaleIds[] = $result['sale_id'];


            if (count($result['payouts']) !== 5) {
                echo "FAILED: Expected 5 payouts, got " . count($result['payouts']) . "\n";
                return;
            }

           
            $expectedAmounts = [100.00, 50.00, 30.00, 20.00, 10.00]; 
            $allCorrect = true;

            foreach ($result['payouts'] as $index => $payout) {
                $this->testPayoutIds[] = $payout['payout_id'];
                
                if ($payout['amount'] != $expectedAmounts[$index]) {
                    $allCorrect = false;
                    break;
                }
            }


            $rootPayouts = $this->payoutCalculator->getUserPayouts($users[0]);
            $hasPayoutFromThisSale = false;
            foreach ($rootPayouts as $payout) {
                if ($payout['sale_id'] == $result['sale_id']) {
                    $hasPayoutFromThisSale = true;
                    break;
                }
            }

            if ($allCorrect && !$hasPayoutFromThisSale) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Commission amounts or distribution incorrect\n";
            }

        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    private function testMultipleChildrenScenario(): void
    {
        echo "Test: Multiple children scenario... ";
        
        try {

            $parent = $this->userManager->createUser('parent_mc_' . time(), 'parent_mc' . time() . '@test.com', null);
            $child1 = $this->userManager->createUser('child1_mc_' . time(), 'child1_mc' . time() . '@test.com', $parent);
            $child2 = $this->userManager->createUser('child2_mc_' . time(), 'child2_mc' . time() . '@test.com', $parent);
            $child3 = $this->userManager->createUser('child3_mc_' . time(), 'child3_mc' . time() . '@test.com', $parent);
            
            $this->testUserIds[] = $parent;
            $this->testUserIds[] = $child1;
            $this->testUserIds[] = $child2;
            $this->testUserIds[] = $child3;

            $result1 = $this->salesManager->recordSaleWithPayouts($child1, 100.00);
            $result2 = $this->salesManager->recordSaleWithPayouts($child2, 200.00);
            $result3 = $this->salesManager->recordSaleWithPayouts($child3, 300.00);

            $this->testSaleIds[] = $result1['sale_id'];
            $this->testSaleIds[] = $result2['sale_id'];
            $this->testSaleIds[] = $result3['sale_id'];

            foreach ($result1['payouts'] as $p) $this->testPayoutIds[] = $p['payout_id'];
            foreach ($result2['payouts'] as $p) $this->testPayoutIds[] = $p['payout_id'];
            foreach ($result3['payouts'] as $p) $this->testPayoutIds[] = $p['payout_id'];

            $parentTotal = $this->payoutCalculator->getUserTotalPayouts($parent);
            $expectedTotal = 10.00 + 20.00 + 30.00;

            if ($parentTotal == $expectedTotal) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected parent total $expectedTotal, got $parentTotal\n";
            }

        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testRootUserSale(): void
    {
        echo "Test: Root user sale (no payouts)... ";
        
        try {
  
            $rootUser = $this->userManager->createUser('root_test_' . time(), 'root_test' . time() . '@test.com', null);
            $this->testUserIds[] = $rootUser;

     
            $result = $this->salesManager->recordSaleWithPayouts($rootUser, 500.00);
            $this->testSaleIds[] = $result['sale_id'];

            if ($result['success'] && $result['payout_count'] === 0) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 0 payouts for root user\n";
            }

        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testSingleLevelHierarchy(): void
    {
        echo "Test: Single-level hierarchy... ";
        
        try {

            $parent = $this->userManager->createUser('single_parent_' . time(), 'single_parent' . time() . '@test.com', null);
            $child = $this->userManager->createUser('single_child_' . time(), 'single_child' . time() . '@test.com', $parent);
            
            $this->testUserIds[] = $parent;
            $this->testUserIds[] = $child;

   
            $result = $this->salesManager->recordSaleWithPayouts($child, 400.00);
            $this->testSaleIds[] = $result['sale_id'];

            foreach ($result['payouts'] as $p) $this->testPayoutIds[] = $p['payout_id'];

            if ($result['success'] && 
                $result['payout_count'] === 1 && 
                $result['payouts'][0]['amount'] == 40.00) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Single-level payout incorrect\n";
            }

        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testTransactionRollback(): void
    {
        echo "Test: Transaction rollback on error... ";
        
        try {
       
            $user = $this->userManager->createUser('rollback_' . time(), 'rollback' . time() . '@test.com', null);
            $this->testUserIds[] = $user;

       
            $result = $this->salesManager->recordSaleWithPayouts($user, -100.00);

            if (!$result['success']) {
             
                $userSales = $this->salesManager->getUserSales($user);
                
                if (count($userSales) === 0) {
                    echo "PASSED\n";
                } else {
                    echo "FAILED: Sale was created despite error\n";
                }
            } else {
                echo "FAILED: Should have failed with invalid amount\n";
            }

        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    private function testComplexTreeStructure(): void
    {
        echo "Test: Complex tree structure... ";
        
        try {

            $root = $this->userManager->createUser('tree_root_' . time(), 'tree_root' . time() . '@test.com', null);
            
            $b1 = $this->userManager->createUser('tree_b1_' . time(), 'tree_b1' . time() . '@test.com', $root);
            $b2 = $this->userManager->createUser('tree_b2_' . time(), 'tree_b2' . time() . '@test.com', $root);
            
            $c1 = $this->userManager->createUser('tree_c1_' . time(), 'tree_c1' . time() . '@test.com', $b1);
            $c2 = $this->userManager->createUser('tree_c2_' . time(), 'tree_c2' . time() . '@test.com', $b1);
            $c3 = $this->userManager->createUser('tree_c3_' . time(), 'tree_c3' . time() . '@test.com', $b2);
            
            $d1 = $this->userManager->createUser('tree_d1_' . time(), 'tree_d1' . time() . '@test.com', $c1);

            $this->testUserIds[] = $root;
            $this->testUserIds[] = $b1;
            $this->testUserIds[] = $b2;
            $this->testUserIds[] = $c1;
            $this->testUserIds[] = $c2;
            $this->testUserIds[] = $c3;
            $this->testUserIds[] = $d1;


            $result = $this->salesManager->recordSaleWithPayouts($d1, 600.00);
            $this->testSaleIds[] = $result['sale_id'];

            foreach ($result['payouts'] as $p) $this->testPayoutIds[] = $p['payout_id'];


            if ($result['payout_count'] === 3) {
                $c1Payout = $this->payoutCalculator->getUserTotalPayouts($c1);
                $b1Payout = $this->payoutCalculator->getUserTotalPayouts($b1);
                $rootPayout = $this->payoutCalculator->getUserTotalPayouts($root);

                if ($c1Payout == 60.00 && $b1Payout == 30.00 && $rootPayout == 18.00) {
                    echo "PASSED\n";
                } else {
                    echo "FAILED: Payout amounts incorrect (C1: $c1Payout, B1: $b1Payout, Root: $rootPayout)\n";
                }
            } else {
                echo "FAILED: Expected 3 payouts, got {$result['payout_count']}\n";
            }

        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    private function cleanup(): void
    {
        echo "\nCleaning up test data... ";
        
        try {
            $pdo = Database::getConnection();
            
            
            foreach ($this->testPayoutIds as $payoutId) {
                $stmt = $pdo->prepare('DELETE FROM payouts WHERE id = ?');
                $stmt->execute([$payoutId]);
            }

            foreach ($this->testSaleIds as $saleId) {
                $stmt = $pdo->prepare('DELETE FROM sales WHERE id = ?');
                $stmt->execute([$saleId]);
            }

            foreach (array_reverse($this->testUserIds) as $userId) {
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$userId]);
            }
            
            echo "Done\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

try {
    $test = new IntegrationTest();
    $test->runAll();
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}
