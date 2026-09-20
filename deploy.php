<?php
   /* // Path to your repository
    $repo_path = '/home/daceuabp/public_html/www.shopafricanattire.com';

    // Pull latest changes
    exec("cd {$repo_path} && git pull origin main", $output);

    // Log output
    file_put_contents('deploy.log', implode("\n", $output));

    echo "Deployment Successful!"; //response*/


    // 1. Security Check: Verify GitHub Secret (Highly Recommended)
    define('GITHUB_SECRET', 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQCxAwM3ltjPUDkFCGJlCavNnL8KKApElPHhNgRQLNzqlQs20lIUpL3nXiBR1/8FMSS5cwAtn1I8XgTmz6treucr2El4CJz2yk5QdBPj1mF4DB5rerBfny0F1S6bZKRinjgb+lV8766dvUZ6M5Vqt+dCNbAG7W3DKIF/Y2BHHzIaAO5/LIiD8jGhAaArHBrphaX4JP68B1XyBvzVFcPOZloqSWNG05T1c6DrYEZzlti1mVhvd2IBe58BCbLAk7vgmcEyuowinv3Q7UWrIHsi0qpRKPlkQrG6jDGVxGpmYbrGIIXBLLwKOATYD8EXGebgehNiT/kL86XCaPXWpDcQmKex'); 

    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $payload = file_get_contents('php://input');

    if (GITHUB_SECRET !== 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQCxAwM3ltjPUDkFCGJlCavNnL8KKApElPHhNgRQLNzqlQs20lIUpL3nXiBR1/8FMSS5cwAtn1I8XgTmz6treucr2El4CJz2yk5QdBPj1mF4DB5rerBfny0F1S6bZKRinjgb+lV8766dvUZ6M5Vqt+dCNbAG7W3DKIF/Y2BHHzIaAO5/LIiD8jGhAaArHBrphaX4JP68B1XyBvzVFcPOZloqSWNG05T1c6DrYEZzlti1mVhvd2IBe58BCbLAk7vgmcEyuowinv3Q7UWrIHsi0qpRKPlkQrG6jDGVxGpmYbrGIIXBLLwKOATYD8EXGebgehNiT/kL86XCaPXWpDcQmKex') {
        $hash = 'sha256=' . hash_hmac('sha256', $payload, GITHUB_SECRET);
        if (!hash_equals($hash, $signature)) {
            http_response_code(403);
            die('Access Denied: Invalid Signature');
        }
    }

    // 2. Configuration
    $repo_path = '/home/daceuabp/public_html/www.shopafricanattire.com';
    $git_bin   = '/usr/bin/git';

    // 3. Execution (The Git Pull Command)
    $command = "cd " . escapeshellarg($repo_path) . " && {$git_bin} pull origin main 2>&1";
    exec($command, $output, $return_var);

    // 4. Logging & Response
    if ($return_var === 0) {
        http_response_code(200);
        echo "Success: Repository updated.";
    } else {
        http_response_code(500);
        echo "Error: Git pull failed.";
        // Log details securely on the server instead of printing them to the web
        error_log("Git Webhook Failure: " . implode("\n", $output)); //
    }


?>
