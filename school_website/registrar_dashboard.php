
<?php
session_start();
require_once __DIR__ . "/db_connect.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =========================================
   REGISTRAR ACCESS
========================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$role = strtolower(trim(
    $_SESSION["role"] ?? $_SESSION["user_role"] ?? ""
));

if ($role !== "registrar") {
    http_response_code(403);
    exit("Access denied. Registrar account required.");
}

/* =========================================
   CSRF PROTECTION
========================================= */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

/* =========================================
   VERIFY OR RETURN GRADES
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $gradeId = filter_input(
        INPUT_POST,
        "grade_id",
        FILTER_VALIDATE_INT
    );
    $action = $_POST["action"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {
        $error = "Invalid request. Please refresh the page.";
    } elseif (!$gradeId || !in_array(
        $action,
        ["verify", "return"],
        true
    )) {
        $error = "Invalid grade review request.";
    } else {
        $newStatus = $action === "verify"
            ? "Verified"
            : "Needs Correction";

        $stmt = $conn->prepare(
            "UPDATE grades
             SET registrar_status = ?
             WHERE id = ?"
        );

        $stmt->bind_param("si", $newStatus, $gradeId);
        $stmt->execute();

        if ($stmt->affected_rows >= 0) {
            $message = "Grade review status updated to "
                     . $newStatus . ".";
        }

        $stmt->close();
    }
}

/* =========================================
   SEARCH
========================================= */

$search = trim($_GET["search"] ?? "");
$searchLike = "%" . $search . "%";

/* =========================================
   MAIN ACADEMIC RECORD QUERY
========================================= */

$sql = "
    SELECT
        g.id AS grade_id,
        s.student_id AS student_number,
        s.first_name,
        s.last_name,
        sub.subject_code,
        sub.subject_name,
        sub.school_year,
        sec.section_name,
        g.prelim,
        g.midterm,
        g.pre_final,
        g.final_grade,
        g.average,
        g.remarks,
        COALESCE(g.registrar_status, 'Pending')
            AS registrar_status
    FROM grades g
    INNER JOIN student_subjects ss
        ON ss.id = g.student_subject_id
    INNER JOIN students s
        ON s.id = ss.student_id
    INNER JOIN subjects sub
        ON sub.id = ss.subject_id
    LEFT JOIN sections sec
        ON sec.id = ss.section_id
    WHERE (
        s.student_id LIKE ?
        OR s.first_name LIKE ?
        OR s.last_name LIKE ?
        OR sub.subject_code LIKE ?
        OR sub.subject_name LIKE ?
    )
    ORDER BY s.last_name, s.first_name, sub.subject_code
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssss",
    $searchLike,
    $searchLike,
    $searchLike,
    $searchLike,
    $searchLike
);
$stmt->execute();
$result = $stmt->get_result();

$records = [];

while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}

$stmt->close();

/* =========================================
   EXPORT ACADEMIC REPORT TO CSV
========================================= */

if (isset($_GET["export"]) && $_GET["export"] === "csv") {
    header("Content-Type: text/csv; charset=UTF-8");
    header(
        'Content-Disposition: attachment; filename="academic_report.csv"'
    );

    $output = fopen("php://output", "w");

    // UTF-8 BOM for Excel compatibility
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        "Student Number",
        "Student Name",
        "Subject Code",
        "Subject Name",
        "Section",
        "School Year",
        "Prelim",
        "Midterm",
        "Pre-Final",
        "Final Grade",
        "GWA / Grade Result",
        "Remarks",
        "Registrar Status"
    ]);

    foreach ($records as $r) {
        fputcsv($output, [
            $r["student_number"],
            $r["first_name"] . " " . $r["last_name"],
            $r["subject_code"],
            $r["subject_name"],
            $r["section_name"] ?? "",
            $r["school_year"],
            $r["prelim"],
            $r["midterm"],
            $r["pre_final"],
            $r["final_grade"],
            $r["average"],
            $r["remarks"],
            $r["registrar_status"]
        ]);
    }

    fclose($output);
    exit;
}

/* =========================================
   SUMMARY COUNTS
========================================= */

$totalRecords = count($records);
$pending = 0;
$verified = 0;
$correction = 0;

foreach ($records as $r) {
    if ($r["registrar_status"] === "Verified") {
        $verified++;
    } elseif ($r["registrar_status"] === "Needs Correction") {
        $correction++;
    } else {
        $pending++;
    }
}

function h($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Registrar Dashboard | DATAMEX COLLEGE OF SAINT ADELINE</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f2f7;
    color: #292333;
}

header {
    background: #48265f;
    color: white;
    padding: 24px;
}

header h1 {
    margin: 0 0 8px;
    font-size: 23px;
}

header p {
    margin: 0;
    color: #eee4f5;
}

.container {
    max-width: 1500px;
    margin: auto;
    padding: 24px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 25px;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px #0000000d;
}

.card h3 {
    font-size: 14px;
    color: #6d6475;
    margin: 0 0 12px;
}

.card strong {
    font-size: 28px;
    color: #48265f;
}

.panel {
    background: white;
    padding: 22px;
    border-radius: 10px;
    margin-bottom: 22px;
    box-shadow: 0 2px 8px #0000000d;
}

.panel h2 {
    margin-top: 0;
    font-size: 20px;
}

.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

