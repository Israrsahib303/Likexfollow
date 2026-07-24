<?php
// fix_flash_db.php
require_once 'includes/db.php';

echo "<h1>🔥 Setting up Flash Deal System...</h1>";

try {
    // 1. Create Flash Sales Table
    $sql = "CREATE TABLE IF NOT EXISTS `flash_sales` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `type` enum('smm','digital') NOT NULL DEFAULT 'smm',
      `item_id` int(11) NOT NULL,
      `item_name` varchar(255) NOT NULL,
      `original_price` decimal(10,4) NOT NULL,
      `discounted_price` decimal(10,4) NOT NULL,
      `start_time` datetime NOT NULL,
      `end_time` datetime NOT NULL,
      `status` enum('active','expired') NOT NULL DEFAULT 'active',
      `claimed_count` int(11) DEFAULT 0,
      `max_claims` int(11) DEFAULT 50,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $db->exec($sql);
    echo "<p>✅ Table 'flash_sales' created.</p>";

    // 2. Create Flash Orders Log (Takay track rahe)
    $sql2 = "CREATE TABLE IF NOT EXISTS `flash_orders` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `flash_id` int(11) NOT NULL,
      `amount_paid` decimal(10,4) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sql2);
    echo "<p>✅ Table 'flash_orders' created.</p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
echo "<h3>✨ Setup Complete! Delete this file.</h3>";
?>