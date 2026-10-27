<?php
session_start();

/* Check administrator login */
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

/* Retrieve teacher accounts */
$sql = "SELECT id, employee_number, full_name, username, email, created_at
        FROM users
        WHERE role = 'teacher'
        ORDER BY full_name ASC";

$result = $conn->query($sql);

if (!$result) {
    die("Unable to retrieve teachers.");
}

function h($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers</title>

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
            max-width: 1200px;
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

        h1 {
            color: #4b285f;
            margin-top: 0;
        }

        .count {
            margin-bottom: 20px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th {
            background: #4b285f;
            color: white;
            text-align: left;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
        }

        tr:hover td {
            background: #f8f5fa;
        }

        .empty {
            padding: 20px;
            background: #f4f7fb;
            border-radius: 8px;
        }
    </style>
</head>

<body>
<div class="container">

    <a class="back" href="admin_dashboard.php">
        &larr; Back to Admin Dashboard
    </a>

    <h1>Manage Teachers</h1>
    <p>View all teacher accounts registered in the system.</p>

    <p class="count">
        <strong>Total Teachers:</strong>
        <?= $result->num_rows ?>
    </p>

    <?php if ($result->num_rows > 0): ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Employee Number</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Date Registered</th>
                    </tr>
                </thead>

                <tbody>
                <?php while ($teacher = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($teacher["employee_number"]) ?></td>
                        <td><?= h($teacher["full_name"]) ?></td>
                        <td><?= h($teacher["username"]) ?></td>
                        <td><?= h($teacher["email"]) ?></td>
                        <td><?= h($teacher["created_at"]) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>

        <div class="empty">
            No teacher accounts were found in the database.
        </div>

    <?php endif; ?>

</div>
</body>
</html>

<?php
$result->free();
$conn->close();
?>