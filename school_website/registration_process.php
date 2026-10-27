<?php
session_start();

mysqli_report(MYSQLI_REPORT_OFF);

/* =====================================
   DATABASE CONNECTION
===================================== */
require_once "db_connect.php";

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    error_log("Database connection failed.");
    http_response_code(500);
    exit("Unable to connect to the database. Please try again later.");
}

$conn->set_charset("utf8mb4");

/* =====================================
   HELPER FUNCTIONS
===================================== */
function stopRegistration(string $message, int $statusCode = 400): void
{
    http_response_code($statusCode);

    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f4f7fb;
                padding: 40px 20px;
                color: #333;
            }
            .container {
                max-width: 550px;
                margin: auto;
                background: white;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 3px 12px rgba(0,0,0,.08);
            }
            a {
                color: #205c4a;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Registration Unsuccessful</h2>
            <p>' . htmlspecialchars($message, ENT_QUOTES, "UTF-8") . '</p>
            <a href="register.html">Return to Registration</a>
        </div>
    </body>
    </html>';

    exit();
}

function valueExists(
    mysqli $conn,
    string $column,
    string $value
): bool {
    $allowedColumns = [
        "username",
        "email",
        "student_number",
        "employee_number"
    ];

    if (!in_array($column, $allowedColumns, true)) {
        throw new InvalidArgumentException("Invalid column.");
    }

    $sql = "SELECT id FROM users WHERE `$column` = ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Duplicate-check prepare failed: " . $conn->error);
        stopRegistration("A database error occurred. Please try again.", 500);
    }

    $stmt->bind_param("s", $value);

    if (!$stmt->execute()) {
        error_log("Duplicate-check execution failed: " . $stmt->error);
        $stmt->close();
        stopRegistration("A database error occurred. Please try again.", 500);
    }

    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

/* =====================================
   REQUEST METHOD
===================================== */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.html");
    exit();
}

/* =====================================
   GET FORM DATA
===================================== */
$full_name = trim((string)($_POST["full_name"] ?? ""));
$username = trim((string)($_POST["username"] ?? ""));
$email = trim((string)($_POST["email"] ?? ""));
$password = (string)($_POST["password"] ?? "");
$confirm_password = (string)($_POST["confirm_password"] ?? "");
$role = trim((string)($_POST["role"] ?? ""));

/* Student information */
$student_number = trim((string)($_POST["student_number"] ?? ""));
$student_status = strtolower(trim((string)($_POST["student_status"] ?? "")));
$enrollment_type = trim((string)($_POST["enrollment_type"] ?? "Regular"));
$semester = trim((string)($_POST["semester"] ?? ""));
$program = strtoupper(trim((string)($_POST["program"] ?? "")));
$year_level = trim((string)($_POST["year_level"] ?? ""));

$first_name = trim((string)($_POST["first_name"] ?? ""));
$middle_name = trim((string)($_POST["middle_name"] ?? ""));
$last_name = trim((string)($_POST["last_name"] ?? ""));
$extension_name = trim((string)($_POST["extension_name"] ?? ""));
$birthdate = trim((string)($_POST["birthdate"] ?? ""));
$gender = trim((string)($_POST["gender"] ?? ""));
$home_address = trim((string)($_POST["home_address"] ?? ""));
$city = trim((string)($_POST["city"] ?? ""));
$barangay = trim((string)($_POST["barangay"] ?? ""));
$mobile_number = trim((string)($_POST["mobile_number"] ?? ""));
$previous_school = trim((string)($_POST["previous_school"] ?? ""));
$previous_course = trim((string)($_POST["previous_course"] ?? ""));
$previous_school_address = trim((string)($_POST["previous_school_address"] ?? ""));
$parent_guardian = trim((string)($_POST["parent_guardian"] ?? ""));
$parent_contact_number = trim((string)($_POST["parent_contact_number"] ?? ""));

/* Teacher/admin information */
$employee_number = trim((string)($_POST["employee_number"] ?? ""));
$admin_id = trim((string)($_POST["Admin_ID"] ?? ""));

/* =====================================
   BASIC VALIDATION
===================================== */
if (
    $full_name === "" ||
    $username === "" ||
    $email === "" ||
    $password === "" ||
    $confirm_password === "" ||
    $role === ""
) {
    stopRegistration("Please complete all required fields.");
}

$allowedRoles = ["student", "teacher", "Admin"];

