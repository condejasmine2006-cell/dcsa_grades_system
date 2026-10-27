
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = strtolower(trim($_SESSION["role"] ?? $_SESSION["user_role"] ?? ""));

if (
    empty($_SESSION["user_id"]) ||
    !in_array($role, ["admin", "administrator", "registrar"], true)
) {
    header("Location: login.html?error=unauthorized");
    exit();
}

require_once __DIR__ . "/db_connect.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn->set_charset("utf8mb4");



$message = "";
$error = "";
$students = [];
$subjects = [];
$sections = [];
$enrollments = [];
$backSubjectCounts = [];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

/* =====================================================
   HANDLE ENROLLMENT OF CURRENT SUBJECTS
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (!is_string($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)) {
        $error = "Invalid request. Please refresh the page.";
    } else {
        $action = $_POST["action"] ?? "enroll_regular";

        $studentId = filter_input(INPUT_POST, "student_id", FILTER_VALIDATE_INT);
        $sectionId = filter_input(INPUT_POST, "section_id", FILTER_VALIDATE_INT);
        $subjectIds = $_POST["subject_ids"] ?? [];

        if (!is_array($subjectIds)) {
            $subjectIds = [];
        }

        $subjectIds = array_values(array_unique(array_filter(
            array_map("intval", $subjectIds),
            fn($id) => $id > 0
        )));

        if ($action !== "enroll_regular") {
            $error = "Invalid action.";
        } elseif (!$studentId || !$sectionId || empty($subjectIds)) {
            $error = "Select a student, at least one subject, and a section.";
        } else {
            try {
                $conn->begin_transaction();

                /* Confirm student exists */
                $stmt = $conn->prepare(
                    "SELECT id FROM students WHERE id = ? LIMIT 1"
                );
                $stmt->bind_param("i", $studentId);
                $stmt->execute();
                $studentExists = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$studentExists) {
                    throw new Exception("The selected student was not found.");
                }

                /* Confirm section exists */
                $stmt = $conn->prepare(
                    "SELECT id FROM sections WHERE id = ? LIMIT 1"
                );
                $stmt->bind_param("i", $sectionId);
                $stmt->execute();
                $sectionExists = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$sectionExists) {
                    throw new Exception("The selected section was not found.");
                }

                $subjectCheck = $conn->prepare(
                    "SELECT id, subject_code
                     FROM subjects
                     WHERE id = ?
                     LIMIT 1"
                );

                $duplicateCheck = $conn->prepare(
                    "SELECT id
                     FROM student_subjects
                     WHERE student_id = ?
                       AND subject_id = ?
                       AND section_id = ?
                       AND UPPER(TRIM(COALESCE(enrollment_type, 'REGULAR'))) = 'REGULAR'
                     LIMIT 1"
                );

                $insert = $conn->prepare(
                    "INSERT INTO student_subjects
                        (student_id, subject_id, section_id, enrollment_type)
                     VALUES (?, ?, ?, 'REGULAR')"
                );

                $added = 0;
                $duplicates = [];
                $invalid = [];

                foreach ($subjectIds as $subjectId) {
                    $subjectCheck->bind_param("i", $subjectId);
                    $subjectCheck->execute();
                    $subject = $subjectCheck->get_result()->fetch_assoc();

                    if (!$subject) {
                        $invalid[] = $subjectId;
                        continue;
                    }

                    $duplicateCheck->bind_param(
                        "iii",
                        $studentId,
                        $subjectId,
                        $sectionId
                    );
                    $duplicateCheck->execute();

                    if ($duplicateCheck->get_result()->num_rows > 0) {
                        $duplicates[] = $subject["subject_code"];
                        continue;
                    }

                    $insert->bind_param(
                        "iii",
                        $studentId,
                        $subjectId,
                        $sectionId
                    );
                    $insert->execute();
                    $added++;
                }

                $subjectCheck->close();
                $duplicateCheck->close();
                $insert->close();

                $conn->commit();

                if ($added > 0) {
                    $message = "$added current subject(s) enrolled as REGULAR.";
                } else {
                    $error = "No new subjects were enrolled.";
                }

                if ($duplicates) {
                    $message .= " Already enrolled: " . implode(", ", $duplicates) . ".";
                }

                if ($invalid) {
                    $message .= " Some invalid subject IDs were skipped.";
                }

            } catch (Throwable $ex) {
                try {
                    $conn->rollback();
                } catch (Throwable $ignored) {
                }

                error_log("Enrollment error: " . $ex->getMessage());
                $error = "Unable to save enrollment. Please check the student, subject, and section records.";
            }
        }
    }
}

