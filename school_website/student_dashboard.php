
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: login.html");
    exit();
}

require_once "db_connect.php";

$conn->set_charset("utf8mb4");

$user_id = (int) $_SESSION["user_id"];

/* =====================================
   GET LINKED STUDENT RECORD
===================================== */

$student_sql = "
    SELECT
        u.id AS user_id,
        u.student_number,
        u.full_name,
        st.id AS student_id,
        st.first_name,
        st.last_name
    FROM users u
    INNER JOIN students st
        ON TRIM(st.student_id) = TRIM(u.student_number)
    WHERE u.id = ?
      AND u.role = 'student'
    LIMIT 1
";

$stmt = $conn->prepare($student_sql);

if (!$stmt) {
    error_log("Student lookup failed: " . $conn->error);
    exit("Unable to load your student record.");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

if (!$student || empty($student["student_id"])) {
    $conn->close();
    exit("Your student record is not linked correctly. Please contact the administrator.");
}

$student_id = (int) $student["student_id"];

/* =====================================
   GET ASSIGNED SUBJECTS WITHOUT DUPLICATES
===================================== */

$sql = "
    SELECT
        s.id AS subject_id,
        s.subject_code,
        s.subject_name,
        s.year_level,
        s.semester,
        CASE
            WHEN MAX(
                CASE
                    WHEN UPPER(TRIM(ss.enrollment_type)) = 'BACK_SUBJECT'
                    THEN 1 ELSE 0
                END
            ) = 1
            THEN 'BACK_SUBJECT'
            ELSE 'REGULAR'
        END AS enrollment_type
    FROM student_subjects ss
    INNER JOIN subjects s
        ON s.id = ss.subject_id
    WHERE ss.student_id = ?
    GROUP BY
        s.id,
        s.subject_code,
        s.subject_name,
        s.year_level,
        s.semester
    ORDER BY
        s.year_level,
        s.semester,
        s.subject_code
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Assigned subjects query failed: " . $conn->error);
    $conn->close();
    exit("Unable to load assigned subjects.");
}

$stmt->bind_param("i", $student_id);
$stmt->execute();

$subjects_result = $stmt->get_result();

$regular_count = 0;
$back_subject_count = 0;
$assigned_subjects = [];

while ($row = $subjects_result->fetch_assoc()) {
    $assigned_subjects[] = $row;

    if ($row["enrollment_type"] === "BACK_SUBJECT") {
        $back_subject_count++;
    } else {
        $regular_count++;
    }
}

$stmt->close();

$conn->close();

$name = htmlspecialchars(
    $_SESSION["full_name"] ?? $student["full_name"] ?? "Student",
    ENT_QUOTES,
    "UTF-8"
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

    <title>Student Dashboard</title>


    <style>

        /* =========================
           GENERAL
        ========================= */

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }


        body {
            background: #f4f7fb;
        }


       
/* ==========================================
   SIDEBAR
========================================== */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;

    width: 240px;
    height: 100vh;

    /* Sidebar background image */
    background-image:
        linear-gradient(
            rgba(45, 25, 60, 0.55),
            rgba(45, 25, 60, 0.55)
        ),
        url("img.jpg");

    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;

    color: white;
    padding: 30px 20px;
    overflow-y: auto;
    z-index: 1000;
}

.sidebar h2 {
    margin-bottom: 35px;
    font-size: 25px;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 8px;
    transition: 0.2s;
}

.sidebar a:hover {
    background: rgba(255, 255, 255, 0.2);
}


        /* =========================
           MAIN CONTENT
        ========================= */

        .main {
            margin-left: 240px;
            padding: 30px;
        }


        /* =========================
           TOP BAR
        ========================= */

        .topbar {
            background: white;

            padding: 20px;

            border-radius: 12px;

            margin-bottom: 25px;
        }


        .topbar h1 {
            margin-bottom: 5px;
        }


        /* =========================
           DASHBOARD CARDS
        ========================= */

        .card {
            display: block;

            background: white;

            padding: 25px;

            border-radius: 12px;

            box-shadow:
                0 3px 12px
                rgba(0, 0, 0, 0.08);

            margin-bottom: 10px;

            color: black;

            text-decoration: none;

            cursor: pointer;
        }


        /* Card hover effect */

        .card:hover {
            box-shadow:
                0 5px 15px
                rgba(0, 0, 0, 0.12);
        }


        .card h3 {
            margin-bottom: 10px;

            color: black;
        }


        .card p {
            color: black;
        }


    </style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->



