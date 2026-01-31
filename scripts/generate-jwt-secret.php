<?php
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die(".env file not found\n");
}

$content = file_get_contents($envFile);

if (preg_match('/^JWT_SECRET=.*/m', $content)) {
    $content = preg_replace('/^JWT_SECRET=.*/m', 'JWT_SECRET=' . base64_encode(random_bytes(32)), $content);
} else {
    $content .= "\nJWT_SECRET=" . base64_encode(random_bytes(32));
}

file_put_contents($envFile, $content, LOCK_EX);
echo "JWT_SECRET generated and saved to .env\n";
