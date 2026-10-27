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

$sql = "
    SELECT *
    FROM users
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log($conn->error);
    die("Unable to load your profile. Please check your database.");
}

$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    die("Teacher account not found. Please contact the administrator.");
}

$fullName = $_SESSION["full_name"] ?? "Teacher";
if ($fullName === "") {
    $fullName = $_SESSION["full_name"] ?? "Teacher";
}

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

    <title>Teacher Profile</title>

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

        .topbar, .profile-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,.07);
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #205c4a;
            text-decoration: none;
        }

        h1 {
            color: #205c4a;
            margin-bottom: 10px;
        }

        .profile-heading {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 25px;
        }

        .avatar {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            background: #205c4a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: bold;
        }

        .profile-heading p {
            color: #666;
            margin-top: 6px;
        }

        .info-row {
            padding: 17px 0;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .label {
            display: block;
            color: #666;
            font-size: 14px;
            margin-bottom: 7px;
        }

        .value {
            font-size: 16px;
            overflow-wrap: anywhere;
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
    <a href="teacher_subjects.php">Subjects</a>
    <a href="teacher_profile.php" class="active">Profile</a>
    <a href="logout.php" class="logout">Logout</a>
</div>

<div class="main">

    <div class="topbar">
        <a href="teacher_dashboard.php" class="back">
            &larr; Back to Dashboard
        </a>

        <h1>My Profile</h1>
        <p>View your teacher account information.</p>
    </div>

    <div class="profile-card">

        <div class="profile-heading">
            <div class="avatar">
                <?php
                echo h(strtoupper(substr($fullName, 0, 1)));
                ?>
            </div>

            <div>
                <h2><?php echo h($fullName); ?></h2>
                <p>Teacher Account</p>
            </div>
        </div>

        <div class="info-row">
            <span class="label">Teacher ID</span>
            <div class="value">
                <?php echo h($teacher["id"]); ?>
            </div>
        </div>

        <div class="info-row">
            <span class="label">Full Name</span>
            <div class="value">
                <?php echo h($fullName); ?>
            </div>
        </div>

        <div class="info-row">
            <span class="label">Username</span>
            <div class="value">
                <?php echo h($teacher["username"]); ?>
            </div>
        </div>

        <div class="info-row">
            <span class="label">Email Address</span>
            <div class="value">
                <?php echo h($teacher["email"] ?? "Not provided"); ?>
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