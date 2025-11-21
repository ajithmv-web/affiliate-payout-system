
# Installation

# 1. Clone or Download
git clone https://github.com/ajithmv-web/affiliate-payout-system.git
cd affiliate-payout-system

# 2. Configure Database

Edit config/database.php with your database credentials:

return [
    'host' => 'localhost',
    'database' => 'affiliate_system',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
];

# 3. Create Database and Tables

http://localhost/affiliate-payout-system/database/setup.php?sample

# Step 4: Test the System

http://localhost/affiliate-payout-system/

# Run Demo

http://localhost/affiliate-payout-system/examples/demo.php

# View Data

http://localhost/affiliate-payout-system/examples/view_data.php

# Run Tests
http://localhost/affiliate-payout-system/tests/IntegrationTest.php


