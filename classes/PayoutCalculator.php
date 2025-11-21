<?php

class PayoutCalculator
{
    private PDO $pdo;
    private UserManager $userManager;

    private const COMMISSION_RATES = [
        1 => 10.0,
        2 => 5.0,
        3 => 3.0,
        4 => 2.0,
        5 => 1.0
    ];

    private const MAX_LEVELS = 5;

    public function __construct(?PDO $pdo = null, ?UserManager $userManager = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->userManager = $userManager ?? new UserManager($this->pdo);
    }

    public function getCommissionRate(int $level): float
    {
        if ($level < 1 || $level > self::MAX_LEVELS) {
            throw new InvalidArgumentException('Commission level must be between 1 and 5.');
        }

        return self::COMMISSION_RATES[$level];
    }

    public function calculatePayouts(int $saleId, int $userId, float $amount): array
    {
        if (!Validator::validatePositiveInteger($saleId)) {
            throw new InvalidArgumentException('Invalid sale ID.');
        }

        if (!Validator::validateUserId($userId)) {
            throw new InvalidArgumentException('User does not exist.');
        }

        if (!Validator::validateSaleAmount($amount)) {
            throw new InvalidArgumentException('Invalid sale amount.');
        }

        $uplineChain = $this->userManager->getUplineChain($userId, self::MAX_LEVELS);
        $payouts = [];

        foreach ($uplineChain as $uplineUser) {
            $level = $uplineUser['level'];
            $uplineUserId = $uplineUser['user_id'];
            $commissionRate = $this->getCommissionRate($level);
            $commissionAmount = $this->calculateCommissionAmount($amount, $commissionRate);
            $payoutId = $this->createPayoutRecord($saleId, $uplineUserId, $commissionAmount, $level);

            if ($payoutId) {
                $payouts[] = [
                    'payout_id' => $payoutId,
                    'user_id' => $uplineUserId,
                    'username' => $uplineUser['username'],
                    'level' => $level,
                    'amount' => $commissionAmount,
                    'rate' => $commissionRate
                ];
            }
        }

        return $payouts;
    }

    private function calculateCommissionAmount(float $saleAmount, float $commissionRate): float
    {
        $commission = $saleAmount * ($commissionRate / 100);
        return round($commission, 2);
    }

    public function createPayoutRecord(int $saleId, int $userId, float $amount, int $level)
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO payouts (sale_id, user_id, amount, level) 
                 VALUES (?, ?, ?, ?)'
            );

            $success = $stmt->execute([$saleId, $userId, $amount, $level]);

            if ($success) {
                return (int)$this->pdo->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            error_log('Payout record creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getUserPayouts(int $userId, ?int $limit = null): array
    {
        try {
            $sql = 'SELECT p.id, p.sale_id, p.user_id, p.amount, p.level, p.created_at,
                           s.amount as sale_amount, s.user_id as seller_id,
                           u.username as seller_username
                    FROM payouts p
                    JOIN sales s ON p.sale_id = s.id
                    JOIN users u ON s.user_id = u.id
                    WHERE p.user_id = ?
                    ORDER BY p.created_at DESC';
            
            if ($limit !== null && $limit > 0) {
                $sql .= ' LIMIT ' . (int)$limit;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log('Get user payouts error: ' . $e->getMessage());
            return [];
        }
    }

    public function getSalePayouts(int $saleId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT p.id, p.sale_id, p.user_id, p.amount, p.level, p.created_at,
                        u.username, u.email
                 FROM payouts p
                 JOIN users u ON p.user_id = u.id
                 WHERE p.sale_id = ?
                 ORDER BY p.level ASC'
            );
            
            $stmt->execute([$saleId]);
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log('Get sale payouts error: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserTotalPayouts(int $userId): float
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COALESCE(SUM(amount), 0) as total
                 FROM payouts
                 WHERE user_id = ?'
            );
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return (float)$result['total'];

        } catch (PDOException $e) {
            error_log('Get user total payouts error: ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getUserPayoutCount(int $userId): int
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) as count
                 FROM payouts
                 WHERE user_id = ?'
            );
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return (int)$result['count'];

        } catch (PDOException $e) {
            error_log('Get user payout count error: ' . $e->getMessage());
            return 0;
        }
    }
}
