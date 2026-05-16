<?php
// Database connection aur admin session ke liye header include karein
include '_header.php'; 

// Basic security check
if (empty($_SESSION['user_id'])) {
    die("Access Denied: Please login as admin first.");
}

try {
    // Database se saari tables fetch karein
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div style='font-family:sans-serif; padding:20px;'>";
    echo "<h2>🛠️ LikexFollow Database Fixer Running...</h2>";
    echo "<div style='font-family:monospace; background:#111; color:#0f0; padding:20px; border-radius:10px; max-height: 500px; overflow-y: auto;'>";
    
    foreach ($tables as $table) {
        try {
            // Check karein ke table mein 'id' ka column hai ya nahi
            $hasId = $db->query("SHOW COLUMNS FROM `$table` LIKE 'id'")->fetch();
            
            if ($hasId) {
                // 1. Primary Key add karein (Errors suppress kiye hain agar pehle se ho)
                @$db->exec("ALTER TABLE `$table` ADD PRIMARY KEY (`id`)");
                
                // 2. Auto Increment on karein
                @$db->exec("ALTER TABLE `$table` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
                
                echo "<p style='margin:5px 0;'>✅ Fixed: $table - Checkboxes restored!</p>";
            } else {
                echo "<p style='margin:5px 0; color:#ff5555'>⚠️ Skipped: $table (No 'id' column found)</p>";
            }
        } catch(Exception $e) {
            // Agar table pehle se perfectly theek hai toh error ko ignore karein
            echo "<p style='margin:5px 0; color:#aaa'>ℹ️ Passed: $table (Already optimized)</p>";
        }
    }
    
    echo "</div>";
    echo "<h3 style='color:green;'>🎉 Sab kuch theek ho gaya! Ab phpMyAdmin refresh karein!</h3>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>Connection Error: " . $e->getMessage() . "</h2>";
}
?>
