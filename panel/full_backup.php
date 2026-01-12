<?php
// panel/full_backup.php - UNIVERSAL BACKUP (Fixed for HPanel)
// Features: Embedded Auth, ZipArchive, Drive Sync, No Timeouts

// 1. Server Config
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0); // Unlimited time
ini_set('memory_limit', '1024M'); // 1GB Ram

// 2. Include Helpers (DB & Settings)
if (file_exists(__DIR__ . '/../includes/helpers.php')) {
    require_once __DIR__ . '/../includes/helpers.php';
} else {
    // Fallback DB connection if helper missing
    die("Error: Core files missing.");
}

// 3. EMBEDDED AUTH CHECK (Fixes Fatal Error)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}

// 4. Setup Paths
$rootPath = realpath(__DIR__ . '/../');
$filename = 'Full_Backup_' . date('Y-m-d_H-i') . '.zip';
$zipPath  = __DIR__ . '/' . $filename;

// 5. Create Zip (Using PHP ZipArchive - Safe for HPanel)
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Error: Cannot create zip. Check folder permissions (chmod 777 on panel folder).");
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        
        // Fix Path & Exclusions
        if (!$filePath) continue; 
        
        $relativePath = substr($filePath, strlen($rootPath) + 1);

        // Skip Loops & Trash
        if (strpos($filePath, $filename) !== false) continue; // Skip self
        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'zip') continue; // Skip other zips
        if (strpos($filePath, 'error_log') !== false) continue;
        if (strpos($filePath, '.git') !== false) continue;
        if (strpos($filePath, 'node_modules') !== false) continue;

        $zip->addFile($filePath, $relativePath);
    }
}
$zip->close();

// 6. Google Drive Auto-Upload Logic
if (file_exists($zipPath) && isset($GLOBALS['settings']['gdrive_enabled']) && $GLOBALS['settings']['gdrive_enabled'] == '1') {
    
    // Check credentials
    $clientId = $GLOBALS['settings']['gdrive_client_id'] ?? '';
    $clientSecret = $GLOBALS['settings']['gdrive_client_secret'] ?? '';
    $refreshToken = $GLOBALS['settings']['gdrive_refresh_token'] ?? '';
    $folderId = $GLOBALS['settings']['gdrive_folder_id'] ?? '';

    if ($clientId && $clientSecret && $refreshToken && $folderId) {
        try {
            // NOTE: Requires Google API Client Library installed via Composer
            // If library missing, this block is skipped to prevent crash
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
                
                $client = new Google_Client();
                $client->setClientId($clientId);
                $client->setClientSecret($clientSecret);
                $client->refreshToken($refreshToken);
                
                $service = new Google_Service_Drive($client);
                $fileMetadata = new Google_Service_Drive_DriveFile([
                    'name' => $filename,
                    'parents' => [$folderId]
                ]);
                
                $content = file_get_contents($zipPath);
                $service->files->create($fileMetadata, [
                    'data' => $content,
                    'mimeType' => 'application/zip',
                    'uploadType' => 'multipart',
                    'fields' => 'id'
                ]);
            }
        } catch (Exception $e) {
            // Silent fail for Drive to allow Download to continue
            error_log("Drive Upload Failed: " . $e->getMessage());
        }
    }
}

// 7. Force Download (Direct Stream)
if (file_exists($zipPath)) {
    // Clean buffer to prevent corruption
    while (ob_get_level()) ob_end_clean();
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.basename($zipPath).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($zipPath));
    
    readfile($zipPath);
    
    // Delete after sending
    unlink($zipPath);
    exit;
} else {
    echo "Error: Backup generation failed.";
}
?>