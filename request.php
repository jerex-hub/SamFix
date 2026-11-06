<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

start_secure_session();

$pageTitle = 'Request a Repair';
$fields = ['customer_name', 'email', 'phone', 'device_brand',
'device_model', 'issue', 'notes', 'service_id'];
$old = array_fill_keys($fields, '');
$errors = [];
$formError = '';
$services = [];

?>