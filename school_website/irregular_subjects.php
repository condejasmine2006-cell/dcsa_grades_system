
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.html");
    exit();
}

require_once "db_connect.php";

$message = "";
$error = "";

/* ==========================================
   GET STUDENT ID
========================================== */

$student_id = filter_input(
    INPUT_GET,
    "student_id",
    FILTER_VALIDATE_INT
);

if (!$student_id || $student_id <= 0) {
    die("Invalid student ID.");
}

/* ==========================================
   GET STUDENT INFORMATION
========================================== */

$studentColumns = [];
$columnsResult = $conn->query("SHOW COLUMNS FROM students");

while ($column = $columnsResult->fetch_assoc()) {
    $studentColumns[] = $column["Field"];
}

if (in_array("student_status", $studentColumns, true)) {
    $statusColumn = "student_status";
} elseif (in_array("status", $studentColumns, true)) {
    $statusColumn = "status";
} else {
    $statusColumn = null;
}

if ($statusColumn !== null) {
    $sql = "
        SELECT
            id,
            student_id AS student_number,
            first_name,
            last_name,
            `$statusColumn` AS student_status
        FROM students
        WHERE id = ?
        LIMIT 1
    ";
} else {
    $sql = "
        SELECT
            id,
            student_id AS student_number,
            first_name,
            last_name,
            'IRREGULAR' AS student_status
        FROM students
        WHERE id = ?
        LIMIT 1
    ";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found.");
}