input[type="text"] {
    padding: 11px;
    border: 1px solid #d8d1df;
    border-radius: 6px;
    min-width: 220px;
    flex: 1;
}

button, .btn {
    display: inline-block;
    border: none;
    padding: 10px 13px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    text-decoration: none;
}

.search-btn, .verify-btn {
    background: #48265f;
    color: white;
}

.return-btn {
    background: #fff0dc;
    color: #8a4a00;
}

.export-btn {
    background: #e5f4e8;
    color: #216638;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1150px;
}

th, td {
    padding: 12px 10px;
    border-bottom: 1px solid #eee9f2;
    text-align: left;
    font-size: 13px;
    vertical-align: middle;
}

th {
    background: #f5f0f8;
    color: #48265f;
}

.table-wrap {
    overflow-x: auto;
}

.status {
    display: inline-block;
    border-radius: 20px;
    padding: 5px 9px;
    font-size: 12px;
    font-weight: bold;
}

.Pending {
    background: #fff0c9;
    color: #805600;
}

.Verified {
    background: #dff5e5;
    color: #176334;
}

.Correction {
    background: #ffe2e2;
    color: #a12222;
}

.notice {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    background: #e5f4e8;
    color: #216638;
}

.error {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    background: #ffe2e2;
    color: #a12222;
}

.actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.empty {
    padding: 25px;
    text-align: center;
    color: #756b7d;
}

footer {
    text-align: center;
    color: #756b7d;
    padding: 20px;
    font-size: 12px;
}

@media (max-width: 800px) {
    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .container {
        padding: 12px;
    }
}
</style>
</head>

<body>

<header>
    <h1>Registrar Dashboard</h1>
    <p>DATAMEX COLLEGE OF SAINT ADELINE</p>
</header>

<main class="container">

    <?php if ($message !== ""): ?>
        <div class="notice"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <!-- SUMMARY -->

    <section class="cards">
        <div class="card">
            <h3>Academic Records</h3>
            <strong><?= $totalRecords ?></strong>
        </div>

        <div class="card">
            <h3>Pending Review</h3>
            <strong><?= $pending ?></strong>
        </div>

        <div class="card">
            <h3>Verified Grades</h3>
            <strong><?= $verified ?></strong>
        </div>

        <div class="card">
            <h3>Needs Correction</h3>
            <strong><?= $correction ?></strong>
        </div>
    </section>

    <!-- ACADEMIC RECORDS AND REPORTS -->

    <section class="panel">
        <h2>Student Academic Records</h2>

        <form method="GET" class="search-form">
            <input
                type="text"
                name="search"
                placeholder="Search student number, name, or subject"
                value="<?= h($search) ?>"
            >

            <button class="search-btn" type="submit">
                Search Records
            </button>

            <a
                class="btn export-btn"
                href="?<?= http_build_query([
                    "search" => $search,
                    "export" => "csv"
                ]) ?>"
            >
                Export CSV Report
            </a>
        </form>

        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student No.</th>
                    <th>Student Name</th>
                    <th>Subject</th>
                    <th>Section</th>
                    <th>Prelim</th>
                    <th>Midterm</th>
                    <th>Pre-Final</th>
                    <th>Final</th>
                    <th>GWA / Result</th>
                    <th>Remarks</th>
                    <th>Registrar Status</th>
                    <th>Review Action</th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="12" class="empty">
                        No academic records found.
                    </td>
                </tr>
            <?php else: ?>

                <?php foreach ($records as $r): ?>
                    <?php
                    $statusClass = "Pending";

                    if ($r["registrar_status"] === "Verified") {
                        $statusClass = "Verified";
                    } elseif (
                        $r["registrar_status"] === "Needs Correction"
                    ) {
                        $statusClass = "Correction";
                    }
                    ?>

                    <tr>
                        <td><?= h($r["student_number"]) ?></td>

                        <td>
                            <?= h($r["first_name"] . " " . $r["last_name"]) ?>
                        </td>

                        <td>
                            <?= h($r["subject_code"]) ?>
                            — <?= h($r["subject_name"]) ?>
                        </td>

                        <td><?= h($r["section_name"] ?? "N/A") ?></td>
                        <td><?= h($r["prelim"]) ?></td>
                        <td><?= h($r["midterm"]) ?></td>
                        <td><?= h($r["pre_final"]) ?></td>
                        <td><?= h($r["final_grade"]) ?></td>
                        <td><?= h($r["average"]) ?></td>
                        <td><?= h($r["remarks"]) ?></td>

                        <td>
                            <span class="status <?= $statusClass ?>">
                                <?= h($r["registrar_status"]) ?>
                            </span>
                        </td>

                        <td>
                            <div class="actions">

                                <form method="POST">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h($_SESSION["csrf_token"]) ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="grade_id"
                                        value="<?= (int)$r["grade_id"] ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="verify"
                                    >
                                    <button
                                        class="verify-btn"
                                        type="submit"
                                    >
                                        Verify
                                    </button>
                                </form>

                                <form method="POST">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h($_SESSION["csrf_token"]) ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="grade_id"
                                        value="<?= (int)$r["grade_id"] ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="return"
                                    >
                                    <button
                                        class="return-btn"
                                        type="submit"
                                    >
                                        Return
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>

</main>

<footer>
    DATAMEX COLLEGE OF SAINT ADELINE
    &copy; <?= date("Y") ?>
</footer>

</body>
</html>
