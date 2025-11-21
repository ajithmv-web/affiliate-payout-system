<?php
/**
 * Unit Tests for UserManager
 * 
 * Tests user creation, parent validation, and upline chain traversal.
 * Run with: php tests/UserManagerTest.php
 */

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Validator.php';
require_once __DIR__ . '/../classes/UserManager.php';

class UserManagerTest
{
    private UserManager $userManager;
    private array $testUserIds = [];

    public function __construct()
    {
        $this->userManager = new UserManager();
    }

    /**
     * Run all tests
     */
    public function runAll(): void
    {
        echo "=== UserManager Unit Tests ===\n\n";

        $this->testCreateUserWithoutParent();
        $this->testCreateUserWithParent();
        $this->testCreateUserWithInvalidParent();
        $this->testCreateUserWithInvalidUsername();
        $this->testCreateUserWithInvalidEmail();
        $this->testCreateUserWithDuplicateUsername();
        $this->testGetUserById();
        $this->testValidateParentExists();
        $this->testGetUplineChainWithMultipleLevels();
        $this->testGetUplineChainWithFewerThan5Levels();
        $this->testGetUplineChainStopsAt5Levels();
        $this->testGetUserLevel();

        $this->cleanup();
        
        echo "\n=== All Tests Completed ===\n";
    }

