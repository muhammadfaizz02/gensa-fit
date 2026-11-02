<?php
// auth/logout.php
require_once __DIR__ . '/../config/database.php';
session_destroy();
redirect(base_url('auth/login.php'));