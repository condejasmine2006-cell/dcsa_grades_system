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

$userRole = strtolower(trim($_SESSION["role"] ?? ""));

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

$id = intval($_GET["id"] ?? $_POST["id"] ?? 0);

if ($id <= 0) {
    header("Location: admin_dashboard.php");
    exit();
}


/* ==========================================
   UPDATE USER
========================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");

    $username = trim($_POST["username"] ?? "");

    $email = trim($_POST["email"] ?? "");

    $student_number = trim(
        $_POST["student_number"] ?? ""
    );

    $employee_number = trim(
        $_POST["employee_number"] ?? ""
    );

    $course = trim(
        $_POST["course"] ?? ""
    );

    $year_level = trim(
        $_POST["year_level"] ?? ""
    );

    $student_type = trim(
        $_POST["student_type"] ?? ""
    );

    $role = strtolower(
        trim($_POST["role"] ?? "")
    );


    /* ==========================================
       VALIDATION
    ========================================== */

    if (
        $full_name === "" ||
        $username === "" ||
        $email === "" ||
        $role === ""
    ) {

        die(
            "Please complete all required fields."
        );

    }


    /* ==========================================
       VALID ROLE
    ========================================== */

    $allowed_roles = [
        "student",
        "teacher",
        "admin"
    ];

    if (!in_array($role, $allowed_roles)) {

        die(
            "Invalid user role."
        );

    }


    /* ==========================================
       CHECK DUPLICATE USERNAME
    ========================================== */

    $check_sql = "
        SELECT id
        FROM users
        WHERE username = ?
        AND id != ?
        LIMIT 1
    ";

    $check_stmt = $conn->prepare(
        $check_sql
    );

    $check_stmt->bind_param(
        "si",
        $username,
        $id
    );

    $check_stmt->execute();

    $check_result =
        $check_stmt->get_result();

    if ($check_result->num_rows > 0) {

        die(
            "Username already exists."
        );

    }

    $check_stmt->close();


    /* ==========================================
       UPDATE DATABASE
    ========================================== */

    $update_sql = "
        UPDATE users
        SET
            student_number = ?,
            employee_number = ?,
            full_name = ?,
            username = ?,
            email = ?,
            role = ?,
            course = ?,
            year_level = ?,
            student_type = ?
        WHERE id = ?
    ";

    $update_stmt =
        $conn->prepare($update_sql);

    if (!$update_stmt) {

        die(
            "Database error: " .
            $conn->error
        );

    }


    $update_stmt->bind_param(
        "sssssssssi",
        $student_number,
        $employee_number,
        $full_name,
        $username,
        $email,
        $role,
        $course,
        $year_level,
        $student_type,
        $id
    );


    if ($update_stmt->execute()) {

        $update_stmt->close();

        header(
            "Location: admin_dashboard.php?updated=success"
        );

        exit();

    } else {

        die(
            "Update failed: " .
            $update_stmt->error
        );

    }

}


/* ==========================================
   GET USER INFORMATION
========================================== */

$sql = "
    SELECT
        id,
        student_number,
        employee_number,
        full_name,
        username,
        email,
        role,
        course,
        year_level,
        student_type
    FROM users
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    die(
        "User not found."
    );

}


