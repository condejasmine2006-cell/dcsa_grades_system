<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "teacher"
) {
    header("Location: login.html");
    exit();
}

require_once "db_connect.php";

$teacherId = (int) $_SESSION["user_id"];
$teacherName = htmlspecialchars(
    $_SESSION["full_name"] ?? "Teacher",
    ENT_QUOTES,
    "UTF-8"
);


/* Get students enrolled in subjects assigned to this teacher */
$sql = "
    SELECT
        s.id,
        s.student_id,
        s.first_name,
        s.last_name,
        GROUP_CONCAT(
            DISTINCT CONCAT(
                sub.subject_code,
                ' - ',
                sub.subject_name
            )
            ORDER BY sub.subject_name
            SEPARATOR ', '
        ) AS subjects
    FROM students s
    INNER JOIN student_subjects ss
        ON ss.student_id = s.id
    INNER JOIN subjects sub
        ON sub.id = ss.subject_id
    INNER JOIN teacher_subjects ts
        ON ts.subject_id = ss.subject_id
    WHERE ts.teacher_id = ?
    GROUP BY
        s.id,
        s.student_id,
        s.first_name,
        s.last_name
    ORDER BY s.last_name ASC, s.first_name ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Student query preparation failed: " . $conn->error);
    die("Unable to load students. Please check the database configuration.");
}

$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();


function h($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

 <title>My Students</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f7fb;
            color: #222;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            height: 100vh;
            padding: 30px 15px;
            background: #205c4a;
            color: white;
            overflow-y: auto;
        }

        .sidebar h2 {
            margin: 0 0 35px;
            font-size: 24px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 13px 12px;
            margin-bottom: 9px;
            border-radius: 8px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #327861;
        }

        .sidebar a.logout {
            background: #c0392b;
            margin-top: 15px;
        }

        .main {
            margin-left: 240px;
            padding: 30px;
            min-height: 100vh;
        }

        .topbar {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,.06);
        }

        .topbar h1 {
            margin-bottom: 8px;
            color: #205c4a;
        }

        .panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.06);
        }

        .panel h2 {
            margin-bottom: 12px;
        }

        .count {
            color: #555;
            margin-bottom: 18px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #205c4a;
            color: white;
        }

        tr:hover {
            background: #f1f7f4;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #666;
        }

        .back {
            display: inline-block;
            margin-bottom: 18px;
            color: #205c4a;
            text-decoration: none;
            font-weight: bold;
        }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .sidebar h2 {
                margin-bottom: 15px;
            }

            .main {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<!-- Teacher sidebar -->
<div class="sidebar">

    <h2>Teacher Portal</h2>

    <a href="teacher_dashboard.php">
        Dashboard
    </a>

    <a href="teacher_students.php">
        My Students
    </a>

    <a href="teacher_grades.php">
        Manage Grades
    </a>

    <a href="teacher_subjects.php">
        Subjects
    </a>

    <a href="teacher_profile.php">
        Profile
    </a>

    <a href="logout.php" class="logout">
        Logout
    </a>

</div>

<!-- Main content -->
<div class="main">

    <div class="topbar">

        <a href="teacher_dashboard.php" class="back">
            &larr; Back to Dashboard
        </a>

        <h1>My Students</h1>

<p>
    Welcome, <?php echo $teacherName; ?>!
</p>

<p>
    View the students enrolled in your assigned subjects.
</p>

    </div>

    
<div class="panel">

    <h2>My Students</h2>

    <p class="count">
        Total students:
        <?php echo $result->num_rows; ?>
    </p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Enrolled Subjects</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result->num_rows > 0): ?>

                    <?php $number = 1; ?>

                    <?php while ($student = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo $number++; ?>
                            </td>

                            <td>
                                <?php echo h($student["student_id"]); ?>
                            </td>

                            <td>
                                <?php
                                echo h(
                                    $student["first_name"] . " " .
                                    $student["last_name"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo h($student["subjects"]); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" class="empty">
                            No students are currently enrolled in
                            your assigned subjects.
                        </td>
                    </tr>

                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>


    </div>

</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
