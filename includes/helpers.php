<?php

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function ensure_session_started()
{
    if (session_status() === PHP_SESSION_NONE) {
        if (headers_sent()) {
            throw new RuntimeException(
                'Session cannot start because headers have already been sent.'
            );
        }

        session_start();
    }
}

function csrf_token()
{
    ensure_session_started();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token)
{
    ensure_session_started();

    if (
        !is_string($token) ||
        empty($_SESSION['csrf_token'])
    ) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function validate_request_form($data)
{
    $errors = [];

    $customerName = trim($data['customer_name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $deviceModel = trim($data['device_model'] ?? '');
    $issueDescription = trim($data['issue_description'] ?? '');
    $serviceId = $data['service_id'] ?? '';

    if ($customerName === '') {
        $errors['customer_name'] = 'Customer name is required.';
    } elseif (strlen($customerName) > 100) {
        $errors['customer_name'] = 'Customer name is too long.';
    }

    if ($phone === '') {
        $errors['phone'] = 'Phone number is required.';
    } elseif (
        !preg_match('/^[0-9+\-\s()]+$/', $phone) ||
        strlen(preg_replace('/\D/', '', $phone)) < 7 ||
        strlen(preg_replace('/\D/', '', $phone)) > 15
    ) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }

    if (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($deviceModel === '') {
        $errors['device_model'] = 'Device model is required.';
    } elseif (strlen($deviceModel) > 100) {
        $errors['device_model'] = 'Device model is too long.';
    }

    if ($issueDescription === '') {
        $errors['issue_description'] = 'Please describe the device issue.';
    } elseif (strlen($issueDescription) > 2000) {
        $errors['issue_description'] = 'Description is too long.';
    }

    if (
        $serviceId !== '' &&
        (!ctype_digit((string) $serviceId) || (int) $serviceId < 1)
    ) {
        $errors['service_id'] = 'Please select a valid service.';
    }

    return $errors;
}

function generate_request_code($requestId)
{
    $requestId = filter_var(
        $requestId,
        FILTER_VALIDATE_INT
    );

    if ($requestId === false || $requestId < 1) {
        throw new InvalidArgumentException(
            'A valid repair request ID is required.'
        );
    }

    $date = date('Ymd');

    $number = str_pad(
        (string) $requestId,
        5,
        '0',
        STR_PAD_LEFT
    );

    return 'SF-' . $date . '-' . $number;
}

function set_flash($type, $message)
{
    ensure_session_started();

    $allowedTypes = [
        'success',
        'error',
        'warning',
        'info'
    ];

    if (!in_array($type, $allowedTypes, true)) {
        throw new InvalidArgumentException(
            'Invalid flash message type.'
        );
    }

    $_SESSION['flash_messages'][$type] = $message;
}

function get_flash($type)
{
    ensure_session_started();

    if (!isset($_SESSION['flash_messages'][$type])) {
        return null;
    }

    $message = $_SESSION['flash_messages'][$type];

    unset($_SESSION['flash_messages'][$type]);

    return $message;
}

?>