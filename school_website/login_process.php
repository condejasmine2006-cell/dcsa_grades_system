<?php

session_start();

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


/* ==========================================
   ONLY ALLOW POST REQUEST
========================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: login.html");
    exit();

}


/* ==========================================
   GET LOGIN DATA
========================================== */

$username = trim(
    $_POST["username"] ?? ""
);

$password =
    $_POST["password"] ?? "";


/* ==========================================
   VALIDATE INPUT
========================================== */

if (
    $username === "" ||
    $password === ""
) {

    header(
        "Location: login.html?error=empty"
    );

    exit();

}


/* ==========================================
   FIND USER
========================================== */

$sql = "
    SELECT
        id,
        student_number,
        employee_number,
        full_name,
        username,
        email,
        password,
        role,
        course,
        year_level,
        student_type
    FROM users
    WHERE username = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database error: " .
        $conn->error
    );

}

$stmt->bind_param(
    "s",
    $username
);

$stmt->execute();

$result =
    $stmt->get_result();


/* ==========================================
   CHECK USER
========================================== */

if ($result->num_rows !== 1) {

    header(
        "Location: login.html?error=invalid"
    );

    exit();

}

$user =
    $result->fetch_assoc();


/* ==========================================
   VERIFY PASSWORD
========================================== */

$storedPassword =
    $user["password"];

$passwordIsCorrect = false;


/* Check hashed password */

if (
    password_verify(
        $password,
        $storedPassword
    )
) {

    $passwordIsCorrect = true;

}


/* Check old plain-text password */

elseif (
    hash_equals(
        $storedPassword,
        $password
    )
) {

    $passwordIsCorrect = true;


    /* Convert old password to hash */

    $newHash =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );

    $updatePassword =
        $conn->prepare(
            "
            UPDATE users
            SET password = ?
            WHERE id = ?
            "
        );

    if ($updatePassword) {

        $updatePassword->bind_param(
            "si",
            $newHash,
            $user["id"]
        );

        $updatePassword->execute();

    }

}


/* Incorrect password */

if (!$passwordIsCorrect) {

    header(
        "Location: login.html?error=invalid"
    );

    exit();

}


/* ==========================================
   CREATE SESSION
========================================== */

session_regenerate_id(true);

$_SESSION["user_id"] =
    $user["id"];

$_SESSION["full_name"] =
    $user["full_name"];

$_SESSION["username"] =
    $user["username"];

$_SESSION["role"] =
    strtolower(
        trim(
            $user["role"]
        )
    );

$_SESSION["student_number"] =
    $user["student_number"];

$_SESSION["employee_number"] =
    $user["employee_number"];

$_SESSION["course"] =
    $user["course"];

$_SESSION["year_level"] =
    $user["year_level"];

$_SESSION["student_type"] =
    $user["student_type"];


/* ==========================================
   REDIRECT BASED ON ROLE
========================================== */

if (
    $_SESSION["role"]
    === "student"
) {

    header(
        "Location: student_dashboard.php"
    );

    exit();

}


if (
    $_SESSION["role"]
    === "teacher"
) {

    header(
        "Location: teacher_dashboard.php"
    );

    exit();

}


if (
    $_SESSION["role"]
    === "admin"
) {

    header(
        "Location: admin_dashboard.php"
    );

    exit();

}

if ($_SESSION["role"] === "registrar") {
    header("Location: registrar_dashboard.php");
    exit();
}


/* ==========================================
   INVALID ROLE
========================================== */

session_destroy();

header(
    "Location: login.html?error=role"
);

exit();

?>