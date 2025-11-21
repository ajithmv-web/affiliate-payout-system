<?php
$configFile = __DIR__ . '/config/database.php';
$dbConfigured = file_exists($configFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate Payout System</title>
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
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 25px;
            font-size: 14px;
        }
        .status {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 25px;
            border-left: 4px solid;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        .links {
            list-style: none;
        }
        .links li {
            margin-bottom: 10px;
        }
        .links a {
            display: block;
            padding: 12px 15px;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            transition: all 0.2s;
        }
        .links a:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .feature {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .feature h3 {
            color: #007bff;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .feature p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #999;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Multi-Level Affiliate Payout System</h1>
        <p class="subtitle">5-Level Commission Management System</p>

        <?php if ($dbConfigured): ?>
            <div class="status success">
                <strong>System Ready</strong> - Database configured successfully
            </div>
        <?php else: ?>
            <div class="status warning">
                <strong>Setup Required</strong> - Please configure database in <code>config/database.php</code>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2>Quick Links</h2>
            <ul class="links">
                <li><a href="database/setup.php">Setup Database</a></li>
                <li><a href="examples/demo.php">Run Demo</a></li>
                <li><a href="examples/view_data.php">View Data</a></li>
            </ul>
        </div>

        <div class="section">
            <h2>Commission Rates</h2>
            <div class="features">
                <div class="feature">
                    <h3>Level 1 - Direct Parent</h3>
                    <p>10% commission</p>
                </div>
                <div class="feature">
                    <h3>Level 2 - Grandparent</h3>
                    <p>5% commission</p>
                </div>
                <div class="feature">
                    <h3>Level 3 - Great-Grandparent</h3>
                    <p>3% commission</p>
                </div>
                <div class="feature">
                    <h3>Level 4</h3>
                    <p>2% commission</p>
                </div>
                <div class="feature">
                    <h3>Level 5</h3>
                    <p>1% commission</p>
                </div>
            </div>
        </div>

        <div class="footer">
            Multi-Level Affiliate Payout System
        </div>
    </div>
</body>
</html>
