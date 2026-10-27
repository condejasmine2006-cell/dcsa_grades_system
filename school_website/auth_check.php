
<?php
// Shared authentication check for DCSA

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION["user_id"] ?? null;
$userRole = strtolower(trim($_SESSION["role"] ?? ""));

// Roles allowed to access each page should be specified
// by the individual page.
function requireRoles(array $allowedRoles): void
{
    $role = strtolower(trim($_SESSION["role"] ?? ""));

    if (
        empty($_SESSION["user_id"]) ||
        !in_array($role, $allowedRoles, true)
    ) {
        header("Location: login.html?error=unauthorized");
        exit();
    }
}
