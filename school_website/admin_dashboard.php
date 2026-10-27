
<?php
/* =========================================================
   DCSA GRADING SYSTEM - ADMIN DASHBOARD
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ADMIN ACCESS CHECK */
$userRole = strtolower(trim($_SESSION["role"] ?? ""));

if (!isset($_SESSION["user_id"]) || $userRole !== "admin") {
    header("Location: login.html");
    exit();
}

/* DATABASE CONNECTION */
$host = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "dcsa_grades";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/* HELPER FUNCTION */
function e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

/* ADMIN NAME */
$fullName = $_SESSION["full_name"] ?? "Administrator";

/* COUNT STUDENTS */
$total_students = 0;
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE LOWER(role) = 'student'"
);
if ($result) {
    $total_students = (int)$result->fetch_assoc()["total"];
}

/* COUNT TEACHERS */
$total_teachers = 0;
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE LOWER(role) = 'teacher'"
);
if ($result) {
    $total_teachers = (int)$result->fetch_assoc()["total"];
}

/* COUNT ALL ACCOUNTS */
$total_accounts = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($result) {
    $total_accounts = (int)$result->fetch_assoc()["total"];
}

/* GET STUDENTS */
$students = $conn->query(
    "SELECT id, student_number, full_name, username,
            course, year_level, student_type
     FROM users
     WHERE LOWER(role) = 'student'
     ORDER BY id DESC"
);

