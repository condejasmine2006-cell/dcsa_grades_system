<?php
session_start();

require_once __DIR__ . "/db_connect.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =========================================================
   ADMIN ACCESS
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$role = strtolower(trim(
    $_SESSION["role"] ??
    $_SESSION["user_role"] ??
    ""
));

if (!in_array($role, ["admin", "administrator"], true)) {
    http_response_code(403);
    exit("Access denied. Administrators only.");
}

/* =========================================================
   VARIABLES
========================================================= */

$message = "";
$error = "";

$teachers = [];
$subjects = [];
$sections = [];
$assignments = [];

/* =========================================================
   ESCAPE OUTPUT
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

/* =========================================================
   CSRF TOKEN
========================================================= */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

/* =========================================================
   SAVE MULTIPLE SUBJECT ASSIGNMENTS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        !is_string($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)
    ) {

        $error = "Invalid request. Please refresh the page.";

    } else {

        $teacherId = filter_input(
            INPUT_POST,
            "teacher_id",
            FILTER_VALIDATE_INT
        );

        $sectionId = filter_input(
            INPUT_POST,
            "section_id",
            FILTER_VALIDATE_INT
        );

        /*
         * Multiple subjects are submitted as:
         *
         * subject_ids[]
         */
        $subjectIds = $_POST["subject_ids"] ?? [];

        if (!is_array($subjectIds)) {
            $subjectIds = [];
        }

        /* Clean subject IDs */
        $subjectIds = array_map(
            "intval",
            $subjectIds
        );

        /* Remove invalid / duplicate IDs */
        $subjectIds = array_values(
            array_unique(
                array_filter(
                    $subjectIds,
                    function ($id) {
                        return $id > 0;
                    }
                )
            )
        );

        if (!$teacherId) {

            $error = "Please select a teacher.";

        } elseif (!$sectionId) {

            $error = "Please select a section.";

        } elseif (empty($subjectIds)) {

            $error = "Please select at least one subject.";

        } else {

            try {

                /* =====================================================
                   VERIFY TEACHER
                ===================================================== */

                $stmt = $conn->prepare("
                    SELECT id
                    FROM users
                    WHERE id = ?
                      AND LOWER(role) = 'teacher'
                    LIMIT 1
                ");

                $stmt->bind_param(
                    "i",
                    $teacherId
                );

                $stmt->execute();

                $teacherResult = $stmt->get_result();

                if ($teacherResult->num_rows === 0) {

                    $stmt->close();

                    throw new Exception(
                        "The selected teacher was not found."
                    );
                }

                $stmt->close();

                /* =====================================================
                   VERIFY SECTION
                ===================================================== */

                $stmt = $conn->prepare("
                    SELECT id
                    FROM sections
                    WHERE id = ?
                    LIMIT 1
                ");

                $stmt->bind_param(
                    "i",
                    $sectionId
                );

                $stmt->execute();

                $sectionResult = $stmt->get_result();

                if ($sectionResult->num_rows === 0) {

                    $stmt->close();

                    throw new Exception(
                        "The selected section was not found."
                    );
                }

                $stmt->close();

                /* =====================================================
                   BEGIN TRANSACTION
                ===================================================== */

                $conn->begin_transaction();

                $assignedCount = 0;
                $duplicateCount = 0;

                /* =====================================================
                   PREPARE DUPLICATE CHECK
                ===================================================== */

                $checkStmt = $conn->prepare("
                    SELECT id
                    FROM teacher_subjects
                    WHERE teacher_id = ?
                      AND subject_id = ?
                      AND section_id = ?
                    LIMIT 1
                ");

                /* =====================================================
                   PREPARE INSERT
                ===================================================== */

                $insertStmt = $conn->prepare("
                    INSERT INTO teacher_subjects
                        (
                            teacher_id,
                            subject_id,
                            section_id
                        )
                    VALUES
                        (?, ?, ?)
                ");

                /* =====================================================
                   PROCESS EVERY SELECTED SUBJECT
                ===================================================== */

                foreach ($subjectIds as $subjectId) {

                    /* ---------------------------------------------
                       Verify subject
                    --------------------------------------------- */

                    $subjectCheck = $conn->prepare("
                        SELECT id
                        FROM subjects
                        WHERE id = ?
                        LIMIT 1
                    ");

                    $subjectCheck->bind_param(
                        "i",
                        $subjectId
                    );

                    $subjectCheck->execute();

                    $subjectResult =
                        $subjectCheck->get_result();

                    if ($subjectResult->num_rows === 0) {

                        $subjectCheck->close();

                        continue;
                    }

                    $subjectCheck->close();

                    /* ---------------------------------------------
                       Check duplicate
                    --------------------------------------------- */

                    $checkStmt->bind_param(
                        "iii",
                        $teacherId,
                        $subjectId,
                        $sectionId
                    );

                    $checkStmt->execute();

                    $duplicateResult =
                        $checkStmt->get_result();

                    if ($duplicateResult->num_rows > 0) {

                        $duplicateCount++;

                        continue;
                    }

                    /* ---------------------------------------------
                       Insert assignment
                    --------------------------------------------- */

                    $insertStmt->bind_param(
                        "iii",
                        $teacherId,
                        $subjectId,
                        $sectionId
                    );

                    $insertStmt->execute();

                    $assignedCount++;
                }

                $checkStmt->close();
                $insertStmt->close();

                $conn->commit();

                /* =====================================================
                   RESULT MESSAGE
                ===================================================== */

                if ($assignedCount > 0 && $duplicateCount > 0) {

                    $message =
                        $assignedCount .
                        " subject(s) assigned successfully. " .
                        $duplicateCount .
                        " duplicate assignment(s) were skipped.";

                } elseif ($assignedCount > 0) {

                    $message =
                        $assignedCount .
                        " subject(s) assigned successfully!";

                } elseif ($duplicateCount > 0) {

                    $error =
                        "All selected subjects are already assigned " .
                        "to this teacher for this section.";

                } else {

                    $error =
                        "No new subject assignments were created.";
                }

            } catch (Exception $ex) {

                if ($conn->errno === 0) {
                    // Nothing needed here.
                }

                try {
                    $conn->rollback();
                } catch (Throwable $rollbackError) {
                    // Ignore rollback errors.
                }

                error_log(
                    "Teacher assignment error: " .
                    $ex->getMessage()
                );

                $error = $ex->getMessage();

            } catch (mysqli_sql_exception $ex) {

                try {
                    $conn->rollback();
                } catch (Throwable $rollbackError) {
                    // Ignore rollback errors.
                }

                error_log(
                    "Teacher assignment SQL error: " .
                    $ex->getMessage()
                );

                $error =
                    "Unable to save the assignments. " .
                    $ex->getMessage();
            }
        }
    }
}

/* =========================================================
   LOAD TEACHERS
========================================================= */

try {

    $result = $conn->query("
        SELECT
            id,
            full_name
        FROM users
        WHERE LOWER(role) = 'teacher'
        ORDER BY full_name ASC
    ");

    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }

} catch (mysqli_sql_exception $ex) {

    error_log(
        "Teacher query error: " .
        $ex->getMessage()
    );

    $error = "Unable to load teachers.";
}

/* =========================================================
   LOAD SUBJECTS
========================================================= */

try {

    $result = $conn->query("
        SELECT
            id,
            subject_code,
            subject_name
        FROM subjects
        ORDER BY
            subject_code ASC
    ");

    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }

} catch (mysqli_sql_exception $ex) {

    error_log(
        "Subject query error: " .
        $ex->getMessage()
    );

    $error = "Unable to load subjects.";
}

/* =========================================================
   LOAD SECTIONS
========================================================= */

try {

    $result = $conn->query("
        SELECT
            id,
            section_name,
            school_year
        FROM sections
        ORDER BY
            school_year DESC,
            section_name ASC
    ");

    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }

} catch (mysqli_sql_exception $ex) {

    error_log(
        "Section query error: " .
        $ex->getMessage()
    );

    $error = "Unable to load sections.";
}

/* =========================================================
   LOAD EXISTING ASSIGNMENTS
========================================================= */

try {

    $sql = "
        SELECT
            ts.id AS assignment_id,

            t.full_name AS teacher_name,

            s.subject_code,
            s.subject_name,

            sec.section_name,
            sec.school_year

        FROM teacher_subjects ts

        INNER JOIN users t
            ON t.id = ts.teacher_id

        INNER JOIN subjects s
            ON s.id = ts.subject_id

        INNER JOIN sections sec
            ON sec.id = ts.section_id

        ORDER BY
            t.full_name ASC,
            sec.school_year DESC,
            sec.section_name ASC,
            s.subject_code ASC
    ";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }

} catch (mysqli_sql_exception $ex) {

    error_log(
        "Assignment list error: " .
        $ex->getMessage()
    );

    $error = "Unable to load existing assignments.";
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Assign Teachers | DCSA Admin</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 25px;

    font-family: Arial, sans-serif;

    background: #f3f6fb;

    color: #333;
}

.container {
    max-width: 1250px;
    margin: auto;
}

/* =========================================================
   BUTTONS
========================================================= */

.back-button {
    display: inline-block;

    margin-bottom: 20px;

    padding: 11px 18px;

    background: #48265f;

    color: white;

    text-decoration: none;

    border-radius: 7px;
}

.back-button:hover {
    background: #603b79;
}

/* =========================================================
   CARDS
========================================================= */

.header,
.card {
    background: white;

    padding: 28px;

    margin-bottom: 25px;

    border-radius: 14px;

    box-shadow:
        0 4px 15px rgba(0,0,0,0.07);
}

h1,
h2 {
    color: #48265f;

    margin-top: 0;
}

.header p {
    color: #666;

    margin-bottom: 0;
}

/* =========================================================
   ALERTS
========================================================= */

.success {
    padding: 15px;

    background: #e5f7eb;

    color: #17663b;

    border: 1px solid #b9e7cb;

    border-radius: 7px;

    margin-bottom: 20px;
}

.error {
    padding: 15px;

    background: #fff0f0;

    color: #a32121;

    border: 1px solid #f2c4c4;

    border-radius: 7px;

    margin-bottom: 20px;
}

/* =========================================================
   FORM
========================================================= */

.form-row {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 20px;

    margin-bottom: 25px;
}

label {
    display: block;

    margin-bottom: 9px;

    font-weight: bold;

    color: #48265f;
}

select {
    width: 100%;

    padding: 13px;

    border: 1px solid #ccc;

    border-radius: 7px;

    background: white;

    font-size: 15px;
}

/* =========================================================
   SUBJECT LIST
========================================================= */

.subject-title {
    color: #48265f;

    font-weight: bold;

    margin-bottom: 12px;
}

.subject-actions {
    display: flex;

    gap: 10px;

    margin-bottom: 15px;

    flex-wrap: wrap;
}

.small-button {
    margin: 0;

    padding: 8px 13px;

    border: none;

    border-radius: 6px;

    background: #eee;

    color: #48265f;

    cursor: pointer;

    font-size: 13px;
}

.small-button:hover {
    background: #ddd;
}

.subject-list {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 10px;

    max-height: 420px;

    overflow-y: auto;

    padding: 15px;

    border: 1px solid #ddd;

    border-radius: 8px;

    background: #fafafa;
}

.subject-item {
    display: flex;

    align-items: center;

    gap: 10px;

    padding: 12px;

    background: white;

    border: 1px solid #e1e1e1;

    border-radius: 7px;

    cursor: pointer;
}

.subject-item:hover {
    background: #f5eff8;

    border-color: #48265f;
}

.subject-item input {
    width: 18px;

    height: 18px;

    cursor: pointer;
}

.subject-code {
    font-weight: bold;

    color: #48265f;
}

.subject-name {
    color: #444;
}

/* =========================================================
   ASSIGN BUTTON
========================================================= */

.assign-button {
    margin-top: 25px;

    padding: 13px 22px;

    background: #48265f;

    color: white;

    border: none;

    border-radius: 7px;

    cursor: pointer;

    font-size: 15px;
}

.assign-button:hover {
    background: #603b79;
}

/* =========================================================
   TABLE
========================================================= */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;

    min-width: 800px;
}

thead {
    background: #48265f;

    color: white;
}

th,
td {
    padding: 15px;

    text-align: left;

    border-bottom: 1px solid #ddd;
}

tbody tr:hover {
    background: #f8f5fb;
}

.empty {
    padding: 20px;

    text-align: center;

    background: #faf9fc;

    border-radius: 7px;

    color: #666;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 750px) {

    body {
        padding: 12px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .subject-list {
        grid-template-columns: 1fr;
    }

    .header,
    .card {
        padding: 20px;
    }
}

</style>

</head>

<body>

<div class="container">

<!-- =====================================================
     BACK BUTTON
===================================================== -->

<a
    href="admin_dashboard.php"
    class="back-button"
>
    &larr; Back to Admin Dashboard
</a>

<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

    <h1>
        Admin Dashboard — Teacher Assignments
    </h1>

    <p>
        Assign multiple subjects to a teacher
        for a specific class section.
    </p>

</div>

<!-- =====================================================
     ASSIGNMENT FORM
===================================================== -->

<div class="card">

    <h2>
        Assign Subjects to Teacher
    </h2>

    <?php if ($message !== ""): ?>

        <div class="success">
            <?= e($message) ?>
        </div>

    <?php endif; ?>

    <?php if ($error !== ""): ?>

        <div class="error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <?php if (
        empty($teachers) ||
        empty($subjects) ||
        empty($sections)
    ): ?>

        <div class="error">

            You need at least one teacher,
            subject, and section.

            <br><br>

            Teachers:
            <?= count($teachers) ?>

            <br>

            Subjects:
            <?= count($subjects) ?>

            <br>

            Sections:
            <?= count($sections) ?>

        </div>

    <?php else: ?>

        <form
            method="POST"
            action=""
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrfToken) ?>"
            >

            <!-- =================================================
                 TEACHER + SECTION
            ================================================== -->

            <div class="form-row">

                <div>

                    <label for="teacher_id">
                        Select Teacher
                    </label>

                    <select
                        name="teacher_id"
                        id="teacher_id"
                        required
                    >

                        <option value="">
                            -- Select Teacher --
                        </option>

                        <?php foreach (
                            $teachers
                            as $teacher
                        ): ?>

                            <option
                                value="<?= (int)$teacher["id"] ?>"
                            >

                                <?= e(
                                    $teacher["full_name"]
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div>

                    <label for="section_id">
                        Select Section
                    </label>

                    <select
                        name="section_id"
                        id="section_id"
                        required
                    >

                        <option value="">
                            -- Select Section --
                        </option>

                        <?php foreach (
                            $sections
                            as $section
                        ): ?>

                            <option
                                value="<?= (int)$section["id"] ?>"
                            >

                                <?= e(
                                    $section["section_name"]
                                ) ?>

                                -
                                <?= e(
                                    $section["school_year"]
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>

            <!-- =================================================
                 SUBJECTS
            ================================================== -->

            <div class="subject-title">
                Select Subjects
            </div>

            <div class="subject-actions">

                <button
                    type="button"
                    class="small-button"
                    onclick="selectAllSubjects()"
                >
                    Select All
                </button>

                <button
                    type="button"
                    class="small-button"
                    onclick="clearAllSubjects()"
                >
                    Clear All
                </button>

            </div>

            <div class="subject-list">

                <?php foreach (
                    $subjects
                    as $subject
                ): ?>

                    <label class="subject-item">

                        <input
                            type="checkbox"
                            name="subject_ids[]"
                            value="<?= (int)$subject["id"] ?>"
                            class="subject-checkbox"
                        >

                        <span>

                            <span class="subject-code">
                                <?= e(
                                    $subject["subject_code"]
                                ) ?>
                            </span>

                            -

                            <span class="subject-name">
                                <?= e(
                                    $subject["subject_name"]
                                ) ?>
                            </span>

                        </span>

                    </label>

                <?php endforeach; ?>

            </div>

            <!-- =================================================
                 SUBMIT
            ================================================== -->

            <button
                type="submit"
                class="assign-button"
                onclick="return validateSubjects()"
            >
                Assign Selected Subjects
            </button>

        </form>

    <?php endif; ?>

</div>

<!-- =====================================================
     EXISTING ASSIGNMENTS
===================================================== -->

<div class="card">

    <h2>
        Existing Teacher Assignments
    </h2>

    <?php if (empty($assignments)): ?>

        <div class="empty">

            No teacher assignments found yet.

            <br><br>

            Select a teacher, section,
            and subjects above.

        </div>

    <?php else: ?>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Teacher
                        </th>

                        <th>
                            Subject Code
                        </th>

                        <th>
                            Subject Name
                        </th>

                        <th>
                            Section
                        </th>

                        <th>
                            School Year
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach (
                        $assignments
                        as $assignment
                    ): ?>

                        <tr>

                            <td>
                                <?= e(
                                    $assignment["teacher_name"]
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $assignment["subject_code"]
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $assignment["subject_name"]
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $assignment["section_name"]
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $assignment["school_year"]
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

</div>

<script>

/* =========================================================
   SELECT ALL SUBJECTS
========================================================= */

function selectAllSubjects()
{
    const checkboxes =
        document.querySelectorAll(
            ".subject-checkbox"
        );

    checkboxes.forEach(function (checkbox) {

        checkbox.checked = true;

    });
}

/* =========================================================
   CLEAR ALL SUBJECTS
========================================================= */

function clearAllSubjects()
{
    const checkboxes =
        document.querySelectorAll(
            ".subject-checkbox"
        );

    checkboxes.forEach(function (checkbox) {

        checkbox.checked = false;

    });
}

/* =========================================================
   VALIDATE SUBJECT SELECTION
========================================================= */

function validateSubjects()
{
    const teacher =
        document.getElementById(
            "teacher_id"
        ).value;

    const section =
        document.getElementById(
            "section_id"
        ).value;

    const selectedSubjects =
        document.querySelectorAll(
            ".subject-checkbox:checked"
        );

    if (!teacher) {

        alert(
            "Please select a teacher."
        );

        return false;
    }

    if (!section) {

        alert(
            "Please select a section."
        );

        return false;
    }

    if (selectedSubjects.length === 0) {

        alert(
            "Please select at least one subject."
        );

        return false;
    }

    return true;
}

</script>

</body>

</html>