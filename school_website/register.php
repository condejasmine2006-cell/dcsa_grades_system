
<?php
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
|--------------------------------------------------------------------------
| DCSA GRADING SYSTEM
| STUDENT REGISTRATION
|--------------------------------------------------------------------------
*/

require_once "database.php";

$conn->set_charset("utf8mb4");


/*
|--------------------------------------------------------------------------
| 1. ACCEPT POST REQUESTS ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.html");
    exit;
}


/*
|--------------------------------------------------------------------------
| 2. HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function getPost(string $key): string
{
    $value = $_POST[$key] ?? "";

    return is_string($value) ? trim($value) : "";
}

function stopRegistration(string $message): void
{
    http_response_code(400);

    exit(
        htmlspecialchars(
            $message,
            ENT_QUOTES,
            "UTF-8"
        )
    );
}

/*
 * Bind a dynamic list of values to a prepared statement.
 */
function bindValues(
    mysqli_stmt $stmt,
    array &$values
): void {
    $types = str_repeat("s", count($values));

    $parameters = [$types];

    foreach ($values as &$value) {
        $parameters[] = &$value;
    }

    unset($value);

    call_user_func_array(
        [$stmt, "bind_param"],
        $parameters
    );
}


/*
|--------------------------------------------------------------------------
| 3. ACCOUNT INFORMATION
|--------------------------------------------------------------------------
*/

$username = getPost("username");
$email = getPost("email");

$password = $_POST["password"] ?? "";
$confirm_password = $_POST["confirm_password"] ?? "";


/*
|--------------------------------------------------------------------------
| 4. STUDENT INFORMATION
|--------------------------------------------------------------------------
*/

$student_number = getPost("student_number");

$student_status = strtolower(
    getPost("student_status")
);

$student_type = strtolower(
    getPost("student_type")
);

$semester = getPost("semester");

$program = strtoupper(
    getPost("program")
);

$year_level = getPost("year_level");


/*
|--------------------------------------------------------------------------
| 5. PERSONAL INFORMATION
|--------------------------------------------------------------------------
*/

$first_name = getPost("first_name");
$middle_name = getPost("middle_name");
$last_name = getPost("last_name");
$extension_name = getPost("extension_name");

$birthdate = getPost("birthdate");
$gender = getPost("gender");


/*
|--------------------------------------------------------------------------
| 6. ADDRESS AND CONTACT INFORMATION
|--------------------------------------------------------------------------
|
| IMPORTANT:
| These names match your actual register.html.
|--------------------------------------------------------------------------
*/

$home_address = getPost("home_address");
$city = getPost("city");
$barangay = getPost("barangay");

$mobile_number = getPost("contact_number");


/*
|--------------------------------------------------------------------------
| 7. EDUCATIONAL INFORMATION
|--------------------------------------------------------------------------
|
| These names match your actual register.html.
|--------------------------------------------------------------------------
*/

$previous_school = getPost("school_last_attended");

$previous_course = getPost("previous_course");

$previous_school_address = getPost("school_address");


/*
|--------------------------------------------------------------------------
| 8. PARENT / GUARDIAN INFORMATION
|--------------------------------------------------------------------------
*/

$parent_guardian = getPost("guardian_name");

$parent_contact_number = getPost(
    "guardian_contact_number"
);


/*
|--------------------------------------------------------------------------
| 9. REQUIRED FIELD VALIDATION
|--------------------------------------------------------------------------
*/

$required_fields = [
    "Username" => $username,
    "Email" => $email,
    "Password" => $password,
    "Confirm Password" => $confirm_password,

    "Student Number" => $student_number,
    "Student Status" => $student_status,
    "Student Type" => $student_type,
    "Semester" => $semester,
    "Program" => $program,
    "Year Level" => $year_level,

    "First Name" => $first_name,
    "Last Name" => $last_name,
    "Birthdate" => $birthdate,
    "Gender" => $gender,

    "Mobile Number" => $mobile_number,
    "Home Address" => $home_address,
    "City" => $city,
    "Barangay" => $barangay,

    "Previous School" => $previous_school,
    "Course" => $previous_course,
    "School Address" => $previous_school_address,

    "Parent/Guardian Name" => $parent_guardian,
    "Parent Contact Number" => $parent_contact_number
];

