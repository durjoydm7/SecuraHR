<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logoutUser();
setFlash('info', 'You have been successfully logged out.');
header('Location: /SecuraHR/login.php');
exit;