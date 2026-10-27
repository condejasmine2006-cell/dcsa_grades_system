<?php

session_start();

/* ==========================================
   PROTECT STUDENT PAGE
========================================== */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: login.html");
    exit();
}


/* ==========================================
   DATABASE CONNECTION
========================================== */

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "dcsa_grades"
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


/* ==========================================
   GET LOGGED-IN STUDENT
========================================== */

$username = $_SESSION["username"] ?? "";

$stmt = $conn->prepare("
    SELECT id, student_id, first_name, last_name
    FROM students
    WHERE username = ?
");

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die("Student record not found.");
}

$student_id = $student["id"];


/* ==========================================
   GET STUDENT SUBJECTS
========================================== */

$stmt = $conn->prepare("
    SELECT
        s.id,
        s.subject_code,
        s.subject_name,
        s.units,
        s.year_level,
        s.semester,
        s.school_year
    FROM student_subjects ss

    INNER JOIN subjects s
        ON ss.subject_id = s.id

    WHERE ss.student_id = ?

    ORDER BY
        s.year_level,
        s.semester,
        s.id
");

$stmt->bind_param("i", $student_id);
$stmt->execute();

$subjects = $stmt->get_result();


/* ==========================================
   STUDENT NAME
========================================== */

$fullName = htmlspecialchars(
    $student["first_name"] . " " . $student["last_name"]
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Subjects</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f7fb;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            width: 240px;
            height: 100vh;
            background: #17324d;
            color: white;
            padding: 30px 20px;
        }

        .sidebar h2 {
            margin-bottom: 35px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
        }

        .sidebar a:hover {
            background: #284d70;
        }

        .logout {
            background: #c0392b;
        }

        /* MAIN */

        .main {
            margin-left: 240px;
            padding: 30px;
        }

        .topbar {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .topbar h1 {
            margin-bottom: 8px;
        }

        /* SUBJECTS */

        .subjects-container {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .subject-card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow:
                0 3px 12px rgba(0,0,0,0.08);
        }

        .subject-code {
            font-size: 20px;
            font-weight: bold;
            color: #17324d;
            margin-bottom: 10px;
        }

        .subject-name {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .subject-info {
            color: #555;
            line-height: 1.8;
        }

        .no-subjects {
            background: white;
            padding: 25px;
            border-radius: 12px;
        }

        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 16px;
            background: #17324d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

    </style>

</head>


<body>


<!-- ==========================================
     SIDEBAR
========================================== -->

<div class="sidebar">

    <h2>
        Student Portal
    </h2>

    <a href="student_dashboard.php">
        Dashboard
    </a>

    <a href="#">
        My Grades
    </a>

    <a href="subjects.php">
        Subjects
    </a>

    <a href="#">
        Profile
    </a>

    <a
        href="logout.php"
        class="logout"
    >
        Logout
    </a>

</div>


<!-- ==========================================
     MAIN CONTENT
========================================== -->

<div class="main">

    <a
        href="student_dashboard.php"
        class="back-button"
    >
        ← Back to Dashboard
    </a>


    <div class="topbar">

        <h1>
            My Subjects
        </h1>

        <p>
            Student: <?php echo $fullName; ?>
        </p>

        <p>
            Student ID:
            <?php echo htmlspecialchars($student["student_id"]); ?>
        </p>

    </div>


    <!-- ==========================================
         SUBJECT LIST
    ========================================== -->

    <div class="subjects-container">

        <?php if ($subjects->num_rows > 0): ?>

            <?php while ($subject = $subjects->fetch_assoc()): ?>

                <div class="subject-card">

                    <div class="subject-code">

                        <?php
                        echo htmlspecialchars(
                            $subject["subject_code"]
                        );
                        ?>

                    </div>


                    <div class="subject-name">

                        <?php
                        echo htmlspecialchars(
                            $subject["subject_name"]
                        );
                        ?>

                    </div>


                    <div class="subject-info">

                        <strong>Units:</strong>

                        <?php
                        echo htmlspecialchars(
                            $subject["units"]
                        );
                        ?>

                        <br>


                        <strong>Year:</strong>

                        <?php
                        echo htmlspecialchars(
                            $subject["year_level"]
                        );
                        ?>

                        <br>


                        <strong>Semester:</strong>

                        <?php
                        echo htmlspecialchars(
                            $subject["semester"]
                        );
                        ?>

                        <br>


                        <strong>School Year:</strong>

                        <?php
                        echo htmlspecialchars(
                            $subject["school_year"]
                        );
                        ?>

                    </div>

                </div>

            <?php endwhile; ?>


        <?php else: ?>

            <div class="no-subjects">

                <h3>
                    No Subjects Found
                </h3>

                <p>
                    You currently have no enrolled subjects.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>


</body>

</html>