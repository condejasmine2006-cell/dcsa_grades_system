
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

$userId = (int) $_SESSION["user_id"];

/* =====================================
   GET STUDENT INFORMATION AND SECTION
===================================== */
$sql = "
    SELECT
        u.id,
        u.full_name,
        u.username,
        u.student_number,
        sec.section_name
    FROM users u
    LEFT JOIN students st
        ON TRIM(st.student_id) = TRIM(u.student_number)
    LEFT JOIN sections sec
        ON sec.id = st.section_id
    WHERE u.id = ?
      AND u.role = 'student'
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Student profile query failed: " . $conn->error);
    exit("Unable to load your profile. Please try again later.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();

$stmt->close();

if (!$student) {
    $conn->close();
    exit("Student profile not found.");
}

/* =====================================
   ESCAPE OUTPUT
===================================== */
function h($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

$name = $student["full_name"] ?? "Student";
$username = $student["username"] ?? "";
$studentNumber = $student["student_number"] ?? "";
$studentSection = $student["section_name"] ?? "";

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>

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
            background: #17324d;
            color: white;
            padding: 30px 20px;
            z-index: 1000;
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

        .sidebar a:hover,
        .sidebar a.active {
            background: #284d70;
        }

        .sidebar .logout {
            background: #c0392b;
        }

        .sidebar .logout:hover {
            background: #a93226;
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
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
        }

        .topbar h1 {
            margin-bottom: 8px;
            color: #17324d;
        }

        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .profile-card h2 {
            margin-bottom: 25px;
            color: #17324d;
        }

        .info {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 17px 0;
            border-bottom: 1px solid #ddd;
            overflow-wrap: anywhere;
        }

        .info strong {
            flex: 0 0 170px;
        }

        .value {
            flex: 1;
        }

        .not-assigned {
            color: #888;
            font-style: italic;
        }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 20px;
            }

            .sidebar h2 {
                margin-bottom: 15px;
            }

            .main {
                margin-left: 0;
                padding: 15px;
            }

            .profile-card {
                padding: 20px;
            }

            .info {
                flex-direction: column;
                gap: 8px;
            }

            .info strong {
                flex: auto;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Student Portal</h2>

        <a href="student_dashboard.php">Dashboard</a>
        <a href="my_grades.php">My Grades</a>
        <a href="subjects.php">Subjects</a>
        <a href="student_profile.php" class="active">Profile</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="main">

        <div class="topbar">
            <h1>Student Profile</h1>
            <p>View your student information.</p>
        </div>

        <div class="profile-card">
            <h2>Personal Information</h2>

            <div class="info">
                <strong>Full Name:</strong>
                <span class="value"><?= h($name) ?></span>
            </div>

            <div class="info">
                <strong>Username:</strong>
                <span class="value"><?= h($username) ?></span>
            </div>

            <div class="info">
                <strong>Student Number:</strong>
                <span class="value">
                    <?php if ($studentNumber !== ""): ?>
                        <?= h($studentNumber) ?>
                    <?php else: ?>
                        <span class="not-assigned">Not assigned</span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="info">
                <strong>Section:</strong>
                <span class="value">
                    <?php if ($studentSection !== ""): ?>
                        <?= h($studentSection) ?>
                    <?php else: ?>
                        <span class="not-assigned">Not assigned</span>
                    <?php endif; ?>
                </span>
            </div>

        </div>

    </div>

</body>
</html>