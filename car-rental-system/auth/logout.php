<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Destroy session
$_SESSION = [];
session_unset();
session_destroy();

// Redirect to home page
header("Location: ../index.php"); // <-- go back to main index
exit;
