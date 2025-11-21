<?php

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Validator.php';
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/SalesManager.php';
require_once __DIR__ . '/../classes/PayoutCalculator.php';

$userManager = new UserManager();
$salesManager = new SalesManager();
$payoutCalculator = new PayoutCalculator();

$users = $userManager->getAllUsers();
$sales = $salesManager->getAllSales();

$pdo = Database::getConnection();
$stmt = $pdo->query(
    'SELECT p.id, p.sale_id, p.user_id, u.username, p.level, p.amount, p.created_at
     FROM payouts p
     JOIN users u ON p.user_id = u.id
     ORDER BY p.created_at DESC'
);
$payouts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Data - Affiliate System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        h2 {
            color: #007bff;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: normal;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .empty {
            text-align: center;
            padding: 20px;
            color: #999;
        }
        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .summary-item:last-child {
            border-bottom: none;
        }
        .summary-label {
            font-weight: bold;
            color: #333;
        }
        .summary-value {
            color: #007bff;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../index.php" class="back-link">&larr; Back to Home</a>
        
        <h1>System Data</h1>

        <h2>Users</h2>
        <?php if (empty($users)): ?>
            <p class="empty">No users found</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Parent ID</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= $user['parent_id'] ? htmlspecialchars($user['parent_id']) : '-' ?></td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Sales</h2>
        <?php if (empty($sales)): ?>
            <p class="empty">No sales found</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Amount</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?= htmlspecialchars($sale['id']) ?></td>
                            <td><?= htmlspecialchars($sale['user_id']) ?></td>
                            <td><?= htmlspecialchars($sale['username']) ?></td>
                            <td>$<?= number_format($sale['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($sale['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Payouts</h2>
        <?php if (empty($payouts)): ?>
            <p class="empty">No payouts found</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sale ID</th>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Level</th>
                        <th>Amount</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payouts as $payout): ?>
                        <tr>
                            <td><?= htmlspecialchars($payout['id']) ?></td>
                            <td><?= htmlspecialchars($payout['sale_id']) ?></td>
                            <td><?= htmlspecialchars($payout['user_id']) ?></td>
                            <td><?= htmlspecialchars($payout['username']) ?></td>
                            <td><?= htmlspecialchars($payout['level']) ?></td>
                            <td>$<?= number_format($payout['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($payout['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="summary">
            <h2 style="margin-top: 0;">Summary</h2>
            <div class="summary-item">
                <span class="summary-label">Total Users:</span>
                <span class="summary-value"><?= count($users) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Sales:</span>
                <span class="summary-value"><?= count($sales) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Payouts:</span>
                <span class="summary-value"><?= count($payouts) ?></span>
            </div>
            <?php if (!empty($sales)): ?>
                <div class="summary-item">
                    <span class="summary-label">Total Sales Amount:</span>
                    <span class="summary-value">$<?= number_format(array_sum(array_column($sales, 'amount')), 2) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($payouts)): ?>
                <div class="summary-item">
                    <span class="summary-label">Total Payouts Amount:</span>
                    <span class="summary-value">$<?= number_format(array_sum(array_column($payouts, 'amount')), 2) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
