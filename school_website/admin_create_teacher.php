<?php

session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once "database.php";

$conn->set_charset("utf8mb4");


/* =========================================================
   ADMIN ONLY
========================================================= */

$role = "";

if (isset($_SESSION["role"])) {
    $role = strtolower(trim($_SESSION["role"]));
} elseif (isset($_SESSION["user_role"])) {
    $role = strtolower(trim($_SESSION["user_role"]));
}

if (
    !isset($_SESSION["user_id"]) ||
    $role !== "admin"
) {
    header("Location: login.html");
    exit();
}


/* =========================================================
   MESSAGE VARIABLES
========================================================= */

$success = "";
$error = "";


/* =========================================================
   CREATE TEACHER
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* ---------------------------------------------
       GET FORM DATA
    --------------------------------------------- */

    $employee_number = trim(
        $_POST["employee_number"] ?? ""
    );

    $first_name = trim(
        $_POST["first_name"] ?? ""
    );

    $middle_name = trim(
        $_POST["middle_name"] ?? ""
    );

    $last_name = trim(
        $_POST["last_name"] ?? ""
    );

    $extension_name = trim(
        $_POST["extension_name"] ?? ""
    );

    $email = trim(
        $_POST["email"] ?? ""
    );

    $username = trim(
        $_POST["username"] ?? ""
    );

    $password = $_POST["password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";


    /* ---------------------------------------------
       FORCE ROLE
    --------------------------------------------- */

    /*
     * The Admin does NOT choose the role.
     *
     * This page can ONLY create teachers.
     */

    $teacher_role = "teacher";


    /* ---------------------------------------------
       VALIDATE REQUIRED FIELDS
    --------------------------------------------- */

    if (
        $employee_number === "" ||
        $first_name === "" ||
        $last_name === "" ||
        $email === "" ||
        $username === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $error =
            "Please complete all required fields.";

    }


    /* ---------------------------------------------
       VALIDATE EMAIL
    --------------------------------------------- */

    elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    }


    /* ---------------------------------------------
       VALIDATE USERNAME
    --------------------------------------------- */

    elseif (
        !preg_match(
            '/^[A-Za-z0-9._-]{3,50}$/',
            $username
        )
    ) {

        $error =
            "Username must be 3-50 characters and may contain only letters, numbers, dots, underscores, and hyphens.";

    }


    /* ---------------------------------------------
       VALIDATE PASSWORD
    --------------------------------------------- */

    elseif (strlen($password) < 8) {

        $error =
            "Password must contain at least 8 characters.";

    }


    elseif ($password !== $confirm_password) {

        $error =
            "Passwords do not match.";

    }


    /* ---------------------------------------------
       CREATE FULL NAME
    --------------------------------------------- */

    else {

        $name_parts = [];

        if ($first_name !== "") {
            $name_parts[] = $first_name;
        }

        if ($middle_name !== "") {
            $name_parts[] = $middle_name;
        }

        if ($last_name !== "") {
            $name_parts[] = $last_name;
        }

        if ($extension_name !== "") {
            $name_parts[] = $extension_name;
        }

        $full_name = trim(
            implode(" ", $name_parts)
        );


        /* -----------------------------------------
           CHECK DUPLICATES
        ----------------------------------------- */

        try {

            /* Employee Number */

            $stmt = $conn->prepare(
                "SELECT id
                 FROM users
                 WHERE employee_number = ?
                 LIMIT 1"
            );

            $stmt->bind_param(
                "s",
                $employee_number
            );

            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {

                $error =
                    "Employee number already exists.";

            }

            $stmt->close();


            /* Username */

            if ($error === "") {

                $stmt = $conn->prepare(
                    "SELECT id
                     FROM users
                     WHERE username = ?
                     LIMIT 1"
                );

                $stmt->bind_param(
                    "s",
                    $username
                );

                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {

                    $error =
                        "Username already exists.";

                }

                $stmt->close();
            }


            /* Email */

            if ($error === "") {

                $stmt = $conn->prepare(
                    "SELECT id
                     FROM users
                     WHERE email = ?
                     LIMIT 1"
                );

                $stmt->bind_param(
                    "s",
                    $email
                );

                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {

                    $error =
                        "Email is already registered.";

                }

                $stmt->close();
            }


            /* -------------------------------------
               INSERT TEACHER
            ------------------------------------- */

            if ($error === "") {

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                $conn->begin_transaction();


                /*
                 * Only teacher accounts are created
                 * on this page.
                 */

                $sql = "
                    INSERT INTO users
                    (
                        employee_number,
                        full_name,
                        username,
                        email,
                        password,
                        role,
                        first_name,
                        middle_name,
                        last_name,
                        extension_name
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ";


                $stmt = $conn->prepare($sql);


                $stmt->bind_param(
                    "ssssssssss",
                    $employee_number,
                    $full_name,
                    $username,
                    $email,
                    $hashed_password,
                    $teacher_role,
                    $first_name,
                    $middle_name,
                    $last_name,
                    $extension_name
                );


                $stmt->execute();

                $stmt->close();


                $conn->commit();


                $success =
                    "Teacher account created successfully.";


                /*
                 * Clear form values after success.
                 */

                $employee_number = "";
                $first_name = "";
                $middle_name = "";
                $last_name = "";
                $extension_name = "";
                $email = "";
                $username = "";
            }

        } catch (Throwable $e) {

            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
            }

            error_log(
                "Teacher creation error: " .
                $e->getMessage()
            );

            $error =
                "Unable to create the teacher account. Please check your database structure.";
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Create Teacher Account | DATAMEX COLLEGE OF SAINT ADELINE
    </title>


    <style>

        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }


        body {
            margin: 0;
            min-height: 100vh;
            background: #f4f7fb;
            padding: 30px;
        }


        .container {
            width: 100%;
            max-width: 850px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 14px;
            box-shadow:
                0 4px 15px rgba(0, 0, 0, 0.08);
        }


        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #48265f;
            text-decoration: none;
            font-weight: bold;
        }


        .back:hover {
            text-decoration: underline;
        }


        h1 {
            margin: 0;
            color: #48265f;
        }


        .subtitle {
            color: #666;
            margin-bottom: 25px;
        }


        .message {
            padding: 13px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
        }


        .success {
            background: #e8f5e9;
            color: #2e7d32;
        }


        .error {
            background: #ffebee;
            color: #c62828;
        }


        .section-title {
            margin-top: 25px;
            margin-bottom: 15px;
            color: #48265f;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }


        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }


        .field {
            display: flex;
            flex-direction: column;
        }


        .field.full {
            grid-column: 1 / -1;
        }


        label {
            font-weight: bold;
            margin-bottom: 7px;
            color: #333;
        }


        .required {
            color: #c62828;
        }


        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
        }


        input:focus {
            border-color: #48265f;
        }


        .role-box {
            padding: 12px;
            background: #f3edf7;
            border: 1px solid #d7c8df;
            border-radius: 7px;
            color: #48265f;
            font-weight: bold;
        }


        .button-area {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }


        button {
            border: none;
            padding: 13px 22px;
            border-radius: 7px;
            background: #48265f;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }


        button:hover {
            background: #603b79;
        }


        .cancel {
            display: inline-block;
            padding: 13px 22px;
            border-radius: 7px;
            background: #ddd;
            color: #333;
            text-decoration: none;
            font-weight: bold;
        }


        .cancel:hover {
            background: #ccc;
        }


        @media (max-width: 700px) {

            body {
                padding: 15px;
            }

            .container {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }

        }

    </style>

</head>


<body>

<div class="container">


    <a
        class="back"
        href="admin_dashboard.php"
    >
        &larr; Back to Dashboard
    </a>


    <h1>
        Create Teacher Account
    </h1>


    <p class="subtitle">
        Only administrators can create teacher accounts.
    </p>


    <?php if ($success !== ""): ?>

        <div class="message success">
            <?= htmlspecialchars(
                $success,
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="message error">
            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        action=""
        autocomplete="off"
    >


        <!-- =============================================
             ACCOUNT INFORMATION
        ============================================== -->

        <h2 class="section-title">
            Account Information
        </h2>


        <div class="form-grid">


            <div class="field">

                <label>
                    Employee Number
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="employee_number"
                    value="<?= htmlspecialchars(
                        $employee_number ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Account Type
                </label>

                <div class="role-box">
                    Teacher
                </div>

            </div>


            <div class="field">

                <label>
                    Username
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="username"
                    value="<?= htmlspecialchars(
                        $username ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Email
                    <span class="required">*</span>
                </label>

                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars(
                        $email ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Password
                    <span class="required">*</span>
                </label>

                <input
                    type="password"
                    name="password"
                    minlength="8"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Confirm Password
                    <span class="required">*</span>
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    minlength="8"
                    required
                >

            </div>


        </div>


        <!-- =============================================
             PERSONAL INFORMATION
        ============================================== -->

        <h2 class="section-title">
            Personal Information
        </h2>


        <div class="form-grid">


            <div class="field">

                <label>
                    First Name
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="first_name"
                    value="<?= htmlspecialchars(
                        $first_name ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Middle Name
                </label>

                <input
                    type="text"
                    name="middle_name"
                    value="<?= htmlspecialchars(
                        $middle_name ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

            </div>


            <div class="field">

                <label>
                    Last Name
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="last_name"
                    value="<?= htmlspecialchars(
                        $last_name ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Extension Name
                </label>

                <input
                    type="text"
                    name="extension_name"
                    placeholder="Jr., Sr., III"
                    value="<?= htmlspecialchars(
                        $extension_name ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

            </div>


        </div>


        <!-- =============================================
             BUTTONS
        ============================================== -->

        <div class="button-area">

            <button type="submit">
                Create Teacher Account
            </button>

            <a
                href="admin_dashboard.php"
                class="cancel"
            >
                Cancel
            </a>

        </div>


    </form>


</div>

</body>

</html>