foreach ($required_fields as $field => $value) {

    if ($value === "") {

        stopRegistration(
            "Please complete the required field: " . $field
        );
    }
}


/*
|--------------------------------------------------------------------------
| 10. VALIDATE USERNAME
|--------------------------------------------------------------------------
*/

if (!preg_match('/^[A-Za-z0-9._-]{4,50}$/', $username)) {

    stopRegistration(
        "Username must be 4-50 characters and may contain "
        . "letters, numbers, dots, underscores, and hyphens."
    );
}


/*
|--------------------------------------------------------------------------
| 11. VALIDATE EMAIL
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    stopRegistration(
        "Please enter a valid email address."
    );
}


/*
|--------------------------------------------------------------------------
| 12. VALIDATE PASSWORD
|--------------------------------------------------------------------------
*/

if (!is_string($password) || !is_string($confirm_password)) {

    stopRegistration("Invalid password.");
}

if ($password !== $confirm_password) {

    stopRegistration("Passwords do not match.");
}

if (strlen($password) < 8) {

    stopRegistration(
        "Password must contain at least 8 characters."
    );
}


/*
|--------------------------------------------------------------------------
| 13. VALIDATE STUDENT NUMBER
|--------------------------------------------------------------------------
*/

if (!preg_match('/^[A-Za-z0-9-]{1,30}$/', $student_number)) {

    stopRegistration("Invalid student number.");
}


/*
|--------------------------------------------------------------------------
| 14. VALIDATE STUDENT STATUS
|--------------------------------------------------------------------------
|
| HTML values: NEW and OLD.
|--------------------------------------------------------------------------
*/

if (!in_array(
    $student_status,
    ["new", "old"],
    true
)) {

    stopRegistration(
        "Please select New Student or Old Student."
    );
}


/*
|--------------------------------------------------------------------------
| 15. VALIDATE STUDENT TYPE
|--------------------------------------------------------------------------
|
| HTML values: REGULAR and IRREGULAR.
|--------------------------------------------------------------------------
*/

if (!in_array(
    $student_type,
    ["regular", "irregular"],
    true
)) {

    stopRegistration(
        "Please select Regular or Irregular."
    );
}


/*
|--------------------------------------------------------------------------
| 16. VALIDATE SEMESTER
|--------------------------------------------------------------------------
*/

if (!in_array(
    $semester,
    ["1st", "2nd"],
    true
)) {

    stopRegistration("Please select a valid semester.");
}


/*
|--------------------------------------------------------------------------
| 17. VALIDATE PROGRAM
|--------------------------------------------------------------------------
*/

if ($program !== "BSIT") {

    stopRegistration("Please select a valid program.");
}


/*
|--------------------------------------------------------------------------
| 18. VALIDATE YEAR LEVEL
|--------------------------------------------------------------------------
|
| Your HTML submits "1st Year", not "1st".
| Accept the exact values used by your existing form.
|--------------------------------------------------------------------------
*/

$valid_year_levels = [
    "1st Year",
    "2nd Year",
    "3rd Year",
    "4th Year"
];

if (!in_array(
    $year_level,
    $valid_year_levels,
    true
)) {

    stopRegistration("Please select a valid year level.");
}


/*
|--------------------------------------------------------------------------
| 19. VALIDATE GENDER
|--------------------------------------------------------------------------
*/

if (!in_array(
    $gender,
    ["Male", "Female"],
    true
)) {

    stopRegistration("Please select a valid gender.");
}


