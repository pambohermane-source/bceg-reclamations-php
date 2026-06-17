<?php
require_once __DIR__ . '/includes/auth.php';
if (estConnecte()) { header('Location: /dashboard.php'); } else { header('Location: /login.php'); }
exit();