/* GET TEACHERS */
$teachers = $conn->query(
    "SELECT id, employee_number, full_name, username, email
     FROM users
     WHERE LOWER(role) = 'teacher'
     ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | DCSA</title>

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
            rgba(45, 25, 60, 0.65),
            rgba(45, 25, 60, 0.65)
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
    margin: 0;
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

/* WELCOME BANNER */
.topbar {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.topbar h1 {
    font-size: 32px;
    margin-bottom: 5px;
    color: #44275c;
}

.topbar p {
    color: #666;
    font-size: 16px;
}

/* CARD GRID */
.cards {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* ALL DASHBOARD CARDS */
.card {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    background: white;
    padding: 25px;
    min-height: 190px;
    border-radius: 12px;
    border-left: 5px solid #44275c;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    text-decoration: none;
    color: #222;
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
}

.card h3 {
    margin-bottom: 10px;
    color: #44275c;
    font-size: 20px;
    line-height: 1.3;
}

.card .number {
    font-size: 32px;
    font-weight: bold;
    color: #44275c;
    margin: 2px 0 10px;
}

.card p {
    color: #666;
    line-height: 1.45;
    margin-bottom: 14px;
    font-size: 15px;
}

.card-action {
    display: inline-block;
    margin-top: auto;
    color: #44275c;
    font-weight: bold;
    text-decoration: none;
}

.card:hover .card-action {
    color: #603b79;
}

/* TABLE SECTION */
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

tr:hover {
    background: #f8f5fa;
}

/* TABLE BUTTONS */
.button {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
    color: white;
    font-size: 13px;
    margin-right: 5px;
}

.edit-button {
    background: #3498db;
}

.delete-button {
    background: #e74c3c;
}

.status {
    display: inline-block;
    padding: 5px 10px;
    background: #e8f8f0;
    color: #16834b;
    border-radius: 15px;
    font-size: 13px;
}

/* RESPONSIVE LAYOUT */
@media (max-width: 1350px) {
    .cards {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 1000px) {
    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
        padding: 20px;
    }

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
        font-size: 25px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>Admin Portal</h2>

    <a href="admin_dashboard.php" class="active">Dashboard</a>
    <a href="admin_students.php">Manage Students</a>
    <a href="admin_teachers.php">Manage Teachers</a>
    <a href="admin_subjects.php">Manage Subjects</a>
    <a href="admin_accounts.php">Manage Accounts</a>
    <a href="admin_reports.php">Reports</a>
    <a href="admin_assign_teacher.php">Assign Teachers</a>
    <a href="admin_enroll_students.php">Enroll Students</a>
    <a href="admin_create_teacher.php">+ Create Teacher Account</a>
    <a href="create_registrar.php">+ Create Registrar Account</a>

    <a
        href="logout.php"
        class="logout"
        onclick="return confirm('Are you sure you want to logout?');"
    >Logout</a>
</div>

<!-- MAIN CONTENT -->
<div class="main">

    <!-- WELCOME -->
    <div class="topbar">
        <h1>Welcome, <?= e($fullName); ?>!</h1>
        <p>Administrator Dashboard</p>
    </div>

    <!-- DASHBOARD CARDS -->
    <div class="cards">

        <a href="admin_students.php" class="card">
            <h3>Students</h3>
            <div class="number"><?= $total_students; ?></div>
            <p>Manage registered student accounts.</p>
            <span class="card-action">Manage Students →</span>
        </a>

        <a href="admin_teachers.php" class="card">
            <h3>Teachers</h3>
            <div class="number"><?= $total_teachers; ?></div>
            <p>Manage registered teachers.</p>
            <span class="card-action">Manage Teachers →</span>
        </a>

        <a href="admin_accounts.php" class="card">
            <h3>Total Accounts</h3>
            <div class="number"><?= $total_accounts; ?></div>
            <p>View all registered accounts.</p>
            <span class="card-action">Manage Accounts →</span>
        </a>

        <a href="admin_subjects.php" class="card">
            <h3>Subjects</h3>
            <p>Add and manage subjects for students and teachers.</p>
            <span class="card-action">Manage Subjects →</span>
        </a>

        <a href="admin_reports.php" class="card">
            <h3>Reports</h3>
            <p>View student grading reports and academic records.</p>
            <span class="card-action">View Reports →</span>
        </a>

        <a href="admin_assign_teacher.php" class="card">
            <h3>Assign Teachers</h3>
            <p>Assign teachers to subjects and sections.</p>
            <span class="card-action">Assign Now →</span>
        </a>

        <a href="admin_enroll_students.php" class="card">
            <h3>Enroll Students</h3>
            <p>Enroll students in their assigned sections and subjects.</p>
            <span class="card-action">Enroll Now →</span>
        </a>

        <a href="admin_create_teacher.php" class="card">
            <h3>Create Teacher Account</h3>
            <p>Create official accounts for teachers.</p>
            <span class="card-action">Create Teacher →</span>
        </a>

        <!-- REGISTRAR CARD: NOW INSIDE THE GRID -->
        <a href="create_registrar.php" class="card">
            <h3>Create Registrar Account</h3>
            <p>Create official accounts for registrars to manage student enrollment and records.</p>
            <span class="card-action">Create Registrar →</span>
        </a>

    </div>

    <!-- REGISTERED STUDENTS -->
    <div class="table-section">
        <h2>Registered Students</h2>

        <table>
            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Student Type</th>
                    <th>Actions</th>
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
                        <td>
                            <span class="status">
                                <?= e($student["student_type"] ?? "Regular"); ?>
                            </span>
                        </td>
                        <td>
                            <a
                                href="edit_user.php?id=<?= (int)$student["id"]; ?>"
                                class="button edit-button"
                            >Edit</a>

                            <a
                                href="delete_user.php?id=<?= (int)$student["id"]; ?>"
                                class="button delete-button"
                                onclick="return confirm('Are you sure you want to delete this student?');"
                            >Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No registered students found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- REGISTERED TEACHERS -->
    <div class="table-section">
        <h2>Registered Teachers</h2>

        <table>
            <thead>
                <tr>
                    <th>Employee Number</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($teachers && $teachers->num_rows > 0): ?>
                <?php while ($teacher = $teachers->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($teacher["employee_number"]); ?></td>
                        <td><?= e($teacher["full_name"]); ?></td>
                        <td><?= e($teacher["username"]); ?></td>
                        <td><?= e($teacher["email"]); ?></td>
                        <td><span class="status">Active</span></td>
                        <td>
                            <a
                                href="edit_user.php?id=<?= (int)$teacher["id"]; ?>"
                                class="button edit-button"
                            >Edit</a>

                            <a
                                href="delete_user.php?id=<?= (int)$teacher["id"]; ?>"
                                class="button delete-button"
                                onclick="return confirm('Are you sure you want to delete this teacher?');"
                            >Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No registered teachers found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php $conn->close(); ?>

</body>
</html>
