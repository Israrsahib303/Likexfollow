<?php
// panel/db_backup.php - ADVANCED STREAMING SQL BACKUP
// Optimized for CPanel/HPanel - No 500 Errors
// Features: Direct Stream, Low Memory Usage, UTF-8 Support

// 1. Settings & Headers
ini_set('memory_limit', '512M'); // Increase just in case
set_time_limit(0);               // No timeout
error_reporting(0);              // Hide errors in backup file

require_once __DIR__ . '/_auth_check.php';
require_once __DIR__ . '/../includes/config.php';

// 2. Disable Output Buffering (Crucial for Streaming)
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
if (ini_get('zlib.output_compression')) {
    @ini_set('zlib.output_compression', 'Off');
}
while (ob_get_level()) { ob_end_clean(); }

// 3. Database Connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die("Database Connection Error: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

// 4. Send Download Headers Immediately
$filename = "backup_" . DB_NAME . "_" . date("Y-m-d_H-i") . ".sql";

header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"" . $filename . "\"");
header('Pragma: no-cache');
header('Expires: 0');

// Helper to write immediately to output
function output($str) {
    echo $str;
    flush(); // Push to browser immediately
}

// 5. Start Backup Generation
output("-- Beast9 Advanced Database Backup\n");
output("-- Server: " . $_SERVER['SERVER_NAME'] . "\n");
output("-- Date: " . date('Y-m-d H:i:s') . "\n");
output("-- --------------------------------------------------------\n\n");
output("SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
output("SET time_zone = \"+00:00\";\n\n");

// Get Tables
$tables = [];
$result = $mysqli->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

// Loop Tables
foreach ($tables as $table) {
    // Structure
    output("-- Table structure for table `$table`\n");
    output("DROP TABLE IF EXISTS `$table`;\n");
    
    $row2 = $mysqli->query("SHOW CREATE TABLE `$table`")->fetch_row();
    output($row2[1] . ";\n\n");

    // Data
    output("-- Dumping data for table `$table`\n");
    
    // Process Large Tables in Chunks (Prevent RAM Overflow)
    $result = $mysqli->query("SELECT * FROM `$table`", MYSQLI_USE_RESULT); // Unbuffered Query
    
    if ($result) {
        $num_fields = $result->field_count;
        
        while ($row = $result->fetch_row()) {
            output("INSERT INTO `$table` VALUES(");
            for ($j = 0; $j < $num_fields; $j++) {
                $row[$j] = isset($row[$j]) ? $mysqli->real_escape_string($row[$j]) : null;
                
                if (isset($row[$j])) {
                    output('"' . $row[$j] . '"');
                } else {
                    output("NULL");
                }
                
                if ($j < ($num_fields - 1)) {
                    output(",");
                }
            }
            output(");\n");
        }
        $result->free();
    }
    output("\n");
}

output("\n-- Backup Completed Successfully.");
exit;
?>