<?php
/**
 * Unit Tests for SalesManager
 * 
 * Tests sale recording, validation, and user sales queries.
 * Run with: php tests/SalesManagerTest.php
 */

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Validator.php';
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/SalesManager.php';

class SalesManagerTest
{
    private UserManager $userManager;
    private SalesManager $salesManager;
    private array $testUserIds = [];
    private array $testSaleIds = [];

    public function __construct()
    {
        $this->userManager = new UserManager();
        $this->salesManager = new SalesManager();
    }

    /**
     * Run all tests
     */
    public function runAll(): void
    {
        echo "=== SalesManager Unit Tests ===\n\n";

        $this->testRecordSaleWithValidData();
        $this->testRecordSaleWithInvalidUser();
        $this->testValidateSaleAmountNegative();
        $this->testValidateSaleAmountZero();
        $this->testValidateSaleAmountNonNumeric();
        $this->testValidateSaleAmountTooManyDecimals();
        $this->testValidateSaleAmountValid();
        $this->testGetSaleById();
        $this->testGetUserSales();
        $this->testGetUserTotalSales();
        $this->testGetUserSalesCount();

        $this->cleanup();
        
        echo "\n=== All Tests Completed ===\n";
    }

    /**
     * Test: Record sale with valid data
     */
    private function testRecordSaleWithValidData(): void
    {
        echo "Test: Record sale with valid data... ";
        
        try {
            // Create a user first
            $userId = $this->userManager->createUser(
                'seller_' . time(),
                'seller' . time() . '@test.com',
                null
            );
            $this->testUserIds[] = $userId;

            // Record a sale
            $saleId = $this->salesManager->recordSale($userId, 100.50);

            if ($saleId && is_int($saleId)) {
                $this->testSaleIds[] = $saleId;
                
                // Verify sale was recorded
                $sale = $this->salesManager->getSaleById($saleId);
                if ($sale && $sale['amount'] == 100.50 && $sale['user_id'] == $userId) {
                    echo "PASSED\n";
                } else {
                    echo "FAILED: Sale data mismatch\n";
                }
            } else {
                echo "FAILED: Sale ID not returned\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Record sale with invalid user
     */
    private function testRecordSaleWithInvalidUser(): void
    {
        echo "Test: Record sale with invalid user... ";
        
        try {
            $saleId = $this->salesManager->recordSale(999999, 100.00);
            echo "FAILED: Should have thrown exception\n";
        } catch (InvalidArgumentException $e) {
            echo "PASSED\n";
        } catch (Exception $e) {
            echo "FAILED: Wrong exception type\n";
        }
    }

    /**
     * Test: Validate negative sale amount
     */
    private function testValidateSaleAmountNegative(): void
    {
        echo "Test: Validate negative sale amount... ";
        
        $isValid = $this->salesManager->validateSaleAmount(-10.00);
        
        if (!$isValid) {
            echo "PASSED\n";
        } else {
            echo "FAILED: Should reject negative amounts\n";
        }
    }

    /**
     * Test: Validate zero sale amount
     */
    private function testValidateSaleAmountZero(): void
    {
        echo "Test: Validate zero sale amount... ";
        
        $isValid = $this->salesManager->validateSaleAmount(0);
        
        if (!$isValid) {
            echo "PASSED\n";
        } else {
            echo "FAILED: Should reject zero amount\n";
        }
    }

    /**
     * Test: Validate non-numeric sale amount
     */
    private function testValidateSaleAmountNonNumeric(): void
    {
        echo "Test: Validate non-numeric sale amount... ";
        
        $isValid = $this->salesManager->validateSaleAmount('abc');
        
        if (!$isValid) {
            echo "PASSED\n";
        } else {
            echo "FAILED: Should reject non-numeric values\n";
        }
    }

    /**
     * Test: Validate sale amount with too many decimals
     */
    private function testValidateSaleAmountTooManyDecimals(): void
    {
        echo "Test: Validate sale amount with too many decimals... ";
        
        $isValid = $this->salesManager->validateSaleAmount(10.123);
        
        if (!$isValid) {
            echo "PASSED\n";
        } else {
            echo "FAILED: Should reject amounts with more than 2 decimals\n";
        }
    }

    /**
     * Test: Validate valid sale amount
     */
    private function testValidateSaleAmountValid(): void
    {
        echo "Test: Validate valid sale amount... ";
        
        $isValid1 = $this->salesManager->validateSaleAmount(100.50);
        $isValid2 = $this->salesManager->validateSaleAmount(0.01);
        $isValid3 = $this->salesManager->validateSaleAmount(99999999.99);
        
        if ($isValid1 && $isValid2 && $isValid3) {
            echo "PASSED\n";
        } else {
            echo "FAILED: Should accept valid amounts\n";
        }
    }

    /**
     * Test: Get sale by ID
     */
    private function testGetSaleById(): void
    {
        echo "Test: Get sale by ID... ";
        
        try {
            // Create user and sale
            $userId = $this->userManager->createUser(
                'getsale_' . time(),
                'getsale' . time() . '@test.com',
                null
            );
            $this->testUserIds[] = $userId;

            $saleId = $this->salesManager->recordSale($userId, 250.75);
            $this->testSaleIds[] = $saleId;

            // Get sale
            $sale = $this->salesManager->getSaleById($saleId);

            if ($sale && 
                $sale['id'] == $saleId && 
                $sale['user_id'] == $userId && 
                $sale['amount'] == 250.75) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Sale data incorrect\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Get user sales
     */
    private function testGetUserSales(): void
    {
        echo "Test: Get user sales... ";
        
        try {
            // Create user
            $userId = $this->userManager->createUser(
                'multisale_' . time(),
                'multisale' . time() . '@test.com',
                null
            );
            $this->testUserIds[] = $userId;

            // Record multiple sales
            $saleId1 = $this->salesManager->recordSale($userId, 100.00);
            $saleId2 = $this->salesManager->recordSale($userId, 200.00);
            $saleId3 = $this->salesManager->recordSale($userId, 300.00);
            
            $this->testSaleIds[] = $saleId1;
            $this->testSaleIds[] = $saleId2;
            $this->testSaleIds[] = $saleId3;

            // Get user sales
            $sales = $this->salesManager->getUserSales($userId);

            if (count($sales) === 3) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 3 sales, got " . count($sales) . "\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Get user total sales
     */
    private function testGetUserTotalSales(): void
    {
        echo "Test: Get user total sales... ";
        
        try {
            // Create user
            $userId = $this->userManager->createUser(
                'totalsale_' . time(),
                'totalsale' . time() . '@test.com',
                null
            );
            $this->testUserIds[] = $userId;

            // Record sales
            $saleId1 = $this->salesManager->recordSale($userId, 100.00);
            $saleId2 = $this->salesManager->recordSale($userId, 150.50);
            
            $this->testSaleIds[] = $saleId1;
            $this->testSaleIds[] = $saleId2;

            // Get total
            $total = $this->salesManager->getUserTotalSales($userId);

            if ($total == 250.50) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 250.50, got $total\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Get user sales count
     */
    private function testGetUserSalesCount(): void
    {
        echo "Test: Get user sales count... ";
        
        try {
            // Create user
            $userId = $this->userManager->createUser(
                'countsale_' . time(),
                'countsale' . time() . '@test.com',
                null
            );
            $this->testUserIds[] = $userId;

            // Record sales
            $saleId1 = $this->salesManager->recordSale($userId, 50.00);
            $saleId2 = $this->salesManager->recordSale($userId, 75.00);
            $saleId3 = $this->salesManager->recordSale($userId, 100.00);
            
            $this->testSaleIds[] = $saleId1;
            $this->testSaleIds[] = $saleId2;
            $this->testSaleIds[] = $saleId3;

            // Get count
            $count = $this->salesManager->getUserSalesCount($userId);

            if ($count === 3) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Expected 3, got $count\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Cleanup test data
     */
    private function cleanup(): void
    {
        echo "\nCleaning up test data... ";
        
        try {
            $pdo = Database::getConnection();
            
            // Delete sales first (foreign key constraint)
            foreach ($this->testSaleIds as $saleId) {
                $stmt = $pdo->prepare('DELETE FROM sales WHERE id = ?');
                $stmt->execute([$saleId]);
            }

            // Delete users
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

// Run tests
try {
    $test = new SalesManagerTest();
    $test->runAll();
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}