<div class="sidebar">
    <h2>Student Portal</h2>

    <a href="student_dashboard.php">Dashboard</a>
    <a href="student_profile.php">Student Profile</a>
    <a href="student_subjects.php">My Subjects</a>
    <a href="my_grades.php">My Grades</a>
    <a href="student_back_subjects.php">Back Subjects</a>
    <a href="logout.php">Logout</a>
</div>




<!-- =========================
     MAIN CONTENT
========================= -->

<div class="main">


    <!-- =========================
         WELCOME SECTION
    ========================= -->

    <div class="topbar">

        <h1>
            Welcome, <?php echo $name; ?>!
        </h1>

        <p>
            Student Dashboard
        </p>

    </div>

    <div class="card">
    <h3>My Enrollment Summary</h3>

    <p>
        Regular Subjects:
        <strong><?= $regular_count ?></strong>
    </p>

    <p>
        Back Subjects:
        <strong><?= $back_subject_count ?></strong>
    </p>

    <p>
        Total Assigned Subjects:
        <strong><?= count($assigned_subjects) ?></strong>
    </p>
</div>

<div class="card">
    <h3>My Assigned Subjects</h3>

    <?php if (!empty($assigned_subjects)): ?>
        <div style="overflow-x: auto;">
            <table style="width:100%; border-collapse:collapse; margin-top:15px;">
                <thead>
                    <tr>
                        <th style="padding:12px; text-align:left;">Code</th>
                        <th style="padding:12px; text-align:left;">Subject</th>
                        <th style="padding:12px; text-align:left;">Year</th>
                        <th style="padding:12px; text-align:left;">Semester</th>
                        <th style="padding:12px; text-align:left;">Type</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($assigned_subjects as $subject): ?>
                        <tr>
                            <td style="padding:12px; border-top:1px solid #ddd;">
                                <?= htmlspecialchars($subject["subject_code"] ?? "", ENT_QUOTES, "UTF-8") ?>
                            </td>

                            <td style="padding:12px; border-top:1px solid #ddd;">
                                <?= htmlspecialchars($subject["subject_name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                            </td>

                            <td style="padding:12px; border-top:1px solid #ddd;">
                                <?= htmlspecialchars($subject["year_level"] ?? "", ENT_QUOTES, "UTF-8") ?>
                            </td>

                            <td style="padding:12px; border-top:1px solid #ddd;">
                                <?= htmlspecialchars($subject["semester"] ?? "", ENT_QUOTES, "UTF-8") ?>
                            </td>

                            <td style="padding:12px; border-top:1px solid #ddd;">
                                <?= htmlspecialchars($subject["enrollment_type"] ?? "REGULAR", ENT_QUOTES, "UTF-8") ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="margin-top:15px;">
            No subjects have been assigned to your account yet.
        </p>
    <?php endif; ?>
</div>



    <!-- =========================
         MY GRADES CARD
    ========================= -->

    <a
        href="my_grades.php"
        class="card"
    >

        <h3>
            My Grades
        </h3>

        <p>
            View your grades and academic results.
        </p>

    </a>



    <!-- =========================
         SUBJECTS CARD
    ========================= -->

    <a
        href="subjects.php"
        class="card"
    >

        <h3>
            Subjects
        </h3>

        <p>
            View your enrolled subjects.
        </p>

    </a>



    <!-- =========================
         STUDENT PROFILE CARD
    ========================= -->

    <a
        href="student_profile.php"
        class="card"
    >

        <h3>
            Student Profile
        </h3>

        <p>
            View your student information.
        </p>

    </a>


</div>


</body>

</html>