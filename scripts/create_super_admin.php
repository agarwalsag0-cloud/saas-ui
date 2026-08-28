<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run this script from the command line.\n");
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

function prompt(string $label): string
{
    echo $label . ': ';
    return trim((string) fgets(STDIN));
}

$name = prompt('Super Admin name');
$email = strtolower(prompt('Super Admin email'));
$password = prompt('Password (min 8 characters)');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    exit("Invalid input. Name, valid email and 8+ character password are required.\n");
}

$pdo = Database::pdo();
$check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->execute([$email]);
if ($check->fetch()) {
    exit("A user with this email already exists.\n");
}

$stmt = $pdo->prepare('INSERT INTO users (business_id, name, email, phone, password_hash, role, status, created_at, updated_at) VALUES (NULL, ?, ?, NULL, ?, "super_admin", "active", NOW(), NOW())');
$stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);

echo "Super Admin created successfully. User ID: " . $pdo->lastInsertId() . PHP_EOL;
