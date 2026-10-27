```php
<?php
session_start();

// Check if the user is logged in as a teacher
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "teacher"
) {
    header("Location: login.html");
    exit();
}

// Get teacher information
$name = htmlspecialchars(
    $_SESSION["full_name"] ?? "Teacher",
    ENT_QUOTES,
    "UTF-8"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Teacher Dashboard</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f7fb;
        }

      /* ==========================================
   SIDEBAR
========================================== */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;

    width: 240px;
    height: 100vh;

    /* Sidebar background image */
    background-image:
        linear-gradient(
            rgba(45, 25, 60, 0.55),
            rgba(45, 25, 60, 0.55)
        ),
        url("img.jpg");

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

.sidebar a:hover {
    background: rgba(255, 255, 255, 0.2);
}

        /* MAIN CONTENT */
        .main {
            margin-left: 240px;
            padding: 30px;
        }

        /* CLICKABLE WELCOME BAR */
        .topbar {
            display: block;
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;

            color: black;
            text-decoration: none;
            cursor: pointer;

            transition: box-shadow 0.2s;
        }

        .topbar:hover {
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.10);
        }

        .topbar h1 {
            font-size: 30px;
            margin-bottom: 2px;
        }

        .topbar p {
            font-size: 16px;
        }

        /* DASHBOARD CARDS */
        .cards {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            display: block;
            background: white;
            padding: 25px;
            border-radius: 12px;

            color: black;
            text-decoration: none;
            cursor: pointer;

            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);

            transition:
                transform 0.2s,
                box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .card h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }

        .card p {
            font-size: 16px;
            line-height: 1.5;
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 700px) {
            .sidebar {
                width: 180px;
                padding: 20px 12px;
            }

            .main {
                margin-left: 180px;
                padding: 15px;
            }

            .topbar h1 {
                font-size: 23px;
            }

            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR MENU -->
    <div class="sidebar">

        <h2>Teacher Portal</h2>

        <a href="teacher_dashboard.php">
            Dashboard
        </a>

        <a href="teacher_students.php">
            My Students
        </a>

        <a href="teacher_grades.php">
            Manage Grades
        </a>

        <a href="teacher_subjects.php">
            Subjects
        </a>

        <a href="teacher_profile.php">
            Profile
        </a>

        <a href="logout.php" class="logout">
            Logout
        </a>

    </div>


    <!-- MAIN DASHBOARD -->
    <div class="main">

        <!-- CLICKABLE WELCOME BAR -->
        <a href="teacher_dashboard.php" class="topbar">

            <h1>
                Welcome, <?php echo $name; ?>!
            </h1>

            <p>
                Teacher Dashboard
            </p>

        </a>


        <!-- CLICKABLE DASHBOARD CARDS -->
        <div class="cards">

            <!-- STUDENTS -->
            <a href="teacher_students.php" class="card">

                <h3>Students</h3>

                <p>
                    View your assigned students.
                </p>

            </a>


            <!-- MANAGE GRADES -->
            <a href="teacher_grades.php" class="card">

                <h3>Manage Grades</h3>

                <p>
                    Add and update student grades.
                </p>

            </a>


            <!-- SUBJECTS -->
            <a href="teacher_subjects.php" class="card">

                <h3>Subjects</h3>

                <p>
                    Manage your assigned subjects.
                </p>

            </a>


            
            <!-- PROFILE -->
            <a href="teacher_profile.php" class="card">

                <h3>Profile</h3>

                <p>
                    Update your profile information.
                </p>

            </a>


        </div>

    </div>

</body>
</html>