/* ==========================================
   ADD BACK SUBJECT
========================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    /* --------------------------------------
       ADD BACK SUBJECT
    -------------------------------------- */

    if ($action === "add_back_subject") {

        $subject_id = filter_input(
            INPUT_POST,
            "subject_id",
            FILTER_VALIDATE_INT
        );

        if (!$subject_id || $subject_id <= 0) {

            $error = "Please select a valid subject.";

        } else {

            // Verify that the selected subject exists.
            $check_subject = $conn->prepare("
                SELECT id
                FROM subjects
                WHERE id = ?
                LIMIT 1
            ");

            $check_subject->bind_param("i", $subject_id);
            $check_subject->execute();

            $valid_subject = $check_subject
                ->get_result()
                ->fetch_assoc();

            $check_subject->close();

            if (!$valid_subject) {

                $error = "The selected subject does not exist.";

            } else {

                /*
                 * Check only for an existing BACK_SUBJECT.
                 * A REGULAR record should not automatically
                 * prevent the admin from recording a retake.
                 */

                $check = $conn->prepare("
                    SELECT id
                    FROM student_subjects
                    WHERE student_id = ?
                      AND subject_id = ?
                      AND enrollment_type = 'BACK_SUBJECT'
                    LIMIT 1
                ");

                $check->bind_param(
                    "ii",
                    $student_id,
                    $subject_id
                );

                $check->execute();

                $existing_back_subject = $check
                    ->get_result()
                    ->fetch_assoc();

                $check->close();

                if ($existing_back_subject) {

                    $error = "This back subject is already assigned.";

                } else {

                    /*
                     * Insert the back subject.
                     * section_id is omitted because the existing
                     * schema permits it to be NULL.
                     */

                    $insert = $conn->prepare("
                        INSERT INTO student_subjects
                            (student_id, subject_id, enrollment_type)
                        VALUES
                            (?, ?, 'BACK_SUBJECT')
                    ");

                    $insert->bind_param(
                        "ii",
                        $student_id,
                        $subject_id
                    );

                    if ($insert->execute()) {
                        $message = "Back subject successfully added.";
                    } else {
                        $error = "Unable to add back subject. "
                               . "Database error: "
                               . $insert->error;
                    }

                    $insert->close();
                }
            }
        }
    }

    /* --------------------------------------
       REMOVE BACK SUBJECT
    -------------------------------------- */

    elseif ($action === "remove_back_subject") {

        $remove_id = filter_input(
            INPUT_POST,
            "back_subject_id",
            FILTER_VALIDATE_INT
        );

        if (!$remove_id || $remove_id <= 0) {

            $error = "Invalid back subject record.";

        } else {

            /*
             * Delete only the selected BACK_SUBJECT record
             * belonging to this student.
             */

            $delete = $conn->prepare("
                DELETE FROM student_subjects
                WHERE id = ?
                  AND student_id = ?
                  AND enrollment_type = 'BACK_SUBJECT'
            ");

            $delete->bind_param(
                "ii",
                $remove_id,
                $student_id
            );

            if ($delete->execute()) {

                if ($delete->affected_rows > 0) {
                    $message = "Back subject successfully removed.";
                } else {
                    $error = "Back subject not found or already removed.";
                }

            } else {
                $error = "Unable to remove the back subject.";
            }

            $delete->close();
        }
    }

    else {
        $error = "Invalid action.";
    }
}

/* ==========================================
   GET AVAILABLE SUBJECTS
========================================== */

/*
 * Exclude subjects that are already assigned as BACK_SUBJECT.
 * A subject may still appear here if it has only a REGULAR record.
 */

$available_subjects = $conn->prepare("
    SELECT
        s.id,
        s.subject_code,
        s.subject_name
    FROM subjects s
    WHERE NOT EXISTS (
        SELECT 1
        FROM student_subjects ss
        WHERE ss.student_id = ?
          AND ss.subject_id = s.id
          AND ss.enrollment_type = 'BACK_SUBJECT'
    )
    ORDER BY s.subject_code ASC
");

$available_subjects->bind_param("i", $student_id);
$available_subjects->execute();

$subjects_result = $available_subjects->get_result();

/* ==========================================
   GET ASSIGNED BACK SUBJECTS
========================================== */

$stmt = $conn->prepare("
    SELECT
        ss.id AS back_subject_id,
        s.subject_code,
        s.subject_name
    FROM student_subjects ss
    INNER JOIN subjects s
        ON s.id = ss.subject_id
    WHERE ss.student_id = ?
      AND ss.enrollment_type = 'BACK_SUBJECT'
    ORDER BY s.subject_code ASC
");

$stmt->bind_param("i", $student_id);
$stmt->execute();

$back_subjects = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Back Subjects</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            color: #333;
        }

        .container {
            width: 95%;
            max-width: 1100px;
            margin: 35px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #205c4a;
            text-decoration: none;
            font-weight: bold;
        }

        .back:hover {
            text-decoration: underline;
        }

        .header {
            background: #205c4a;
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .header h1 {
            margin: 0 0 12px;
        }

        .header p {
            margin: 7px 0;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.07);
        }

        .card h2 {
            margin-top: 0;
            color: #205c4a;
        }

        .description {
            color: #666;
            line-height: 1.6;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 15px;
            background: white;
        }

        button {
            border: none;
            padding: 11px 17px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 14px;
        }

        .add-button {
            background: #205c4a;
            color: white;
        }

        .add-button:hover {
            background: #327861;
        }

        .remove-button {
            background: #b42318;
            color: white;
        }

        .remove-button:hover {
            background: #8f1b12;
        }

        .message,
        .error {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .message {
            background: #dff5e9;
            color: #17663d;
        }

        .error {
            background: #ffe0e0;
            color: #a00000;
            overflow-wrap: anywhere;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 520px;
        }

        th,
        td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #205c4a;
            color: white;
        }

        tbody tr:hover {
            background: #f8fbf9;
        }

        .badge {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #777;
        }

        .count {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        @media (max-width: 600px) {
            .container {
                width: 92%;
                margin: 20px auto;
            }

            .header,
            .card {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    
<a href="admin_students.php" class="back">
    &larr; Back to Students
</a>

    <div class="header">
        <h1>Manage Back Subjects</h1>

        <p>
            <strong>Student:</strong>
            <?= htmlspecialchars(
                $student["first_name"] . " " . $student["last_name"]
            ) ?>
        </p>

        <p>
            <strong>Student Number:</strong>
            <?= htmlspecialchars($student["student_number"]) ?>
        </p>

        <p>
            <strong>Status:</strong>
            <?= htmlspecialchars(
                $student["student_status"] ?? "Irregular"
            ) ?>
        </p>
    </div>

    <?php if ($message !== ""): ?>
        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- ADD BACK SUBJECT -->

    <div class="card">
        <h2>Add Back Subject</h2>

        <p class="description">
            Select a subject that the student needs to retake.
            A subject already assigned as a back subject will not
            appear in the available list.
        </p>

        <form method="POST"
              action="irregular_subjects.php?student_id=<?= (int) $student_id ?>">

            <input type="hidden"
                   name="action"
                   value="add_back_subject">

            <label for="subject_id">Select Subject</label>

            <select name="subject_id"
                    id="subject_id"
                    required>

                <option value="">-- Select Subject --</option>

                <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                    <option value="<?= (int) $subject["id"] ?>">
                        <?= htmlspecialchars($subject["subject_code"]) ?>
                        -
                        <?= htmlspecialchars($subject["subject_name"]) ?>
                    </option>
                <?php endwhile; ?>

            </select>

            <button type="submit" class="add-button">
                + Add Back Subject
            </button>
        </form>
    </div>

    <!-- ASSIGNED BACK SUBJECTS -->

    <div class="card">
        <h2>Assigned Back Subjects</h2>

        <p class="count">
            Total assigned:
            <strong><?= (int) $back_subjects->num_rows ?></strong>
        </p>

        <?php if ($back_subjects->num_rows > 0): ?>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $back_subjects->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($row["subject_code"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row["subject_name"]) ?>
                                </td>

                                <td>
                                    <span class="badge">BACK SUBJECT</span>
                                </td>

                                <td>
                                    <form method="POST"
                                          action="irregular_subjects.php?student_id=<?= (int) $student_id ?>"
                                          onsubmit="return confirm('Remove this back subject assignment?');">

                                        <input type="hidden"
                                               name="action"
                                               value="remove_back_subject">

                                        <input type="hidden"
                                               name="back_subject_id"
                                               value="<?= (int) $row["back_subject_id"] ?>">

                                        <button type="submit"
                                                class="remove-button">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <div class="empty">
                No back subjects have been assigned to this student yet.
            </div>

        <?php endif; ?>
    </div>

</div>

</body>
</html>

<?php
$stmt->close();
$available_subjects->close();
$conn->close();
?>
