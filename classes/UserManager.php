<?php

class UserManager
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function createUser(string $username, string $email, ?int $parentId = null)
    {
        if (!Validator::validateUsername($username)) {
            throw new InvalidArgumentException('Invalid username.');
        }

        if (!Validator::validateEmail($email)) {
            throw new InvalidArgumentException('Invalid email format.');
        }

        if ($parentId !== null && !$this->validateParentExists($parentId)) {
            throw new InvalidArgumentException('Parent user does not exist.');
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (username, email, parent_id) VALUES (?, ?, ?)'
            );

            $success = $stmt->execute([$username, $email, $parentId]);

            if ($success) {
                return (int)$this->pdo->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new InvalidArgumentException('Username or email already exists.');
            }
            error_log('User creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getUserById(int $userId)
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, username, email, parent_id, created_at 
                 FROM users 
                 WHERE id = ?'
            );
            
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            return $user ?: false;

        } catch (PDOException $e) {
            error_log('Get user error: ' . $e->getMessage());
            return false;
        }
    }

    public function validateParentExists(int $parentId): bool
    {
        return Validator::validateUserId($parentId);
    }

    public function getChildren(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, username, email, parent_id, created_at 
                 FROM users 
                 WHERE parent_id = ?
                 ORDER BY created_at ASC'
            );
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log('Get children error: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserLevel(int $userId): int
    {
        $level = 1;
        $currentUserId = $userId;

        while (true) {
            $user = $this->getUserById($currentUserId);
            
            if (!$user || $user['parent_id'] === null) {
                break;
            }

            $level++;
            $currentUserId = $user['parent_id'];

            if ($level > 100) {
                error_log('Possible circular reference detected for user ' . $userId);
                break;
            }
        }

        return $level;
    }

    public function getUplineChain(int $userId, int $maxLevels = 5): array
    {
        $uplineChain = [];
        $currentUserId = $userId;
        $level = 0;

        while ($level < $maxLevels) {
            $user = $this->getUserById($currentUserId);
            
            if (!$user || $user['parent_id'] === null) {
                break;
            }

            $level++;
            $parentId = $user['parent_id'];
            $parentUser = $this->getUserById($parentId);
            
            if ($parentUser) {
                $uplineChain[] = [
                    'user_id' => $parentUser['id'],
                    'level' => $level,
                    'username' => $parentUser['username'],
                    'email' => $parentUser['email']
                ];
                
                $currentUserId = $parentId;
            } else {
                break;
            }

            if ($level > 100) {
                error_log('Possible circular reference in upline chain for user ' . $userId);
                break;
            }
        }

        return $uplineChain;
    }

    public function getAllUsers(): array
    {
        try {
            $stmt = $this->pdo->query(
                'SELECT id, username, email, parent_id, created_at 
                 FROM users 
                 ORDER BY created_at ASC'
            );
            
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log('Get all users error: ' . $e->getMessage());
            return [];
        }
    }
}
