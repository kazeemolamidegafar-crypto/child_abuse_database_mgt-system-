<?php
$code = http_response_code();
$message = $code === 403
    ? 'You do not have permission to access this resource.'
    : 'The requested page was not found.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Error <?= (int) $code ?></title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<main class="container">
    <div class="login-wrap">
        <div class="login-card">
            <h1><?= (int) $code ?></h1>
            <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <p><a href="/index.php">Return to dashboard</a></p>
        </div>
    </div>
</main>
</body>
</html>
