<?php
require __DIR__ . '/povezava.php';

$_SESSION = [];
session_unset();
session_destroy();

header('Location: index');
exit;
