<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

logout_user();
set_flash('success', 'You have been successfully logged out.');
redirect('pages/login.php');
