
<?php
session_start();

/* =========================================
   ADMIN ACCESS CHECK
========================================= */

if (
    !isset($_SESSION["user_id"]) ||
    strtolower(trim($_SESSION["role"] ?? "")) !== "admin"
) {
    header("Location: login.html");
    exit();
}

/* =========================================
   DATABASE CONNECTION
========================================= */

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "dcsa_grades"
);

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

/* =========================================
   VARIABLES
========================================= */

$message = "";
$messageType = "";

$fullName = $_SESSION["full_name"] ?? "Administrator";

$formName = "";
$formUsername = "";
$formEmail = "";

/* =========================================
   CREATE REGISTRAR ACCOUNT
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $formName = trim($_POST["full_name"] ?? "");
    $formUsername = trim($_POST["username"] ?? "");
    $formEmail = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (
        $formName === "" ||
        $formUsername === "" ||
        $formEmail === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {
        $message = "Please complete all fields.";
        $messageType = "error";

    } elseif (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif (strlen($formUsername) > 50) {

        $message = "Username must not exceed 50 characters.";
        $messageType = "error";

    } elseif (strlen($formName) > 100) {

        $message = "Full name must not exceed 100 characters.";
        $messageType = "error";

    } elseif (strlen($formEmail) > 100) {

        $message = "Email must not exceed 100 characters.";
        $messageType = "error";

    } elseif (strlen($password) < 8) {

        $message = "Password must contain at least 8 characters.";
        $messageType = "error";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } else {

        /* Check whether username or email already exists */

        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE username = ? OR email = ?
             LIMIT 1"
        );

        if (!$check) {

            $message = "Database query failed: " . $conn->error;
            $messageType = "error";

        } else {

            $check->bind_param(
                "ss",
                $formUsername,
                $formEmail
            );

            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {

                $message = "Username or email already exists.";
                $messageType = "error";

            } else {

                /* Hash the password */

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $role = "registrar";

                /* Insert account into users table */

                $insert = $conn->prepare(
                    "INSERT INTO users
                        (full_name, username, email, password, role)
                     VALUES (?, ?, ?, ?, ?)"
                );

                if (!$insert) {

                    $message = "Could not prepare account creation: "
                             . $conn->error;

                    $messageType = "error";

                } else {

                    $insert->bind_param(
                        "sssss",
                        $formName,
                        $formUsername,
                        $formEmail,
                        $hashedPassword,
                        $role
                    );

                    if ($insert->execute()) {

                        $message = "Registrar account created successfully!";
                        $messageType = "success";

                        /* Clear form after success */

                        $formName = "";
                        $formUsername = "";
                        $formEmail = "";

                    } else {

                        $message = "Account creation failed: "
                                 . $insert->error;

                        $messageType = "error";
                    }

                    $insert->close();
                }
            }

            $check->close();
        }
    }
}

/* =========================================
   ESCAPE OUTPUT
========================================= */

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Create Registrar Account | DCSA</title>

<style>
* {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    margin: 0;
    background: #f4f7fb;
    color: #333;
}

.header {
    background: #44275c;
    color: white;
    padding: 22px 30px;
}

.header h1 {
    margin: 0 0 5px;
    font-size: 26px;
}

.header p {
    margin: 0;
}

.container {
    max-width: 650px;
    margin: 40px auto;
    padding: 0 20px;
}

.form-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    border-left: 5px solid #44275c;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.form-card h2 {
    color: #44275c;
    margin-top: 0;
    margin-bottom: 10px;
}

.description {
    color: #666;
    line-height: 1.5;
    margin-bottom: 22px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
    color: #44275c;
}

input {
    display: block;
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    margin-bottom: 18px;
    font-size: 15px;
}

input:focus {
    outline: none;
    border-color: #70418a;
    box-shadow: 0 0 0 2px rgba(112,65,138,0.12);
}

button,
.back-button {
    display: inline-block;
    padding: 12px 18px;
    border: none;
    border-radius: 6px;
    background: #44275c;
    color: white;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
    cursor: pointer;
}

button:hover {
    background: #603b79;
}

.back-button {
    background: #687080;
}

.back-button:hover {
    background: #4f5663;
}

.message {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.success {
    color: #17663b;
    background: #e8f8f0;
    border: 1px solid #b9e8ce;
}

.error {
    color: #922;
    background: #fff0f0;
    border: 1px solid #efc2c2;
}

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

@media (max-width: 500px) {
    .form-card {
        padding: 20px;
    }

    .actions {
        flex-direction: column;
    }

    .actions button,
    .actions a {
        text-align: center;
    }
}
</style>

</head>

<body>

<div class="header">

    <h1>DCSA Grading System</h1>

    <p>Administrator: <?= e($fullName) ?></p>

</div>

<div class="container">

    <div class="form-card">

        <h2>Create Registrar Account</h2>

        <p class="description">
            Create an official Registrar account for managing
            student enrollment and academic records.
        </p>

        <?php if ($message !== ""): ?>

            <div class="message <?= e($messageType) ?>">
                <?= e($message) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="create_registrar.php">

            <label for="full_name">Full Name</label>

            <input
                type="text"
                id="full_name"
                name="full_name"
                maxlength="100"
                value="<?= e($formName) ?>"
                required
            >

            <label for="username">Username</label>

            <input
                type="text"
                id="username"
                name="username"
                maxlength="50"
                value="<?= e($formUsername) ?>"
                required
            >

            <label for="email">Email Address</label>

            <input
                type="email"
                id="email"
                name="email"
                maxlength="100"
                value="<?= e($formEmail) ?>"
                required
            >

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <label for="confirm_password">Confirm Password</label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <div class="actions">

                <button type="submit">
                    Create Registrar Account
                </button>

                <a
                    href="admin_dashboard.php"
                    class="back-button"
                >
                    Back to Dashboard
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>
