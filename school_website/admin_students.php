
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = strtolower(trim($_SESSION["role"] ?? $_SESSION["user_role"] ?? ""));

if (
    empty($_SESSION["user_id"]) ||
    !in_array($role, ["admin", "administrator", "registrar"], true)
) {
    header("Location: login.html?error=unauthorized");
    exit();
}

require_once __DIR__ . "/database.php";


/* =========================================================
   ADMIN ACCESS CHECK
========================================================= */

$role = "";

if (isset($_SESSION["role"])) {
    $role = strtolower(trim($_SESSION["role"]));
} elseif (isset($_SESSION["user_role"])) {
    $role = strtolower(trim($_SESSION["user_role"]));
}

$role = strtolower(trim(
    $_SESSION["role"] ?? $_SESSION["user_role"] ?? ""
));

if (
    empty($_SESSION["user_id"]) ||
    !in_array($role, ["admin", "administrator", "registrar"], true)
) {
    header("Location: login.html?error=unauthorized");
    exit();
}


/* =========================================================
   SYNCHRONIZE USERS → STUDENTS
========================================================= */

try {

    /*
     * Get all student accounts from users.
     */

    $usersQuery = "
        SELECT
            student_number,
            full_name,
            first_name,
            last_name,
            email,
            username,
            password,
            student_type,
            enrollment_type
        FROM users
        WHERE LOWER(role) = 'student'
          AND student_number IS NOT NULL
          AND TRIM(student_number) <> ''
    ";

    $usersResult = $conn->query($usersQuery);


    /*
     * Process every student account.
     */

    while ($user = $usersResult->fetch_assoc()) {

        /* ---------------------------------------------
           STUDENT NUMBER
        --------------------------------------------- */

        $studentNumber = "";

        if (isset($user["student_number"])) {
            $studentNumber = trim(
                (string)$user["student_number"]
            );
        }

        if ($studentNumber === "") {
            continue;
        }


        /* ---------------------------------------------
           FULL NAME
        --------------------------------------------- */

        $fullName = "";

        if (isset($user["full_name"])) {
            $fullName = trim(
                (string)$user["full_name"]
            );
        }


        /* ---------------------------------------------
           FIRST NAME
        --------------------------------------------- */

        $firstName = "";

        if (
            isset($user["first_name"]) &&
            trim((string)$user["first_name"]) !== ""
        ) {

            $firstName = trim(
                (string)$user["first_name"]
            );

        } elseif ($fullName !== "") {

            $parts = preg_split(
                "/\s+/",
                $fullName
            );

            if (isset($parts[0])) {
                $firstName = trim($parts[0]);
            }
        }


        /* ---------------------------------------------
           LAST NAME
        --------------------------------------------- */

        $lastName = "";

        if (
            isset($user["last_name"]) &&
            trim((string)$user["last_name"]) !== ""
        ) {

            $lastName = trim(
                (string)$user["last_name"]
            );

        } elseif ($fullName !== "") {

            $parts = preg_split(
                "/\s+/",
                $fullName
            );

            if (count($parts) > 1) {

                array_shift($parts);

                $lastName = trim(
                    implode(" ", $parts)
                );
            }
        }


        /* ---------------------------------------------
           PREVENT NULL VALUES
        --------------------------------------------- */

        if ($firstName === "") {
            $firstName = "Unknown";
        }

        if ($lastName === "") {
            $lastName = "Unknown";
        }


        /* ---------------------------------------------
           EMAIL
        --------------------------------------------- */

        $email = "";

        if (isset($user["email"])) {
            $email = trim(
                (string)$user["email"]
            );
        }


        /* ---------------------------------------------
           USERNAME
        --------------------------------------------- */

        $username = "";

        if (isset($user["username"])) {
            $username = trim(
                (string)$user["username"]
            );
        }

        if ($username === "") {
            continue;
        }


        /* ---------------------------------------------
           PASSWORD
        --------------------------------------------- */

        $password = "";

        if (isset($user["password"])) {
            $password = (string)$user["password"];
        }


        /* ---------------------------------------------
   STUDENT TYPE
--------------------------------------------- */

$studentType = "regular";

$userStudentType = "";

if (isset($user["student_type"])) {
    $userStudentType = strtolower(
        trim((string)$user["student_type"])
    );
}


/* ---------------------------------------------
   ENROLLMENT TYPE
--------------------------------------------- */

$enrollmentType = "REGULAR";

if (isset($user["enrollment_type"])) {
    $enrollmentType = strtoupper(
        trim((string)$user["enrollment_type"])
    );
}


/*
 * If enrollment_type is IRREGULAR,
 * automatically make student_type IRREGULAR too.
 */

if ($enrollmentType === "IRREGULAR") {

    $studentType = "irregular";

} elseif (
    $userStudentType === "irregular"
) {

    $studentType = "irregular";

} else {

    $studentType = "regular";
}


/* Make sure enrollment has a valid value */

if ($enrollmentType === "") {
    $enrollmentType = "REGULAR";
}


        /* ---------------------------------------------
           CHECK IF STUDENT ALREADY EXISTS
        --------------------------------------------- */

        $checkSql = "
            SELECT id
            FROM students
            WHERE username = ?
               OR student_id = ?
            LIMIT 1
        ";

        $checkStmt = $conn->prepare($checkSql);

        $checkStmt->bind_param(
            "ss",
            $username,
            $studentNumber
        );

        $checkStmt->execute();

        $checkResult = $checkStmt->get_result();

        $existingStudent = $checkResult->fetch_assoc();

        $checkStmt->close();


        /* ---------------------------------------------
           UPDATE EXISTING STUDENT
        --------------------------------------------- */

        if ($existingStudent) {

            $updateSql = "
                UPDATE students
                SET
                    student_id = ?,
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    password = ?,
                    status = ?,
                    student_status = ?
                WHERE id = ?
            ";

            $updateStmt = $conn->prepare($updateSql);

            $studentDbId = (int)$existingStudent["id"];

            $updateStmt->bind_param(
                "sssssssi",
                $studentNumber,
                $firstName,
                $lastName,
                $email,
                $password,
                $studentType,
                $enrollmentType,
                $studentDbId
            );

            $updateStmt->execute();

            $updateStmt->close();

        }


        /* ---------------------------------------------
           INSERT NEW STUDENT
        --------------------------------------------- */

        else {

            $insertSql = "
                INSERT INTO students
                (
                    student_id,
                    first_name,
                    last_name,
                    email,
                    username,
                    password,
                    status,
                    student_status
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
                    ?
                )
            ";

            $insertStmt = $conn->prepare($insertSql);

            $insertStmt->bind_param(
                "ssssssss",
                $studentNumber,
                $firstName,
                $lastName,
                $email,
                $username,
                $password,
                $studentType,
                $enrollmentType
            );

            $insertStmt->execute();

            $insertStmt->close();
        }
    }

} catch (mysqli_sql_exception $e) {

    error_log(
        "Student synchronization failed: " .
        $e->getMessage()
    );
}