/* =====================================================
   LOAD STUDENTS
   Supports student_status, status, or no status column.
===================================================== */

$statusColumnAvailable = false;

try {
    $studentColumns = [];
    $columnResult = $conn->query("SHOW COLUMNS FROM students");

    while ($column = $columnResult->fetch_assoc()) {
        $studentColumns[] = $column["Field"];
    }

    if (in_array("student_status", $studentColumns, true)) {
        $statusExpression = "st.student_status";
        $statusColumnAvailable = true;
    } elseif (in_array("status", $studentColumns, true)) {
        $statusExpression = "st.status";
        $statusColumnAvailable = true;
    } else {
        $statusExpression = "'regular'";
    }

    $sql = "
        SELECT
            st.id,
            st.student_id,
            st.first_name,
            st.last_name,
            st.email,
            {$statusExpression} AS student_status,
            CONCAT(st.first_name, ' ', st.last_name) AS full_name,
            CASE
                WHEN LOWER(TRIM(COALESCE({$statusExpression}, '')))
                     LIKE '%irregular%' THEN 'OLD'
                WHEN LOWER(TRIM(COALESCE({$statusExpression}, '')))
                     LIKE '%old%' THEN 'OLD'
                ELSE 'NEW'
            END AS enrollment_type
        FROM students st
        ORDER BY st.last_name ASC, st.first_name ASC
    ";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    if (!$statusColumnAvailable) {
        $message = "Students loaded successfully. No student status column was found, so all students are currently treated as NEW / REGULAR.";
    }

} catch (mysqli_sql_exception $ex) {
    error_log("Student query error: " . $ex->getMessage());
    $error = "Unable to load students. Please check the students table structure.";
}

/* =====================================================
   LOAD SUBJECTS
===================================================== */

