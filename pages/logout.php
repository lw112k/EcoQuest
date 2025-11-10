<?php
// logout.php
// This file handles the secure termination of the user's session.

// 1. Ensure the session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Unset all session variables (clears the $_SESSION superglobal)
$_SESSION = array();

// 3. Destroy the session (removes the session file/cookie)
session_destroy();

// 4. Redirect the user back to the home page (index.php)
// After redirection, the browser loads index.php, which will now see
// that $_SESSION['user_id'] is unset, and correctly display Login/Register.
header("Location: index.php");
exit();
?>