/* =========================================================
   LOAD STUDENTS
========================================================= */

$sql = "
    SELECT
        id,
        student_id,
        first_name,
        last_name,
        email,
        username,
        status,
        student_status
    FROM students
    ORDER BY id DESC
";

try {

    $result = $conn->query($sql);

} catch (mysqli_sql_exception $e) {

    error_log(
        "Student loading failed: " .
        $e->getMessage()
    );

    die(
        "Unable to load student records."
    );
}


/* =========================================================
   STUDENT COUNT
========================================================= */

$studentCount = $result->num_rows;


/* =========================================================
   HTML ESCAPE FUNCTION
========================================================= */

function outputSafe($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
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
        Manage Students | DATAMEX COLLEGE OF SAINT ADELINE
    </title>


    <style>

        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }


        body {
            margin: 0;
            padding: 30px;
            background: #f4f7fb;
            color: #222;
        }


        .container {
            width: 100%;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow:
                0 3px 12px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }


        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #205c4a;
            text-decoration: none;
            font-size: 15px;
        }


        .back:hover {
            text-decoration: underline;
        }


        h1 {
            color: #48265f;
            margin: 0 0 8px 0;
        }


        .subtitle {
            color: #666;
            margin: 0 0 5px 0;
        }


        .count {
            color: #777;
            font-size: 14px;
            margin: 0 0 20px 0;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }


        th,
        td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }


        th {
            background: #48265f;
            color: white;
            font-size: 14px;
        }


        td {
            font-size: 14px;
        }


        tbody tr:hover {
            background: #f7f7fb;
        }


        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }


        .regular {
            background: #e8f5e9;
            color: #2e7d32;
        }


        .irregular {
            background: #fff3e0;
            color: #e65100;
        }


        .enrollment {
            font-weight: bold;
            color: #48265f;
        }


        .back-subject {
            display: inline-block;
            padding: 7px 12px;
            background: #48265f;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
        }


        .back-subject:hover {
            background: #603b79;
        }


        .no-action {
            color: #999;
            font-size: 12px;
        }


        .empty {
            text-align: center;
            padding: 35px;
            color: #777;
        }


        @media (max-width: 768px) {

            body {
                padding: 15px;
            }

            .container {
                padding: 15px;
            }

            h1 {
                font-size: 25px;
            }
        }

    </style>

