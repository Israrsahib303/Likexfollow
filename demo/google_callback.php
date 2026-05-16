<?php
// google_callback.php - Final Mobile Viewport Fix using JS Redirect
require_once 'includes/helpers.php';
require_once 'includes/google_config.php';

// Helper function for secure API calls
function http_get_secure($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SubHub-Login');
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// Redirect Function with Viewport Fix
function mobile_redirect($url) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>Redirecting...</title>
        <style>body{background:#f8fafc; font-family:sans-serif; text-align:center; padding-top:50px;}</style>
    </head>
    <body>
        <p>Loading...</p>
        <script>
            window.location.href = "' . $url . '";
        </script>
    </body>
    </html>';
    exit;
}

if (isset($_GET['code'])) {
    // 1. Get Token
    $token_url = 'https://oauth2.googleapis.com/token';
    $post_data = [
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URL,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        // 2. Get User Profile
        $user_info_url = 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $token_data['access_token'];
        $user_info_raw = http_get_secure($user_info_url);
        $user_info = json_decode($user_info_raw, true);

        if (isset($user_info['email'])) {
            $email = $user_info['email'];
            $name = $user_info['name'];
            $google_id = $user_info['id'];

            // 3. Check Database
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // --- 🔒 BAN CHECK ---
                if (isset($user['status']) && $user['status'] === 'banned') {
                    // Use Mobile Redirect function specifically for Popup
                    mobile_redirect("login.php?banned=1&email=" . urlencode($email));
                }
                
                // USER EXISTS -> LOGIN
                if (empty($user['google_id'])) {
                    $db->prepare("UPDATE users SET google_id = ?, name = COALESCE(name, ?) WHERE id = ?")->execute([$google_id, $name, $user['id']]);
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['role'] = $user['role'] ?? ($user['is_admin'] ? 'admin' : 'user');
                
                // Safe Mobile Redirect
                $target = $user['is_admin'] ? 'panel/index.php' : 'user/index.php';
                mobile_redirect($target);

            } else {
                // USER NEW -> REGISTER
                $random_pass = bin2hex(random_bytes(8)); 
                $hash = password_hash($random_pass, PASSWORD_DEFAULT);

                $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, google_id, is_admin, role, created_at) VALUES (?, ?, ?, ?, 0, 'user', NOW())");
                $stmt->execute([$name, $email, $hash, $google_id]);
                
                $new_user_id = $db->lastInsertId();

                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['email'] = $email;
                $_SESSION['is_admin'] = 0;
                $_SESSION['role'] = 'user';

                // Safe Mobile Redirect
                mobile_redirect("user/index.php?welcome=1");
            }
        }
    }
}
// Fail Safe
mobile_redirect("login.php?error=google_failed");
?>
