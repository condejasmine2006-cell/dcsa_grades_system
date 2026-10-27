<?php
session_start();

/* =========================================
   HELPER FUNCTION
========================================= */
function e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* =========================================
   DATABASE SETTINGS
========================================= */
$host = "localhost";
$dbname = "dcsa_grades";
$dbuser = "root";
$dbpass = "";

/* =========================================
   VARIABLES
========================================= */
$error = "";
$success = "";

/* =========================================
   ROLE-BASED REDIRECT
========================================= */
function redirectByRole(string $role): void
{
    $role = strtolower(trim($role));

    $dashboards = [
        "admin"     => "admin_dashboard.php",
        "registrar" => "registrar_dashboard.php",
        "teacher"   => "teacher_dashboard.php",
        "student"   => "student_dashboard.php"
    ];

    if (isset($dashboards[$role])) {
        header("Location: " . $dashboards[$role]);
        exit;
    }

    // An unsupported role must not access a dashboard.
    session_unset();
    session_destroy();

    header("Location: login.php?error=role");
    exit;
}

/* =========================================
   HANDLE EXISTING SESSION
========================================= */
if (isset($_SESSION["user_id"])) {
    $sessionRole = strtolower(
        trim((string)($_SESSION["role"] ?? ""))
    );

    redirectByRole($sessionRole);
}

/* =========================================
   DISPLAY MESSAGES
========================================= */
if (isset($_GET["registered"]) && $_GET["registered"] === "1") {
    $success = "Registration successful! You can now log in.";
}

if (isset($_GET["error"]) && $_GET["error"] === "role") {
    $error = "Invalid account role. Please contact the administrator.";
}

/* =========================================
   LOGIN PROCESS
========================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        mysqli_report(MYSQLI_REPORT_OFF);

        $conn = new mysqli(
            $host,
            $dbuser,
            $dbpass,
            $dbname
        );

        if ($conn->connect_error) {

            error_log("Database connection failed: " . $conn->connect_error);

            $error = "Unable to connect to the database. Please try again.";

        } else {

            $conn->set_charset("utf8mb4");

            /*
             * These columns match the users table structure
             * shown in your phpMyAdmin screenshot.
             */
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
                    student_type
                FROM users
                WHERE username = ?
                LIMIT 1
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {

                error_log("Login prepare error: " . $conn->error);

                $error = "A database error occurred. Please try again.";

            } else {

                $stmt->bind_param("s", $username);

                if (!$stmt->execute()) {

                    error_log("Login execution error: " . $stmt->error);

                    $error = "A database error occurred. Please try again.";

                } else {

                    $result = $stmt->get_result();

                    if ($result && $result->num_rows === 1) {

                        $user = $result->fetch_assoc();

                        /*
                         * Check the password.
                         * Passwords should be stored using password_hash().
                         */
                        if (password_verify($password, $user["password"])) {

                            $userRole = strtolower(
                                trim((string)($user["role"] ?? ""))
                            );

                            $allowedRoles = [
                                "admin",
                                "registrar",
                                "teacher",
                                "student"
                            ];

                            /*
                             * Validate the role returned by the database.
                             */
                            if (!in_array($userRole, $allowedRoles, true)) {

                                $error = "Invalid account role. Please contact the administrator.";

                            } else {

                                /*
                                 * Regenerate session ID after successful login.
                                 */
                                session_regenerate_id(true);

                                /*
                                 * Store account information in the session.
                                 */
                                $_SESSION["user_id"] = (int)$user["id"];
                                $_SESSION["student_number"] =
                                    $user["student_number"] ?? "";
                                $_SESSION["employee_number"] =
                                    $user["employee_number"] ?? "";
                                $_SESSION["full_name"] =
                                    $user["full_name"] ?? "";
                                $_SESSION["username"] =
                                    $user["username"] ?? "";
                                $_SESSION["email"] =
                                    $user["email"] ?? "";
                                $_SESSION["role"] = $userRole;
                                $_SESSION["student_type"] =
                                    $user["student_type"] ?? "";

                                $stmt->close();
                                $conn->close();

                                /*
                                 * Redirect to the correct dashboard.
                                 */
                                redirectByRole($userRole);
                            }

                        } else {

                            $error = "Incorrect username or password.";

                        }

                    } else {

                        $error = "Incorrect username or password.";

                    }
                }

                $stmt->close();
            }

            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DCSA Grading System | Login</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #173e78, #2861d5);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 35px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.18);
        }

        .school-logo {
            text-align: center;
            margin-bottom: 15px;
        }

        .school-logo img {
            width: 85px;
            height: 85px;
            object-fit: contain;
        }

        h1 {
            text-align: center;
            color: #1e3a8a;
            font-size: 30px;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 13px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 2px rgba(30, 58, 138, 0.12);
        }

        .show-password {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: #333;
        }

        .show-password input {
            width: auto;
        }

        .show-password label {
            margin: 0;
            font-weight: normal;
        }

        button {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 7px;
            background: #1e3a8a;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #162d6b;
        }

        .message {
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 18px;
            text-align: center;
            font-size: 14px;
            line-height: 1.5;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .register-link {
            text-align: center;
            margin-top: 22px;
            color: #555;
            line-height: 1.7;
        }

        .register-link a,
        .home-link {
            color: #1e3a8a;
            text-decoration: none;
            font-weight: bold;
        }

        .register-link a:hover,
        .home-link:hover {
            text-decoration: underline;
        }

        .home-link {
            display: block;
            text-align: center;
            margin-top: 18px;
            font-weight: normal;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 25px 20px;
            }

            h1 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="school-logo">
        <img src="imgs.jpg" alt="DCSA School Logo">
    </div>

    <h1>School Login</h1>
    <p class="subtitle">Sign in to your account</p>

    <?php if ($error !== ""): ?>
        <div class="message error" role="alert">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <div class="message success" role="status">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">

        <div class="form-group">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="Enter your username"
                value="<?= e($_POST["username"] ?? "") ?>"
                autocomplete="username"
                required
                autofocus
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
            >
        </div>

        <div class="show-password">
            <input type="checkbox" id="showPassword">
            <label for="showPassword">Show Password</label>
        </div>

        <button type="submit">Login</button>

    </form>

    <p class="register-link">
        Don't have an account?
        <a href="register.html">Create an Account</a>
    </p>

    <a href="index.php" class="home-link">← Back to Home</a>

</div>

<script>
    document.getElementById("showPassword").addEventListener("change", function () {
        document.getElementById("password").type =
            this.checked ? "text" : "password";
    });
</script>

</body>
</html>