/*
|--------------------------------------------------------------------------
| 20. VALIDATE BIRTHDATE
|--------------------------------------------------------------------------
*/

$date = DateTime::createFromFormat(
    "!Y-m-d",
    $birthdate
);

$date_errors = DateTime::getLastErrors();

if (
    !$date ||
    $date->format("Y-m-d") !== $birthdate ||
    (
        $date_errors !== false &&
        (
            $date_errors["warning_count"] > 0 ||
            $date_errors["error_count"] > 0
        )
    )
) {

    stopRegistration("Please enter a valid birthdate.");
}

if ($date > new DateTime("today")) {

    stopRegistration(
        "Birthdate cannot be in the future."
    );
}


/*
|--------------------------------------------------------------------------
| 21. VALIDATE MOBILE NUMBER
|--------------------------------------------------------------------------
|
| Expected Philippine mobile format: 09XXXXXXXXX
|--------------------------------------------------------------------------
*/

if (!preg_match('/^09[0-9]{9}$/', $mobile_number)) {

    stopRegistration(
        "Please enter a valid 11-digit mobile number "
        . "starting with 09."
    );
}


/*
|--------------------------------------------------------------------------
| 22. VALIDATE PARENT CONTACT NUMBER
|--------------------------------------------------------------------------
*/

if (!preg_match(
    '/^09[0-9]{9}$/',
    $parent_contact_number
)) {

    stopRegistration(
        "Please enter a valid 11-digit parent/guardian "
        . "contact number starting with 09."
    );
}


/*
|--------------------------------------------------------------------------
| 23. CREATE FULL NAME
|--------------------------------------------------------------------------
*/

$name_parts = [
    $first_name
];

if ($middle_name !== "") {
    $name_parts[] = $middle_name;
}

$name_parts[] = $last_name;

if ($extension_name !== "") {
    $name_parts[] = $extension_name;
}

$full_name = implode(" ", $name_parts);


/*
|--------------------------------------------------------------------------
| 24. ACCOUNT SETTINGS
|--------------------------------------------------------------------------
|
| Public registration always creates a student account.
|--------------------------------------------------------------------------
*/

$role = "student";

/*
 * Preserve compatibility with pages that use the course column.
 */
$course = $program;

/*
 * Enrollment type reflects regular or irregular status.
 */
$enrollment_type = strtoupper($student_type);

/*
 * Hash the password before saving it.
 */
$hashed_password = password_hash(
    $password,
    PASSWORD_DEFAULT
);

if ($hashed_password === false) {

    stopRegistration(
        "Unable to secure your password. Please try again."
    );
}


/*
|--------------------------------------------------------------------------
| 25. DATABASE REGISTRATION
|--------------------------------------------------------------------------
*/

$stmt = null;
$transaction_started = false;

