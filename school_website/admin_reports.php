
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = strtolower(trim(
    $_SESSION["role"] ?? $_SESSION["user_role"] ?? ""
));

if (empty($_SESSION["user_id"])) {
    header("Location: login.html?error=session");
    exit();
}

if (!in_array(
    $role,
    ["admin", "administrator", "registrar"],
    true
)) {
    http_response_code(403);
    exit("Access denied. Your account does not have permission to view reports.");
}

require_once __DIR__ . "/database.php";


// Get registered students and their grades.
$sql = "
    SELECT
        s.student_id,
        s.first_name,
        s.last_name,
        sub.subject_code,
        sub.subject_name,
        g.prelim,
        g.midterm,
        g.pre_final,
        g.final_grade,
        g.average,
        g.remarks
    FROM grades g
    INNER JOIN student_subjects ss
        ON g.student_subject_id = ss.id
    INNER JOIN students s
        ON ss.student_id = s.id
    INNER JOIN subjects sub
        ON ss.subject_id = sub.id
    ORDER BY s.last_name, s.first_name, sub.subject_name
";

$result = $conn->query($sql);

if (!$result) {
    die("Unable to load reports. Please check your database connection and table columns.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grade Reports</title>

    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #f4f7fb;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.08);
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

        .search {
            width: 100%;
            max-width: 400px;
            padding: 12px;
            margin: 20px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th {
            background: #205c4a;
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        tr:hover {
            background: #f0f7f4;
        }

        .print-btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            background: #205c4a;
            color: white;
            cursor: pointer;
            margin-bottom: 15px;
        }

        .empty {
            padding: 20px;
            background: #f4f7fb;
            border-radius: 6px;
        }

        @media print {
            .back, .search, .print-btn {
                display: none;
            }

            body {
                padding: 0;
                background: white;
            }

            .container {
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
<div class="container">

   <a class="back"
   href="<?= $role === 'registrar'
       ? 'registrar_dashboard.php'
       : 'admin_dashboard.php' ?>">
    &larr; Back to Dashboard
</a>

    <h1>Student Grade Reports</h1>
    <p>View recorded student grades and academic results.</p>

    <input
        type="text"
        id="search"
        class="search"
        placeholder="Search student, subject, or student number..."
        onkeyup="filterTable()"
    >

    <br>

    <button class="print-btn" onclick="window.print()">
        Print Report
    </button>

    <?php if ($result->num_rows > 0): ?>

        <div class="table-wrap">
            <table id="reportsTable">
                <thead>
                    <tr>
                        <th>Student Number</th>
                        <th>Student Name</th>
                        <th>Subject Code</th>
                        <th>Subject</th>
                        <th>Prelim</th>
                        <th>Midterm</th>
                        <th>Pre-Final</th>
                        <th>Final Grade</th>
                        <th>Average</th>
                        <th>Remarks</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["student_id"] ?? "") ?></td>

                        <td>
                            <?= htmlspecialchars(
                                trim(($row["first_name"] ?? "") . " " .
                                ($row["last_name"] ?? ""))
                            ) ?>
                        </td>

                        <td><?= htmlspecialchars($row["subject_code"] ?? "") ?></td>
                        <td><?= htmlspecialchars($row["subject_name"] ?? "") ?></td>
                        <td><?= htmlspecialchars($row["prelim"] ?? "") ?></td>
                        <td><?= htmlspecialchars($row["midterm"] ?? "") ?></td>
                        <td><?= htmlspecialchars($row["pre_final"] ?? "") ?></td>
                        <td><?= htmlspecialchars($row["final_grade"] ?? "") ?></td>
                        <td><?= htmlspecialchars($row["average"] ?? "") ?></td>
                        <td><?= htmlspecialchars($row["remarks"] ?? "") ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <p class="empty">
            No grade reports are available yet. Add student-subject
            assignments and grades to display records here.
        </p>
    <?php endif; ?>

</div>

<script>
function filterTable() {
    const input = document.getElementById("search");
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll("#reportsTable tbody tr");

    rows.forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(filter)
            ? ""
            : "none";
    });
}
</script>
</body>
</html>
