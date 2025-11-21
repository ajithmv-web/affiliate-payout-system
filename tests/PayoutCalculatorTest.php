<?php


require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Validator.php';
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/SalesManager.php';
require_once __DIR__ . '/../classes/PayoutCalculator.php';

class PayoutCalculatorTest
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
        echo "=== PayoutCalculator Unit Tests ===\n\n";

        $this->testGetCommissionRateLevel1();
        $this->testGetCommissionRateLevel2();
        $this->testGetCommissionRateLevel3();
        $this->testGetCommissionRateLevel4();
        $this->testGetCommissionRateLevel5();
        $this->testGetCommissionRateInvalidLevel();
        $this->testCalculatePayoutsWith5LevelHierarchy();
        $this->testCalculatePayoutsWithFewerThan5Levels();
        $this->testCalculatePayoutsStopsAt5Levels();
        $this->testCalculatePayoutsWithNoUpline();
        $this->testGetUserPayouts();
        $this->testGetSalePayouts();
        $this->testGetUserTotalPayouts();

        $this->cleanup();
        
        echo "\n=== All Tests Completed ===\n";
    }


    private function testGetCommissionRateLevel1(): void
    {
        echo "Test: Get commission rate for level 1... ";
        
        try {
            $rate = $this->payoutCalculator->getCommissionRate(1);
            
            if ($rate === 10.0) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 10.0, got $rate\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testGetCommissionRateLevel2(): void
    {
        echo "Test: Get commission rate for level 2... ";
        
        try {
            $rate = $this->payoutCalculator->getCommissionRate(2);
            
            if ($rate === 5.0) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 5.0, got $rate\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

  
    private function testGetCommissionRateLevel3(): void
    {
        echo "Test: Get commission rate for level 3... ";
        
        try {
            $rate = $this->payoutCalculator->getCommissionRate(3);
            
            if ($rate === 3.0) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 3.0, got $rate\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testGetCommissionRateLevel4(): void
    {
        echo "Test: Get commission rate for level 4... ";
        
        try {
            $rate = $this->payoutCalculator->getCommissionRate(4);
            
            if ($rate === 2.0) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 2.0, got $rate\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testGetCommissionRateLevel5(): void
    {
        echo "Test: Get commission rate for level 5... ";
        
        try {
            $rate = $this->payoutCalculator->getCommissionRate(5);
            
            if ($rate === 1.0) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 1.0, got $rate\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testGetCommissionRateInvalidLevel(): void
    {
        echo "Test: Get commission rate for invalid level... ";
        
        try {
            $rate = $this->payoutCalculator->getCommissionRate(6);
            echo "FAILED: Should have thrown exception\n";
        } catch (InvalidArgumentException $e) {
            echo "PASSED\n";
        } catch (Exception $e) {
            echo "FAILED: Wrong exception type\n";
        }
    }

   
    private function testCalculatePayoutsWith5LevelHierarchy(): void
    {
        echo "Test: Calculate payouts with 5-level hierarchy... ";
        
        try {
           
            $users = [];
            $users[0] = $this->userManager->createUser('l1_' . time(), 'l1' . time() . '@test.com', null);
            
            for ($i = 1; $i < 5; $i++) {
                $users[$i] = $this->userManager->createUser(
                    'l' . ($i + 1) . '_' . time(),
                    'l' . ($i + 1) . time() . '@test.com',
                    $users[$i - 1]
                );
            }
            
            foreach ($users as $userId) {
                $this->testUserIds[] = $userId;
            }

           
            $saleId = $this->salesManager->recordSale($users[4], 1000.00);
            $this->testSaleIds[] = $saleId;

            
            $payouts = $this->payoutCalculator->calculatePayouts($saleId, $users[4], 1000.00);

           
            if (count($payouts) === 4) {
                
                $expectedAmounts = [100.00, 50.00, 30.00, 20.00];
                $allCorrect = true;
                
                for ($i = 0; $i < 4; $i++) {
                    if ($payouts[$i]['amount'] != $expectedAmounts[$i]) {
                        $allCorrect = false;
                        break;
                    }
                    $this->testPayoutIds[] = $payouts[$i]['payout_id'];
                }
                
                if ($allCorrect) {
                    echo "PASSED\n";
                } else {
                    echo "FAILED: Payout amounts incorrect\n";
                }
            } else {
                echo "FAILED: Expected 4 payouts, got " . count($payouts) . "\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testCalculatePayoutsWithFewerThan5Levels(): void
    {
        echo "Test: Calculate payouts with fewer than 5 levels... ";
        
        try {
            
            $user1 = $this->userManager->createUser('few1_' . time(), 'few1' . time() . '@test.com', null);
            $user2 = $this->userManager->createUser('few2_' . time(), 'few2' . time() . '@test.com', $user1);
            $user3 = $this->userManager->createUser('few3_' . time(), 'few3' . time() . '@test.com', $user2);
            
            $this->testUserIds[] = $user1;
            $this->testUserIds[] = $user2;
            $this->testUserIds[] = $user3;

           
            $saleId = $this->salesManager->recordSale($user3, 500.00);
            $this->testSaleIds[] = $saleId;

         
            $payouts = $this->payoutCalculator->calculatePayouts($saleId, $user3, 500.00);

 
            if (count($payouts) === 2) {
             
                if ($payouts[0]['amount'] == 50.00 && $payouts[1]['amount'] == 25.00) {
                    foreach ($payouts as $payout) {
                        $this->testPayoutIds[] = $payout['payout_id'];
                    }
                    echo "PASSED\n";
                } else {
                    echo "FAILED: Payout amounts incorrect\n";
                }
            } else {
                echo "FAILED: Expected 2 payouts, got " . count($payouts) . "\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testCalculatePayoutsStopsAt5Levels(): void
    {
        echo "Test: Calculate payouts stops at 5 levels... ";
        
        try {

            $users = [];
            $users[0] = $this->userManager->createUser('deep1_' . time(), 'deep1' . time() . '@test.com', null);
            
            for ($i = 1; $i < 7; $i++) {
                $users[$i] = $this->userManager->createUser(
                    'deep' . ($i + 1) . '_' . time(),
                    'deep' . ($i + 1) . time() . '@test.com',
                    $users[$i - 1]
                );
            }
            
            foreach ($users as $userId) {
                $this->testUserIds[] = $userId;
            }


            $saleId = $this->salesManager->recordSale($users[6], 1000.00);
            $this->testSaleIds[] = $saleId;

        
            $payouts = $this->payoutCalculator->calculatePayouts($saleId, $users[6], 1000.00);

            if (count($payouts) === 5) {
                foreach ($payouts as $payout) {
                    $this->testPayoutIds[] = $payout['payout_id'];
                }
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 5 payouts, got " . count($payouts) . "\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testCalculatePayoutsWithNoUpline(): void
    {
        echo "Test: Calculate payouts with no upline... ";
        
        try {

            $userId = $this->userManager->createUser('root_' . time(), 'root' . time() . '@test.com', null);
            $this->testUserIds[] = $userId;

    
            $saleId = $this->salesManager->recordSale($userId, 100.00);
            $this->testSaleIds[] = $saleId;

        
            $payouts = $this->payoutCalculator->calculatePayouts($saleId, $userId, 100.00);

            if (count($payouts) === 0) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 0 payouts, got " . count($payouts) . "\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    private function testGetUserPayouts(): void
    {
        echo "Test: Get user payouts... ";
        
        try {
       
            $parent = $this->userManager->createUser('payparent_' . time(), 'payparent' . time() . '@test.com', null);
            $child = $this->userManager->createUser('paychild_' . time(), 'paychild' . time() . '@test.com', $parent);
            
            $this->testUserIds[] = $parent;
            $this->testUserIds[] = $child;

        
            $saleId = $this->salesManager->recordSale($child, 200.00);
            $this->testSaleIds[] = $saleId;

            $payouts = $this->payoutCalculator->calculatePayouts($saleId, $child, 200.00);
            foreach ($payouts as $payout) {
                $this->testPayoutIds[] = $payout['payout_id'];
            }

            $userPayouts = $this->payoutCalculator->getUserPayouts($parent);

            if (count($userPayouts) >= 1 && $userPayouts[0]['amount'] == 20.00) {
                echo "PASSED\n";
            } else {
                echo "FAILED: User payouts incorrect\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testGetSalePayouts(): void
    {
        echo "Test: Get sale payouts... ";
        
        try {
 
            $u1 = $this->userManager->createUser('sp1_' . time(), 'sp1' . time() . '@test.com', null);
            $u2 = $this->userManager->createUser('sp2_' . time(), 'sp2' . time() . '@test.com', $u1);
            $u3 = $this->userManager->createUser('sp3_' . time(), 'sp3' . time() . '@test.com', $u2);
            
            $this->testUserIds[] = $u1;
            $this->testUserIds[] = $u2;
            $this->testUserIds[] = $u3;

           
            $saleId = $this->salesManager->recordSale($u3, 300.00);
            $this->testSaleIds[] = $saleId;

         
            $payouts = $this->payoutCalculator->calculatePayouts($saleId, $u3, 300.00);
            foreach ($payouts as $payout) {
                $this->testPayoutIds[] = $payout['payout_id'];
            }

          
            $salePayouts = $this->payoutCalculator->getSalePayouts($saleId);

            if (count($salePayouts) === 2) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 2 payouts for sale, got " . count($salePayouts) . "\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }


    private function testGetUserTotalPayouts(): void
    {
        echo "Test: Get user total payouts... ";
        
        try {
  
            $parent = $this->userManager->createUser('totparent_' . time(), 'totparent' . time() . '@test.com', null);
            $child1 = $this->userManager->createUser('totchild1_' . time(), 'totchild1' . time() . '@test.com', $parent);
            $child2 = $this->userManager->createUser('totchild2_' . time(), 'totchild2' . time() . '@test.com', $parent);
            
            $this->testUserIds[] = $parent;
            $this->testUserIds[] = $child1;
            $this->testUserIds[] = $child2;

         
            $saleId1 = $this->salesManager->recordSale($child1, 100.00);
            $saleId2 = $this->salesManager->recordSale($child2, 200.00);
            
            $this->testSaleIds[] = $saleId1;
            $this->testSaleIds[] = $saleId2;

         
            $payouts1 = $this->payoutCalculator->calculatePayouts($saleId1, $child1, 100.00);
            $payouts2 = $this->payoutCalculator->calculatePayouts($saleId2, $child2, 200.00);
            
            foreach ($payouts1 as $payout) {
                $this->testPayoutIds[] = $payout['payout_id'];
            }
            foreach ($payouts2 as $payout) {
                $this->testPayoutIds[] = $payout['payout_id'];
            }

       
            $total = $this->payoutCalculator->getUserTotalPayouts($parent);

            if ($total == 30.00) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 30.00, got $total\n";
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
    $test = new PayoutCalculatorTest();
    $test->runAll();
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}