try {

    /*
    |--------------------------------------------------------------------------
    | START TRANSACTION
    |--------------------------------------------------------------------------
    */

    $conn->begin_transaction();

    $transaction_started = true;


    /*
    |--------------------------------------------------------------------------
    | 26. CHECK DUPLICATE USERNAME
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE username = ? LIMIT 1"
    );

    $stmt->bind_param("s", $username);

    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;

    $stmt->close();
    $stmt = null;

    if ($exists) {

        throw new RuntimeException(
            "Username already exists. Please choose another."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 27. CHECK DUPLICATE EMAIL
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE email = ? LIMIT 1"
    );

    $stmt->bind_param("s", $email);

    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;

    $stmt->close();
    $stmt = null;

    if ($exists) {

        throw new RuntimeException(
            "This email address is already registered."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 28. CHECK DUPLICATE STUDENT NUMBER
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id
         FROM users
         WHERE student_number = ?
         LIMIT 1"
    );

    $stmt->bind_param("s", $student_number);

    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;

    $stmt->close();
    $stmt = null;

    if ($exists) {

        throw new RuntimeException(
            "This student number is already registered."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 29. CHECK EXISTING STUDENT RECORD
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT id
         FROM students
         WHERE student_id = ?
         LIMIT 1"
    );

    $stmt->bind_param("s", $student_number);

    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;

    $stmt->close();
    $stmt = null;

    if ($exists) {

        throw new RuntimeException(
            "A student record with this student number already exists."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 30. INSERT INTO USERS TABLE
    |--------------------------------------------------------------------------
    |
    | This uses the column structure from your original PHP.
    | employee_number is NULL for student accounts.
    |--------------------------------------------------------------------------
    */

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
            student_status,
            semester,
            program,
            first_name,
            middle_name,
            last_name,
            extension_name,
            birthdate,
            gender,
            home_address,
            city,
            barangay,
            mobile_number,
            previous_school,
            previous_course,
            previous_school_address,
            parent_guardian,
            parent_contact_number,
            enrollment_type
        )
        VALUES (
            ?,
            NULL,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
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

    $stmt = $conn->prepare($sql);

    $user_values = [
        $student_number,
        $full_name,
        $username,
        $email,
        $hashed_password,
        $role,
        $course,
        $year_level,
        $student_type,
        $student_status,
        $semester,
        $program,
        $first_name,
        $middle_name,
        $last_name,
        $extension_name,
        $birthdate,
        $gender,
        $home_address,
        $city,
        $barangay,
        $mobile_number,
        $previous_school,
        $previous_course,
        $previous_school_address,
        $parent_guardian,
        $parent_contact_number,
        $enrollment_type
    ];

    bindValues($stmt, $user_values);

    $stmt->execute();

    $stmt->close();
    $stmt = null;


    /*
    |--------------------------------------------------------------------------
    | 31. INSERT INTO STUDENTS TABLE
    |--------------------------------------------------------------------------
    |
    | These columns match your original registration code.
    |--------------------------------------------------------------------------
    */

    $sql = "
        INSERT INTO students (
            student_id,
            first_name,
            last_name,
            email,
            username,
            password,
            status,
            student_status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    $students_status = $student_type;

    $students_student_status = strtoupper(
        $student_status
    );

    $student_values = [
        $student_number,
        $first_name,
        $last_name,
        $email,
        $username,
        $hashed_password,
        $students_status,
        $students_student_status
    ];

    bindValues($stmt, $student_values);

    $stmt->execute();

    $stmt->close();
    $stmt = null;


    /*
    |--------------------------------------------------------------------------
    | 32. SAVE BOTH RECORDS
    |--------------------------------------------------------------------------
    */

    $conn->commit();

    $transaction_started = false;


    /*
    |--------------------------------------------------------------------------
    | 33. REDIRECT TO LOGIN
    |--------------------------------------------------------------------------
    */

    $conn->close();

    header("Location: login.html?registered=success");
    exit();


} catch (RuntimeException $e) {

    /*
    |--------------------------------------------------------------------------
    | REGISTRATION VALIDATION ERRORS
    |--------------------------------------------------------------------------
    */

    if ($transaction_started) {
        $conn->rollback();
        $transaction_started = false;
    }

    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }

    $conn->close();

    stopRegistration($e->getMessage());


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE OR APPLICATION ERRORS
    |--------------------------------------------------------------------------
    */

    if ($transaction_started) {

        try {
            $conn->rollback();
        } catch (Throwable $rollback_error) {

            error_log(
                "Registration rollback error: " .
                $rollback_error->getMessage()
            );
        }
    }

    if ($stmt instanceof mysqli_stmt) {

        try {
            $stmt->close();
        } catch (Throwable $ignored) {
            // Statement may already be closed.
        }
    }

    error_log(
        "DCSA registration error: " .
        $e->getMessage()
    );

    $conn->close();

    http_response_code(500);

    exit(
        "Registration could not be completed. " .
        "Please check your database configuration " .
        "or contact the administrator."
    );
}
?>
