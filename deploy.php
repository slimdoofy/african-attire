<?php
    // Path to your repository
    $repo_path = '/home/daceuabp/public_html/www.shopafricanattire.com';

    // Pull latest changes
    exec("cd {$repo_path} && git pull origin main", $output);

    // Log output
    file_put_contents('deploy.log', implode("\n", $output));

    echo "Deployment Successful!"; //response
?>