try {
    $result = $conn->query("
        SELECT id, subject_code, subject_name, units,
               year_level, semester, school_year
        FROM subjects
        ORDER BY year_level ASC, semester ASC, subject_code ASC
    ");

    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
} catch (mysqli_sql_exception $ex) {
    error_log("Subject query error: " . $ex->getMessage());
    $error = $error ?: "Unable to load subjects.";
}

/* =====================================================
   LOAD SECTIONS
===================================================== */

try {
    $result = $conn->query("
        SELECT id, section_name, school_year
        FROM sections
        ORDER BY school_year DESC, section_name ASC
    ");

    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
} catch (mysqli_sql_exception $ex) {
    error_log("Section query error: " . $ex->getMessage());
    $error = $error ?: "Unable to load sections.";
}

/* =====================================================
   COUNT BACK SUBJECTS PER STUDENT

   Uses students.id, which is the student_id used by
   the existing irregular_subjects.php assignment code.
===================================================== */

try {
    $result = $conn->query("
        SELECT student_id, COUNT(*) AS total
        FROM student_subjects
        WHERE UPPER(TRIM(COALESCE(enrollment_type, ''))) = 'BACK_SUBJECT'
        GROUP BY student_id
    ");

    while ($row = $result->fetch_assoc()) {
        $backSubjectCounts[(int)$row["student_id"]] = (int)$row["total"];
    }
} catch (mysqli_sql_exception $ex) {
    error_log("Back subject count error: " . $ex->getMessage());
    $error = $error ?: "Unable to count back subjects.";
}

/* =====================================================
   LOAD ENROLLMENTS

   LEFT JOIN sections ensures a back subject without
   a section is still displayed.
===================================================== */

try {
    $result = $conn->query("
        SELECT
            ss.id AS enrollment_id,
            ss.student_id,
            ss.subject_id,
            ss.section_id,
            ss.enrollment_type,
            st.student_id AS student_number,
            CONCAT(st.first_name, ' ', st.last_name) AS full_name,
            s.subject_code,
            s.subject_name,
            sec.section_name,
            sec.school_year
        FROM student_subjects ss
        INNER JOIN students st ON st.id = ss.student_id
        INNER JOIN subjects s ON s.id = ss.subject_id
        LEFT JOIN sections sec ON sec.id = ss.section_id
        ORDER BY
            sec.school_year DESC,
            sec.section_name ASC,
            st.last_name ASC,
            st.first_name ASC,
            s.subject_code ASC
    ");

    while ($row = $result->fetch_assoc()) {
        $enrollments[] = $row;
    }
} catch (mysqli_sql_exception $ex) {
    error_log("Enrollment list error: " . $ex->getMessage());
    $error = $error ?: "Unable to load enrollment records.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Enrollment | DCSA</title>

<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    padding: 25px;
    background: #f3f6fb;
    color: #333;
    font-family: Arial, Helvetica, sans-serif;
}

.container { width: 100%; max-width: 1350px; margin: auto; }

.header, .card {
    background: white;
    padding: 28px;
    margin-bottom: 25px;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,.07);
}

h1, h2 { color: #48265f; margin-top: 0; }
.header p { color: #666; line-height: 1.6; }

.back-button, .action-button {
    display: inline-block;
    padding: 10px 15px;
    border-radius: 8px;
    background: #48265f;
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.back-button { margin-bottom: 20px; }
.back-button:hover, .action-button:hover { background: #603b79; }

.info-box {
    margin-bottom: 25px;
    padding: 18px;
    background: #f7f2fa;
    border-left: 4px solid #48265f;
    border-radius: 7px;
    line-height: 1.7;
    color: #555;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}

label {
    display: block;
    margin-bottom: 9px;
    color: #48265f;
    font-weight: bold;
}

select {
    width: 100%;
    padding: 13px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background: white;
    font-size: 15px;
}

#subject_ids { min-height: 260px; }
#subject_ids option { padding: 6px; }

.help-text, .student-number {
    display: block;
    margin-top: 6px;
    color: #777;
    font-size: 12px;
}

button {
    margin-top: 25px;
    padding: 13px 24px;
    background: #48265f;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 15px;
    font-weight: bold;
}

button:hover { background: #603b79; }

.success, .error {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    line-height: 1.5;
}

.success { background: #e5f7eb; color: #17663b; }
.error { background: #fff0f0; color: #a32121; }

.table-wrapper { overflow-x: auto; }

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 950px;
}

thead { background: #48265f; color: white; }

th, td {
    padding: 14px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

tbody tr:hover { background: #f8f5fb; }

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
}

.badge-regular { background: #e7f1ff; color: #1756a9; }
.badge-old { background: #fff3cd; color: #856404; }
.badge-back { background: #fce8ff; color: #8b238f; }

.subject-code { font-weight: bold; color: #48265f; }

.action-button { font-size: 12px; padding: 8px 10px; }

.disabled {
    display: inline-block;
    background: #ddd;
    color: #555;
    padding: 8px 10px;
    border-radius: 7px;
    font-size: 12px;
}

.empty {
    padding: 20px;
    text-align: center;
    background: #faf9fc;
    color: #666;
    border-radius: 8px;
}

@media (max-width: 850px) {
    body { padding: 12px; }
    .form-grid { grid-template-columns: 1fr; }
    .header, .card { padding: 20px; }
}
</style>
</head>

<body>
<div class="container">

<a href="<?= $role === 'registrar'
    ? 'registrar_dashboard.php'
    : 'admin_dashboard.php' ?>"
   class="back-button">
    &larr; Back to Dashboard
</a>

<div class="header">
    <h1>Student Enrollment</h1>
    <p>
        Manage current subjects for new/regular and old/irregular students.
        Back subjects are managed separately from current subjects.
    </p>
</div>

<?php if ($message !== ""): ?>
    <div class="success"><?= e($message) ?></div>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <div class="error"><?= e($error) ?></div>
<?php endif; ?>

<!-- ENROLL CURRENT SUBJECTS -->
<div class="card">
    <h2>Enroll Student in Current Subjects</h2>

    <div class="info-box">
        <strong>Current subjects:</strong> These are saved as REGULAR for
        both regular and irregular students.<br><br>
        <strong>Back subjects:</strong> Use the Manage Back Subjects button
        in the student table below.
    </div>

    <?php if (empty($students) || empty($subjects) || empty($sections)): ?>
        <div class="error">
            Enrollment requires at least one student, subject, and section.
            <br>Students: <?= count($students) ?>
            <br>Subjects: <?= count($subjects) ?>
            <br>Sections: <?= count($sections) ?>
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="enroll_regular">

            <div class="form-grid">
                <div>
                    <label for="student_id">Select Student</label>
                    <select name="student_id" id="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= (int)$student["id"] ?>">
                                <?= e($student["full_name"]) ?>
                                — <?= e($student["student_id"]) ?>
                                — <?= $student["enrollment_type"] === "OLD"
                                    ? "IRREGULAR" : "REGULAR" ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="subject_ids">Select Subjects</label>
                    <select name="subject_ids[]" id="subject_ids" multiple required size="10">
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= (int)$subject["id"] ?>">
                                <?= e($subject["subject_code"]) ?> -
                                <?= e($subject["subject_name"]) ?>
                                <?php if (!empty($subject["year_level"])): ?>
                                    — Year <?= e($subject["year_level"]) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="help-text">
                        Hold CTRL and click to select multiple subjects.
                    </span>
                </div>

                <div>
                    <label for="section_id">Select Section</label>
                    <select name="section_id" id="section_id" required>
                        <option value="">-- Select Section --</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= (int)$section["id"] ?>">
                                <?= e($section["section_name"]) ?> -
                                <?= e($section["school_year"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit">Enroll Selected Subjects</button>
        </form>
    <?php endif; ?>
</div>

<!-- STUDENT SUBJECT MANAGEMENT -->
<div class="card">
    <h2>Student Subject Management</h2>
    <p>View each student's status and manage their back subjects.</p>
    <br>

    <?php if (empty($students)): ?>
        <div class="empty">No students found.</div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student Number</th>
                        <th>Student Status</th>
                        <th>Back Subjects</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $student): ?>
                    <?php
                    $isIrregular = ($student["enrollment_type"] === "OLD");
                    $backCount = $backSubjectCounts[(int)$student["id"]] ?? 0;
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($student["full_name"]) ?></strong>
                            <?php if (!empty($student["email"])): ?>
                                <span class="student-number"><?= e($student["email"]) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($student["student_id"]) ?></td>
                        <td>
                            <?php if ($isIrregular): ?>
                                <span class="badge badge-old">OLD / IRREGULAR</span>
                            <?php else: ?>
                                <span class="badge badge-regular">NEW / REGULAR</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($backCount > 0): ?>
                                <span class="badge badge-back">
                                    <?= $backCount ?>
                                    Back Subject<?= $backCount === 1 ? "" : "s" ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#777;">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isIrregular): ?>
                                <a class="action-button"
                                   href="irregular_subjects.php?student_id=<?= (int)$student["id"] ?>">
                                    Manage Back Subjects
                                </a>
                            <?php else: ?>
                                <span class="disabled">New / Regular Student</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ENROLLED SUBJECTS -->
<div class="card">
    <h2>Enrolled Subjects</h2>

    <?php if (empty($enrollments)): ?>
        <div class="empty">No enrollment records found yet.</div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Subject Code</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Section</th>
                        <th>School Year</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($enrollments as $enrollment): ?>
                    <?php
                    $type = strtoupper(trim($enrollment["enrollment_type"] ?? "REGULAR"));
                    $isBack = ($type === "BACK_SUBJECT");
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($enrollment["full_name"]) ?></strong>
                            <span class="student-number">
                                Student No: <?= e($enrollment["student_number"]) ?>
                            </span>
                        </td>
                        <td><span class="subject-code"><?= e($enrollment["subject_code"]) ?></span></td>
                        <td><?= e($enrollment["subject_name"]) ?></td>
                        <td>
                            <?php if ($isBack): ?>
                                <span class="badge badge-back">BACK_SUBJECT</span>
                            <?php else: ?>
                                <span class="badge badge-regular">REGULAR</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($enrollment["section_name"] ?? "Not assigned") ?></td>
                        <td><?= e($enrollment["school_year"] ?? "—") ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</div>
</body>
</html>