    /**
     * Test: Create user without parent (root user)
     */
    private function testCreateUserWithoutParent(): void
    {
        echo "Test: Create user without parent... ";
        
        try {
            $userId = $this->userManager->createUser(
                'test_root_' . time(),
                'root' . time() . '@test.com',
                null
            );

            if ($userId && is_int($userId)) {
                $this->testUserIds[] = $userId;
                echo "PASSED\n";
            } else {
                echo "FAILED: User ID not returned\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Create user with valid parent
     */
    private function testCreateUserWithParent(): void
    {
        echo "Test: Create user with parent... ";
        
        try {
            // Create parent first
            $parentId = $this->userManager->createUser(
                'test_parent_' . time(),
                'parent' . time() . '@test.com',
                null
            );
            $this->testUserIds[] = $parentId;

            // Create child
            $childId = $this->userManager->createUser(
                'test_child_' . time(),
                'child' . time() . '@test.com',
                $parentId
            );

            if ($childId && is_int($childId)) {
                $this->testUserIds[] = $childId;
                
                // Verify parent relationship
                $child = $this->userManager->getUserById($childId);
                if ($child['parent_id'] == $parentId) {
                    echo "PASSED\n";
                } else {
                    echo "FAILED: Parent relationship not established\n";
                }
            } else {
                echo "FAILED: Child user not created\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Create user with invalid parent ID
     */
    private function testCreateUserWithInvalidParent(): void
    {
        echo "Test: Create user with invalid parent... ";
        
        try {
            $userId = $this->userManager->createUser(
                'test_invalid_' . time(),
                'invalid' . time() . '@test.com',
                999999 // Non-existent parent ID
            );
            
            echo "FAILED: Should have thrown exception\n";
        } catch (InvalidArgumentException $e) {
            echo "PASSED\n";
        } catch (Exception $e) {
            echo "FAILED: Wrong exception type\n";
        }
    }

    /**
     * Test: Create user with invalid username
     */
    private function testCreateUserWithInvalidUsername(): void
    {
        echo "Test: Create user with invalid username... ";
        
        try {
            $userId = $this->userManager->createUser(
                'ab', // Too short
                'test' . time() . '@test.com',
                null
            );
            
            echo "FAILED: Should have thrown exception\n";
        } catch (InvalidArgumentException $e) {
            echo "PASSED\n";
        } catch (Exception $e) {
            echo "FAILED: Wrong exception type\n";
        }
    }

    /**
     * Test: Create user with invalid email
     */
    private function testCreateUserWithInvalidEmail(): void
    {
        echo "Test: Create user with invalid email... ";
        
        try {
            $userId = $this->userManager->createUser(
                'test_user_' . time(),
                'invalid-email', // Invalid format
                null
            );
            
            echo "FAILED: Should have thrown exception\n";
        } catch (InvalidArgumentException $e) {
            echo "PASSED\n";
        } catch (Exception $e) {
            echo "FAILED: Wrong exception type\n";
        }
    }

    /**
     * Test: Create user with duplicate username
     */
    private function testCreateUserWithDuplicateUsername(): void
    {
        echo "Test: Create user with duplicate username... ";
        
        try {
            $username = 'test_duplicate_' . time();
            
            // Create first user
            $userId1 = $this->userManager->createUser(
                $username,
                'first' . time() . '@test.com',
                null
            );
            $this->testUserIds[] = $userId1;

            // Try to create second user with same username
            $userId2 = $this->userManager->createUser(
                $username,
                'second' . time() . '@test.com',
                null
            );
            
            echo "FAILED: Should have thrown exception\n";
        } catch (InvalidArgumentException $e) {
            echo "PASSED\n";
        } catch (Exception $e) {
            echo "FAILED: Wrong exception type\n";
        }
    }

    /**
     * Test: Get user by ID
     */
    private function testGetUserById(): void
    {
        echo "Test: Get user by ID... ";
        
        try {
            $username = 'test_getuser_' . time();
            $email = 'getuser' . time() . '@test.com';
            
            $userId = $this->userManager->createUser($username, $email, null);
            $this->testUserIds[] = $userId;

            $user = $this->userManager->getUserById($userId);

            if ($user && $user['username'] === $username && $user['email'] === $email) {
                echo "PASSED\n";
            } else {
                echo "FAILED: User data mismatch\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Validate parent exists
     */
    private function testValidateParentExists(): void
    {
        echo "Test: Validate parent exists... ";
        
        try {
            // Create a user
            $userId = $this->userManager->createUser(
                'test_validate_' . time(),
                'validate' . time() . '@test.com',
                null
            );
            $this->testUserIds[] = $userId;

            // Test with valid ID
            $exists = $this->userManager->validateParentExists($userId);
            
            // Test with invalid ID
            $notExists = $this->userManager->validateParentExists(999999);

            if ($exists && !$notExists) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Validation logic incorrect\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Get upline chain with multiple levels
     */
    private function testGetUplineChainWithMultipleLevels(): void
    {
        echo "Test: Get upline chain with multiple levels... ";
        
        try {
            // Create 3-level hierarchy
            $level1 = $this->userManager->createUser('level1_' . time(), 'l1' . time() . '@test.com', null);
            $level2 = $this->userManager->createUser('level2_' . time(), 'l2' . time() . '@test.com', $level1);
            $level3 = $this->userManager->createUser('level3_' . time(), 'l3' . time() . '@test.com', $level2);
            
            $this->testUserIds[] = $level1;
            $this->testUserIds[] = $level2;
            $this->testUserIds[] = $level3;

            // Get upline chain from level 3
            $upline = $this->userManager->getUplineChain($level3);

            if (count($upline) === 2 && 
                $upline[0]['user_id'] == $level2 && $upline[0]['level'] == 1 &&
                $upline[1]['user_id'] == $level1 && $upline[1]['level'] == 2) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Upline chain incorrect\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Get upline chain with fewer than 5 levels
     */
    private function testGetUplineChainWithFewerThan5Levels(): void
    {
        echo "Test: Get upline chain with fewer than 5 levels... ";
        
        try {
            // Create 2-level hierarchy
            $parent = $this->userManager->createUser('parent2_' . time(), 'p2' . time() . '@test.com', null);
            $child = $this->userManager->createUser('child2_' . time(), 'c2' . time() . '@test.com', $parent);
            
            $this->testUserIds[] = $parent;
            $this->testUserIds[] = $child;

            // Get upline chain (should only return 1 level)
            $upline = $this->userManager->getUplineChain($child, 5);

            if (count($upline) === 1 && $upline[0]['user_id'] == $parent) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Should return only available levels\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Get upline chain stops at 5 levels
     */
    private function testGetUplineChainStopsAt5Levels(): void
    {
        echo "Test: Get upline chain stops at 5 levels... ";
        
        try {
            // Create 7-level hierarchy
            $users = [];
            $users[0] = $this->userManager->createUser('u0_' . time(), 'u0' . time() . '@test.com', null);
            
            for ($i = 1; $i <= 6; $i++) {
                $users[$i] = $this->userManager->createUser(
                    'u' . $i . '_' . time(),
                    'u' . $i . time() . '@test.com',
                    $users[$i - 1]
                );
                $this->testUserIds[] = $users[$i];
            }
            $this->testUserIds[] = $users[0];

            // Get upline chain from level 7 (should return only 5 levels)
            $upline = $this->userManager->getUplineChain($users[6], 5);

            if (count($upline) === 5) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Should return exactly 5 levels (got " . count($upline) . ")\n";
            }
        } catch (Exception $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test: Get user level
     */
    private function testGetUserLevel(): void
    {
        echo "Test: Get user level... ";
        
        try {
            // Create 4-level hierarchy
            $l1 = $this->userManager->createUser('lv1_' . time(), 'lv1' . time() . '@test.com', null);
            $l2 = $this->userManager->createUser('lv2_' . time(), 'lv2' . time() . '@test.com', $l1);
            $l3 = $this->userManager->createUser('lv3_' . time(), 'lv3' . time() . '@test.com', $l2);
            $l4 = $this->userManager->createUser('lv4_' . time(), 'lv4' . time() . '@test.com', $l3);
            
            $this->testUserIds[] = $l1;
            $this->testUserIds[] = $l2;
            $this->testUserIds[] = $l3;
            $this->testUserIds[] = $l4;

            $level1 = $this->userManager->getUserLevel($l1);
            $level2 = $this->userManager->getUserLevel($l2);
            $level3 = $this->userManager->getUserLevel($l3);
            $level4 = $this->userManager->getUserLevel($l4);

            if ($level1 === 1 && $level2 === 2 && $level3 === 3 && $level4 === 4) {
                echo "PASSED\n";
            } else {
                echo "FAILED: Levels incorrect ($level1, $level2, $level3, $level4)\n";
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
            
            // Delete in reverse order to handle foreign key constraints
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
    $test = new UserManagerTest();
    $test->runAll();
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}