</head>


<body>

<div class="container">


    <!-- =====================================================
         BACK TO DASHBOARD
    ====================================================== -->

    <a class="back"
   href="<?= $role === 'registrar'
       ? 'registrar_dashboard.php'
       : 'admin_dashboard.php' ?>">
    &larr; Back to Dashboard
</a>

    <!-- =====================================================
         TITLE
    ====================================================== -->

    <h1>
        Manage Students
    </h1>


    <p class="subtitle">
        Registered student records from the database.
    </p>


    <p class="count">

        Total Students:
        <strong>
            <?= outputSafe($studentCount) ?>
        </strong>

    </p>


    <!-- =====================================================
         STUDENT TABLE
    ====================================================== -->

    <table>

        <thead>

            <tr>

                <th>
                    Student Number
                </th>

                <th>
                    First Name
                </th>

                <th>
                    Last Name
                </th>

                <th>
                    Email
                </th>

                <th>
                    Username
                </th>

                <th>
                    Student Type
                </th>

                <th>
                    Enrollment
                </th>

                <th>
                    Action
                </th>

            </tr>

        </thead>


        <tbody>

        <?php if ($studentCount > 0): ?>

            <?php while ($student = $result->fetch_assoc()): ?>

                <?php

                $studentType = "regular";

                if (isset($student["status"])) {

                    $studentType = strtolower(
                        trim(
                            (string)$student["status"]
                        )
                    );
                }

                if (
                    $studentType !== "irregular"
                ) {

                    $studentType = "regular";
                }

                $enrollment = "REGULAR";

                if (
                    isset($student["student_status"]) &&
                    trim(
                        (string)$student["student_status"]
                    ) !== ""
                ) {

                    $enrollment = strtoupper(
                        trim(
                            (string)$student["student_status"]
                        )
                    );
                }

                ?>

                <tr>

                    <!-- STUDENT NUMBER -->

                    <td>
                        <?= outputSafe(
                            $student["student_id"]
                        ) ?>
                    </td>


                    <!-- FIRST NAME -->

                    <td>
                        <?= outputSafe(
                            $student["first_name"]
                        ) ?>
                    </td>


                    <!-- LAST NAME -->

                    <td>
                        <?= outputSafe(
                            $student["last_name"]
                        ) ?>
                    </td>


                    <!-- EMAIL -->

                    <td>
                        <?= outputSafe(
                            $student["email"]
                        ) ?>
                    </td>


                    <!-- USERNAME -->

                    <td>
                        <?= outputSafe(
                            $student["username"]
                        ) ?>
                    </td>


                    <!-- STUDENT TYPE -->

                    <td>

                        <span
                            class="status <?= outputSafe(
                                $studentType
                            ) ?>"
                        >

                            <?= outputSafe(
                                ucfirst(
                                    $studentType
                                )
                            ) ?>

                        </span>

                    </td>


                    <!-- ENROLLMENT -->

                    <td>

                        <span class="enrollment">

                            <?= outputSafe(
                                $enrollment
                            ) ?>

                        </span>

                    </td>


                    <!-- ACTION -->

                    <td>

                        <?php if (
                            $studentType === "irregular"
                        ): ?>

                            <a
                                class="back-subject"
                                href="irregular_subjects.php?student_id=<?= urlencode(
                                    $student["id"]
                                ) ?>"
                            >
                                Back Subjects
                            </a>

                        <?php else: ?>

                            <span class="no-action">
                                Regular Student
                            </span>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>

                <td
                    colspan="8"
                    class="empty"
                >
                    No students found.
                </td>

            </tr>

        <?php endif; ?>

        </tbody>

    </table>


</div>

</body>

</html>