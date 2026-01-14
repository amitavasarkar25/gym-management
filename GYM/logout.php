<?php
session_start();
session_destroy(); // Kills the session
header("Location: login.php"); // Redirects to Login
exit();
?>