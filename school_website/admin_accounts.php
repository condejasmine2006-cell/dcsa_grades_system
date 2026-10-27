
<?php
session_start();

/* Allow administrators only */
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: login.html");
    exit();
}

/* Connect to database */
$conn = new mysqli("localhost", "root", "", "dcsa_grades");

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

/* Count all registered accounts */
$countQuery = $conn->query(
    "SELECT COUNT(*) AS total FROM users"
);

$totalAccounts = (int)$countQuery->fetch_assoc()["total"];

/* Count accounts by role */
$roleQuery = $conn->query(
    "SELECT role, COUNT(*) AS total
     FROM users
     GROUP BY role"
);

$roleCounts = [
    "student" => 0,
    "teacher" => 0,
    "admin" => 0
];

while ($row = $roleQuery->fetch_assoc()) {
    $roleCounts[$row["role"]] = (int)$row["total"];
}

/* Retrieve account details (never display passwords) */
$accounts = $conn->query(
    "SELECT id, student_number, employee_number,
            full_name, username, email, role, created_at
     FROM users
     ORDER BY id DESC"
);

if (!$accounts) {
    die("Unable to retrieve accounts.");
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

    <title>Total Accounts</title>

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
            max-width: 1300px;
            margin: auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.08);
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #4b285f;
            text-decoration: none;
        }

        h1, h2 {
            color: #4b285f;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .summary-card {
            padding: 20px;
            background: #f4f0f8;
            border-radius: 10px;
        }

        .summary-card h3 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        .number {
            font-size: 30px;
            font-weight: bold;
            color: #4b285f;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        th {
            background: #4b285f;
            color: white;
            text-align: left;
        }

        th, td {
            padding: 13px;
            border-bottom: 1px solid #ddd;
        }

        tr:hover td {
            background: #f8f5fa;
        }

        .role {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            background: #eee4f5;
            color: #4b285f;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .container {
                padding: 18px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <a class="back" href="admin_dashboard.php">
        &larr; Back to Admin Dashboard
    </a>

    <h1>Total Accounts</h1>
    <p>View all registered student, teacher, and administrator accounts.</p>

    <h3>Total Accounts</h3>
<h2><?= (int)$totalAccounts ?></h2>
<p>All registered accounts</p>

    <div class="summary">

        <div class="summary-card">
            <h3>All Accounts</h3>
            <div class="number"><?= $totalAccounts ?></div>
        </div>

        <div class="summary-card">
            <h3>Students</h3>
            <div class="number"><?= $roleCounts["student"] ?></div>
        </div>

        <div class="summary-card">
            <h3>Teachers</h3>
            <div class="number"><?= $roleCounts["teacher"] ?></div>
        </div>

        <div class="summary-card">
            <h3>Administrators</h3>
            <div class="number"><?= $roleCounts["admin"] ?></div>
        </div>

    </div>

    <h2>Registered Accounts</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Account Number</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date Registered</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($accounts->num_rows > 0): ?>

                <?php while ($account = $accounts->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($account["id"]) ?></td>

                        <td>
                            <?php
                            if ($account["role"] === "student") {
                                echo h($account["student_number"]);
                            } elseif ($account["role"] === "teacher") {
                                echo h($account["employee_number"]);
                            } else {
                                echo "—";
                            }
                            ?>
                        </td>

                        <td><?= h($account["full_name"]) ?></td>
                        <td><?= h($account["username"]) ?></td>
                        <td><?= h($account["email"]) ?></td>
                        <td><span class="role"><?= h(ucfirst($account["role"])) ?></span></td>
                        <td><?= h($account["created_at"]) ?></td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="7">No accounts found.</td>
                </tr>

            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>

<?php
$accounts->free();
$countQuery->free();
$roleQuery->free();
$conn->close();
?>