$user = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit User</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }


        body {

            min-height: 100vh;

            background:
                linear-gradient(
                    rgba(18, 59, 109, 0.10),
                    rgba(18, 59, 109, 0.10)
                ),
                #f4f6f9;

            padding: 40px 20px;

        }


        .container {

            width: 100%;

            max-width: 700px;

            margin: auto;

            background: white;

            padding: 35px;

            border-radius: 15px;

            box-shadow:
                0 8px 30px
                rgba(0, 0, 0, 0.10);

        }


        h1 {

            color: #44275c;

            margin-bottom: 8px;

        }


        .subtitle {

            color: #666;

            margin-bottom: 30px;

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


        input,
        select {

            width: 100%;

            padding: 12px;

            border: 1px solid #ccc;

            border-radius: 8px;

            font-size: 15px;

            outline: none;

        }


        input:focus,
        select:focus {

            border-color: #44275c;

            box-shadow:
                0 0 0 3px
                rgba(68, 39, 92, 0.10);

        }


        .form-row {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 15px;

        }


        .buttons {

            display: flex;

            gap: 10px;

            margin-top: 30px;

        }


        .save-btn {

            flex: 1;

            background: #44275c;

            color: white;

            border: none;

            padding: 13px 20px;

            border-radius: 8px;

            cursor: pointer;

            font-size: 15px;

            font-weight: bold;

        }


        .save-btn:hover {

            background: #321d45;

        }


        .cancel-btn {

            flex: 1;

            background: #ddd;

            color: #222;

            padding: 13px 20px;

            border-radius: 8px;

            text-decoration: none;

            text-align: center;

            font-size: 15px;

        }


        .cancel-btn:hover {

            background: #ccc;

        }


        @media (max-width: 600px) {

            .container {

                padding: 25px 20px;

            }


            .form-row {

                grid-template-columns: 1fr;

                gap: 0;

            }


            .buttons {

                flex-direction: column;

            }

        }

    </style>

</head>


<body>


<div class="container">


    <h1>
        Edit User
    </h1>


    <p class="subtitle">

        Editing:

        <strong>
            <?php
            echo htmlspecialchars(
                $user["full_name"] ?? ""
            );
            ?>
        </strong>

    </p>


    <form method="POST">

        <!-- IMPORTANT:
             Keeps the user ID when saving
        -->

        <input
            type="hidden"
            name="id"
            value="<?php
                echo (int)$user["id"];
            ?>"
        >


        <!-- FULL NAME -->

        <div class="form-group">

            <label>
                Full Name
            </label>

            <input
                type="text"
                name="full_name"
                value="<?php
                    echo htmlspecialchars(
                        $user["full_name"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?>"
                required
            >

        </div>


        <!-- USERNAME -->

        <div class="form-group">

            <label>
                Username
            </label>

            <input
                type="text"
                name="username"
                value="<?php
                    echo htmlspecialchars(
                        $user["username"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?>"
                required
            >

        </div>


        <!-- EMAIL -->

        <div class="form-group">

            <label>
                Email
            </label>

            <input
                type="email"
                name="email"
                value="<?php
                    echo htmlspecialchars(
                        $user["email"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?>"
                required
            >

        </div>


        <!-- ROLE -->

        <div class="form-group">

            <label>
                Role
            </label>

            <select
                name="role"
                required
            >

                <option
                    value="student"
                    <?php
                    if (
                        strtolower(
                            $user["role"] ?? ""
                        ) === "student"
                    ) {
                        echo "selected";
                    }
                    ?>
                >
                    Student
                </option>


                <option
                    value="teacher"
                    <?php
                    if (
                        strtolower(
                            $user["role"] ?? ""
                        ) === "teacher"
                    ) {
                        echo "selected";
                    }
                    ?>
                >
                    Teacher
                </option>


                <option
                    value="admin"
                    <?php
                    if (
                        strtolower(
                            $user["role"] ?? ""
                        ) === "admin"
                    ) {
                        echo "selected";
                    }
                    ?>
                >
                    Admin
                </option>

            </select>

        </div>


        <!-- STUDENT NUMBER -->

        <div class="form-group">

            <label>
                Student Number
            </label>

            <input
                type="text"
                name="student_number"
                value="<?php
                    echo htmlspecialchars(
                        $user["student_number"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?>"
            >

        </div>


        <!-- EMPLOYEE NUMBER -->

        <div class="form-group">

            <label>
                Employee Number
            </label>

            <input
                type="text"
                name="employee_number"
                value="<?php
                    echo htmlspecialchars(
                        $user["employee_number"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?>"
            >

        </div>


        <!-- COURSE -->

        <div class="form-group">

            <label>
                Course
            </label>

            <input
                type="text"
                name="course"
                value="<?php
                    echo htmlspecialchars(
                        $user["course"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?>"
            >

        </div>


        <!-- YEAR LEVEL -->

        <div class="form-group">

            <label>
                Year Level
            </label>

            <input
                type="text"
                name="year_level"
                value="<?php
                    echo htmlspecialchars(
                        $user["year_level"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?>"
            >

        </div>


        <!-- STUDENT TYPE -->

        <div class="form-group">

            <label>
                Student Type
            </label>

            <select name="student_type">

                <option value="">
                    Not Applicable
                </option>


                <option
                    value="regular"
                    <?php
                    if (
                        strtolower(
                            $user["student_type"] ?? ""
                        ) === "regular"
                    ) {
                        echo "selected";
                    }
                    ?>
                >
                    Regular
                </option>


                <option
                    value="irregular"
                    <?php
                    if (
                        strtolower(
                            $user["student_type"] ?? ""
                        ) === "irregular"
                    ) {
                        echo "selected";
                    }
                    ?>
                >
                    Irregular
                </option>

            </select>

        </div>


        <!-- BUTTONS -->

        <div class="buttons">

            <button
                type="submit"
                class="save-btn"
            >
                Save Changes
            </button>


            <a
                href="admin_dashboard.php"
                class="cancel-btn"
            >
                Cancel
            </a>

        </div>


    </form>


</div>


</body>

</html>

<?php

$conn->close();

?>