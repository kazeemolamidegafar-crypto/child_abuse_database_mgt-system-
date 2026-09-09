<?php
require_once __DIR__ . '/config.php';

if (!is_logged_in()) {
    redirect('/login.php');
}

switch (current_role()) {
    case 'administrator':
        redirect('/admin/dashboard.php');
    case 'caseworker':
        redirect('/caseworker/dashboard.php');
    case 'intake_officer':
        redirect('/intake/dashboard.php');
    default:
        forbidden();
}
