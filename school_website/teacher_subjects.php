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

$sql = "
    SELECT
        s.id,
        s.subject_code,
        s.subject_name,
        s.units
    FROM teacher_subjects ts
    INNER JOIN subjects s
        ON ts.subject_id = s.id
    WHERE ts.teacher_id = ?
    ORDER BY s.subject_name ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log($conn->error);
    die("Unable to load subjects. Check your database tables.");
}

$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();

$totalSubjects = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
        }

        .sidebar h2 {
            margin-bottom: 35px;
        }

        .sidebar a {
            display: block;
            padding: 13px 12px;
            margin-bottom: 9px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #327861;
        }

        .sidebar .logout {
            background: #c0392b;
        }

        .main {
            margin-left: 240px;
            padding: 30px;
            min-height: 100vh;
        }

        .topbar, .panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,.07);
        }

        .back {
            display: inline-block;
            margin-bottom: 25px;
            color: #205c4a;
            text-decoration: none;
        }

        h1 {
            color: #205c4a;
            margin-bottom: 12px;
        }

        .panel h2 {
            margin-bottom: 12px;
        }

        .count {
            margin-bottom: 20px;
            color: #555;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #205c4a;
            color: white;
        }

        tbody tr:hover {
            background: #f1f7f4;
        }

        .empty {
            padding: 25px;
            text-align: center;
            color: #666;
        }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">
    <h2>Teacher Portal</h2>

    <a href="teacher_dashboard.php">Dashboard</a>
    <a href="teacher_students.php">My Students</a>
    <a href="teacher_grades.php">Manage Grades</a>
    <a href="teacher_subjects.php" class="active">Subjects</a>
    <a href="teacher_profile.php">Profile</a>
    <a href="logout.php" class="logout">Logout</a>
</div>

<div class="main">

    <div class="topbar">
        <a href="teacher_dashboard.php" class="back">
            &larr; Back to Dashboard
        </a>

        <h1>My Subjects</h1>

        <p>Welcome, <?php echo $teacherName; ?>!</p>
        <p>View the subjects assigned to your account.</p>
    </div>

    <div class="panel">
        <h2>Assigned Subjects</h2>

        <p class="count">
            Total assigned subjects: <?php echo $totalSubjects; ?>
        </p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Units</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($totalSubjects > 0): ?>
                    <?php $number = 1; ?>

                    <?php while ($subject = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $number++; ?></td>
                            <td><?php echo htmlspecialchars($subject["subject_code"]); ?></td>
                            <td><?php echo htmlspecialchars($subject["subject_name"]); ?></td>
                            <td><?php echo htmlspecialchars((string)$subject["units"]); ?></td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="4" class="empty">
                            No subjects assigned to this teacher yet.
                            Please contact the administrator.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
