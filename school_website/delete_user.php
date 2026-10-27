<?php

/* ==========================================
   START SESSION
========================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* ==========================================
   ADMIN ONLY
========================================== */

$userRole = strtolower(
    trim($_SESSION["role"] ?? "")
);

if (
    !isset($_SESSION["user_id"]) ||
    $userRole !== "admin"
) {
    header("Location: login.html");
    exit();
}


/* ==========================================
   DATABASE CONNECTION
========================================== */

$host = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "dcsa_grades";

$conn = new mysqli(
    $host,
    $dbuser,
    $dbpass,
    $dbname
);

if ($conn->connect_error) {

    die(
        "Database connection failed: " .
        $conn->connect_error
    );

}

$conn->set_charset("utf8mb4");


/* ==========================================
   GET USER ID
========================================== */

$id = intval(
    $_GET["id"] ?? 0
);


if ($id <= 0) {

    header(
        "Location: admin_dashboard.php"
    );

    exit();

}


/* ==========================================
   MAKE SURE USER EXISTS
========================================== */

$check_sql = "
    SELECT id, role, full_name
    FROM users
    WHERE id = ?
    LIMIT 1
";

$check_stmt = $conn->prepare(
    $check_sql
);

$check_stmt->bind_param(
    "i",
    $id
);

$check_stmt->execute();

$result =
    $check_stmt->get_result();


if ($result->num_rows !== 1) {

    $check_stmt->close();

    die(
        "User not found."
    );

}


$user = $result->fetch_assoc();

$check_stmt->close();


/* ==========================================
   OPTIONAL:
   PREVENT ADMIN FROM DELETING THEMSELVES
========================================== */

if (
    $id == $_SESSION["user_id"]
) {

    die(
        "You cannot delete your own admin account."
    );

}


/* ==========================================
   DELETE USER
========================================== */

$delete_sql = "
    DELETE FROM users
    WHERE id = ?
    LIMIT 1
";

$delete_stmt = $conn->prepare(
    $delete_sql
);

$delete_stmt->bind_param(
    "i",
    $id
);


if ($delete_stmt->execute()) {

    $delete_stmt->close();

    header(
        "Location: admin_dashboard.php?deleted=success"
    );

    exit();

} else {

    die(
        "Delete failed: " .
        $delete_stmt->error
    );

}


$conn->close();

?>