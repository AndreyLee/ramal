<?php
// This script is intended to be run from the command line or a secure environment.
// Do not expose it to the web.

if (php_sapi_name() !== 'cli' && php_sapi_name() !== 'cgi-fcgi') {
    die("This script can only be run from the command line.");
}

if ($argc < 2) {
    echo "Usage: php generate_password_hash.php <password>\n";
    exit(1);
}

$password = $argv[1];
$hash = password_hash($password, PASSWORD_DEFAULT);

if ($hash === false) {
    echo "Error hashing password.\n";
    exit(1);
}

echo "Password: " . $password . "\n";
echo "Hashed Password: " . $hash . "\n";

// Example of how to verify (for testing purposes):
// if (password_verify($password, $hash)) {
//     echo "Password verified successfully.\n";
// } else {
//     echo "Password verification failed.\n";
// }
?>