if (!in_array($role, $allowedRoles, true)) {
    stopRegistration("Invalid user role.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    stopRegistration("Please enter a valid email address.");
}

if (strlen($password) < 8) {
    stopRegistration("Password must contain at least 8 characters.");
}

if ($password !== $confirm_password) {
    stopRegistration("Passwords do not match.");
}

if (strlen($username) < 3) {
    stopRegistration("Username must contain at least 3 characters.");
}

/* =====================================
   INITIALIZE OPTIONAL DATABASE FIELDS
===================================== */
$student_number_db = null;
$employee_number_db = null;
$course_db = null;
$year_level_db = null;
$student_type_db = null;
$enrollment_type_db = null;

/* =====================================
   STUDENT VALIDATION
===================================== */
if ($role === "student") {
    $requiredFields = [
        "Student number" => $student_number,
        "Student status" => $student_status,
        "Semester" => $semester,
        "Program" => $program,
        "Year level" => $year_level,
        "First name" => $first_name,
        "Last name" => $last_name,
        "Birthdate" => $birthdate,
        "Gender" => $gender,
        "Home address" => $home_address,
        "City" => $city,
        "Barangay" => $barangay,
        "Mobile number" => $mobile_number,
        "Parent/guardian" => $parent_guardian,
        "Parent contact number" => $parent_contact_number
    ];

    foreach ($requiredFields as $label => $value) {
        if ($value === "") {
            stopRegistration("Please complete the required field: " . $label . ".");
        }
    }

    if (!in_array($student_status, ["new", "old"], true)) {
        stopRegistration("Please select a valid student status.");
    }

    if (!in_array($semester, ["1st", "2nd"], true)) {
        stopRegistration("Please select a valid semester.");
    }

    if (!in_array($program, ["BSIT"], true)) {
        stopRegistration("Please select a valid program.");
    }

    if (!in_array($year_level, ["1st", "2nd", "3rd", "4th"], true)) {
        stopRegistration("Please select a valid year level.");
    }

    if (!in_array($gender, ["Male", "Female"], true)) {
        stopRegistration("Please select a valid gender.");
    }

    if (!in_array($enrollment_type, ["Regular", "Irregular"], true)) {
        stopRegistration("Please select a valid enrollment type.");
    }

    $date = DateTime::createFromFormat("!Y-m-d", $birthdate);

    if (!$date || $date->format("Y-m-d") !== $birthdate) {
        stopRegistration("Please enter a valid birthdate.");
    }

    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $mobile_number)) {
        stopRegistration("Please enter a valid mobile number.");
    }

    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $parent_contact_number)) {
        stopRegistration("Please enter a valid parent/guardian contact number.");
    }

    $student_number_db = $student_number;
    $course_db = $program;
    $year_level_db = $year_level;
    $student_type_db = $student_status;
    $enrollment_type_db = $enrollment_type;
}

/* =====================================
   TEACHER VALIDATION
===================================== */
if ($role === "teacher") {
    if ($employee_number === "") {
        stopRegistration("Please enter the employee number.");
    }

    $employee_number_db = $employee_number;
}

/* =====================================
   ADMIN VALIDATION
===================================== */
if ($role === "Admin" && $admin_id === "") {
    stopRegistration("Please enter the Admin ID.");
}

/* =====================================
   CHECK DUPLICATES
===================================== */
if (valueExists($conn, "username", $username)) {
    stopRegistration("Username already exists. Please choose another.");
}

if (valueExists($conn, "email", $email)) {
    stopRegistration("Email address is already registered.");
}

if (
    $role === "student" &&
    valueExists($conn, "student_number", $student_number)
) {
    stopRegistration("Student number is already registered.");
}

if (
    $role === "teacher" &&
    valueExists($conn, "employee_number", $employee_number)
) {
    stopRegistration("Employee number is already registered.");
}

/* =====================================
   HASH PASSWORD
===================================== */
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

if ($hashed_password === false) {
    error_log("Password hashing failed.");
    stopRegistration("Unable to process your password. Please try again.", 500);
}

/* =====================================
   INSERT USER
===================================== */
$sql = "
    INSERT INTO users (
        student_number,
        employee_number,
        full_name,
        username,
        email,
        password,
        role,
        course,
        year_level,
        student_type,
        enrollment_type
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Registration prepare failed: " . $conn->error);
    stopRegistration("Unable to prepare registration. Please contact the administrator.", 500);
}

/*
 * Exactly 11 placeholders.
 * Exactly 11 type characters.
 * Exactly 11 variables.
 */
$stmt->bind_param(
    "sssssssssss",
    $student_number_db,
    $employee_number_db,
    $full_name,
    $username,
    $email,
    $hashed_password,
    $role,
    $course_db,
    $year_level_db,
    $student_type_db,
    $enrollment_type_db
);

if (!$stmt->execute()) {
    $errorNumber = $stmt->errno;
    error_log("Registration INSERT failed (" . $errorNumber . "): " . $stmt->error);

    $stmt->close();
    $conn->close();

    if ($errorNumber === 1062) {
        stopRegistration("This account information is already registered. Please check the details and try again.");
    }

    stopRegistration("Registration failed due to a database error. Please contact the administrator.", 500);
}

$stmt->close();
$conn->close();

/* =====================================
   SUCCESS
===================================== */
header("Location: login.html?registered=success");
exit();
?>