<?php

class SalesManager
{
    private PDO $pdo;
    private UserManager $userManager;

    public function __construct(?PDO $pdo = null, ?UserManager $userManager = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->userManager = $userManager ?? new UserManager($this->pdo);
    }

    public function recordSale(int $userId, float $amount)
    {
        if (!Validator::validateUserId($userId)) {
            throw new InvalidArgumentException('User does not exist.');
        }

        if (!$this->validateSaleAmount($amount)) {
            throw new InvalidArgumentException('Invalid sale amount.');
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO sales (user_id, amount) VALUES (?, ?)'
            );

            $success = $stmt->execute([$userId, $amount]);

            if ($success) {
                return (int)$this->pdo->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            error_log('Sale recording error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function validateSaleAmount($amount): bool
    {
        return Validator::validateSaleAmount($amount);
    }

    public function getSaleById(int $saleId)
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT s.id, s.user_id, s.amount, s.created_at,
                        u.username, u.email
                 FROM sales s
                 JOIN users u ON s.user_id = u.id
                 WHERE s.id = ?'
            );
            
            $stmt->execute([$saleId]);
            $sale = $stmt->fetch();

            return $sale ?: false;

        } catch (PDOException $e) {
            error_log('Get sale error: ' . $e->getMessage());
            return false;
        }
    }

    public function getUserSales(int $userId, ?int $limit = null): array
    {
        try {
            $sql = 'SELECT id, user_id, amount, created_at 
                    FROM sales 
                    WHERE user_id = ?
                    ORDER BY created_at DESC';
            
            if ($limit !== null && $limit > 0) {
                $sql .= ' LIMIT ' . (int)$limit;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log('Get user sales error: ' . $e->getMessage());
            return [];
        }
    }

    public function getAllSales(?int $limit = null): array
    {
        try {
            $sql = 'SELECT s.id, s.user_id, s.amount, s.created_at,
                           u.username, u.email
                    FROM sales s
                    JOIN users u ON s.user_id = u.id
                    ORDER BY s.created_at DESC';
            
            if ($limit !== null && $limit > 0) {
                $sql .= ' LIMIT ' . (int)$limit;
            }

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log('Get all sales error: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserTotalSales(int $userId): float
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COALESCE(SUM(amount), 0) as total
                 FROM sales
                 WHERE user_id = ?'
            );
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return (float)$result['total'];

        } catch (PDOException $e) {
            error_log('Get user total sales error: ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getUserSalesCount(int $userId): int
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) as count
                 FROM sales
                 WHERE user_id = ?'
            );
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return (int)$result['count'];

        } catch (PDOException $e) {
            error_log('Get user sales count error: ' . $e->getMessage());
            return 0;
        }
    }

    public function recordSaleWithPayouts(int $userId, float $amount): array
    {
        try {
            $this->pdo->beginTransaction();

            $saleId = $this->recordSale($userId, $amount);

            if (!$saleId) {
                throw new RuntimeException('Failed to record sale.');
            }

            $payoutCalculator = new PayoutCalculator($this->pdo, $this->userManager);
            $payouts = $payoutCalculator->calculatePayouts($saleId, $userId, $amount);

            $this->pdo->commit();

            return [
                'success' => true,
                'sale_id' => $saleId,
                'amount' => $amount,
                'user_id' => $userId,
                'payouts' => $payouts,
                'payout_count' => count($payouts)
            ];

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('Sale with payouts error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
