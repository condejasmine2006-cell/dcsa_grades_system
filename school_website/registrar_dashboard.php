
<?php
/* =========================================
   DCSA GRADING SYSTEM
   REGISTRAR DASHBOARD
========================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* REGISTRAR ACCESS CHECK */

$userRole = strtolower(trim($_SESSION["role"] ?? ""));

if (
    !isset($_SESSION["user_id"]) ||
    $userRole !== "registrar"
) {
    header("Location: login.html");
    exit();
}

/* DATABASE CONNECTION */

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "dcsa_grades"
);

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

/* HELPER FUNCTION */

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* REGISTRAR NAME */

$fullName = $_SESSION["full_name"] ?? "Registrar";

/* COUNT STUDENTS */

$totalStudents = 0;

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE LOWER(role) = 'student'"
);

if ($result) {
    $totalStudents = (int)$result->fetch_assoc()["total"];
}

/* COUNT REGISTERED STUDENTS */

$totalEnrolled = 0;

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE LOWER(role) = 'student'
       AND LOWER(COALESCE(student_status, '')) = 'enrolled'"
);

if ($result) {
    $totalEnrolled = (int)$result->fetch_assoc()["total"];
}

/* GET STUDENTS */

$students = $conn->query(
    "SELECT
        id,
        student_number,
        full_name,
        username,
        course,
        year_level,
        student_type,
        student_status
     FROM users
     WHERE LOWER(role) = 'student'
     ORDER BY id DESC
     LIMIT 20"
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

<title>Registrar Dashboard | DCSA</title>

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

/* SIDEBAR */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 240px;
    height: 100vh;

    background-image:
        linear-gradient(
            rgba(45, 25, 60, 0.70),
            rgba(45, 25, 60, 0.70)
        ),
        url("school_building.png");

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
    text-align: center;
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

.sidebar a:hover,
.sidebar a.active {
    background: rgba(255,255,255,0.20);
}

.sidebar .logout {
    position: absolute;
    left: 20px;
    right: 20px;
    bottom: 25px;
    background: #c0392b;
    color: white;
    text-align: center;
    font-weight: bold;
}

.sidebar .logout:hover {
    background: #a93226;
}

/* MAIN CONTENT */

.main {
    margin-left: 240px;
    padding: 30px;
    min-height: 100vh;
}

/* WELCOME */

.topbar {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.topbar h1 {
    font-size: 30px;
    margin-bottom: 5px;
    color: #44275c;
}

.topbar p {
    color: #666;
}

/* DASHBOARD CARDS */

.cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    background: white;
    padding: 25px;
    min-height: 180px;
    border-radius: 12px;
    border-left: 5px solid #44275c;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    text-decoration: none;
    color: #222;
    transition: 0.2s;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
}

.card h3 {
    margin-bottom: 10px;
    color: #44275c;
    font-size: 20px;
}

.card .number {
    font-size: 32px;
    font-weight: bold;
    color: #44275c;
    margin: 5px 0 10px;
}

.card p {
    color: #666;
    line-height: 1.5;
    margin-bottom: 14px;
}

.card-action {
    margin-top: auto;
    color: #44275c;
    font-weight: bold;
}

/* TABLE */

.table-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.table-section h2 {
    color: #44275c;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th {
    background: #44275c;
    color: white;
    padding: 13px;
    text-align: left;
}

td {
    padding: 13px;
    border-bottom: 1px solid #ddd;
}

tbody tr:hover {
    background: #f8f5fa;
}

.status {
    display: inline-block;
    padding: 5px 10px;
    background: #e8f8f0;
    color: #16834b;
    border-radius: 15px;
    font-size: 13px;
}

.empty {
    text-align: center;
    color: #666;
    padding: 20px;
}

/* RESPONSIVE */

@media (max-width: 1100px) {
    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        padding-bottom: 25px;
    }

    .sidebar .logout {
        position: static;
        margin-top: 20px;
    }

    .main {
        margin-left: 0;
        padding: 15px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    .topbar h1 {
        font-size: 24px;
    }
}

</style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <h2>Registrar Portal</h2>

    <a href="registrar_dashboard.php" class="active">
        Dashboard
    </a>

    <a href="admin_enroll_students.php">
        Student Enrollment
    </a>

    <a href="admin_students.php">
        Student Records
    </a>

    <a href="admin_reports.php">
        Enrollment Reports
    </a>

    <a
        href="logout.php"
        class="logout"
        onclick="return confirm('Are you sure you want to logout?');"
    >
        Logout
    </a>

</div>

<!-- MAIN CONTENT -->

<div class="main">

    <!-- WELCOME -->

    <div class="topbar">

        <h1>
            Welcome, <?= e($fullName); ?>!
        </h1>

        <p>
            Registrar Dashboard
        </p>

    </div>

    <!-- DASHBOARD CARDS -->

    <div class="cards">

        <a href="admin_students.php" class="card">

            <h3>Total Students</h3>

            <div class="number">
                <?= $totalStudents; ?>
            </div>

            <p>
                View registered student accounts.
            </p>

            <span class="card-action">
                View Students →
            </span>

        </a>

        <a href="admin_enroll_students.php" class="card">

            <h3>Enrolled Students</h3>

            <div class="number">
                <?= $totalEnrolled; ?>
            </div>

            <p>
                View students marked as enrolled.
            </p>

            <span class="card-action">
                Manage Enrollment →
            </span>

        </a>

        <a href="admin_reports.php" class="card">

            <h3>Enrollment Reports</h3>

            <p>
                Review student enrollment and academic records.
            </p>

            <span class="card-action">
                View Reports →
            </span>

        </a>

        <a href="admin_enroll_students.php" class="card">

            <h3>Enroll Students</h3>

            <p>
                Manage student enrollment in courses and sections.
            </p>

            <span class="card-action">
                Enroll Now →
            </span>

        </a>

        <a href="admin_students.php" class="card">

            <h3>Student Records</h3>

            <p>
                View student information and academic details.
            </p>

            <span class="card-action">
                View Records →
            </span>

        </a>

    </div>

    <!-- STUDENT RECORDS -->

    <div class="table-section">

        <h2>Student Records</h2>

        <table>

            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Student Type</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($students && $students->num_rows > 0): ?>

                <?php while ($student = $students->fetch_assoc()): ?>

                    <tr>
                        <td><?= e($student["student_number"]); ?></td>
                        <td><?= e($student["full_name"]); ?></td>
                        <td><?= e($student["username"]); ?></td>
                        <td><?= e($student["course"]); ?></td>
                        <td><?= e($student["year_level"]); ?></td>
                        <td><?= e($student["student_type"] ?? "Regular"); ?></td>
                        <td>
                            <?= e($student["student_status"] ?? "Pending"); ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="7" class="empty">
                        No student records found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php
$conn->close();
?>

</body>
</html>
