<?php

/* ============================================
   START SESSION
============================================ */

session_start();


/* ============================================
   CHECK IF USER IS LOGGED IN
============================================ */

if (!isset($_SESSION["user_id"])) {

    header("Location: login.html");
    exit();
}


/* ============================================
   GET USER SESSION INFORMATION
============================================ */

$firstName = $_SESSION["first_name"] ?? "User";
$lastName  = $_SESSION["last_name"] ?? "";
$username  = $_SESSION["username"] ?? "";
$role      = $_SESSION["role"] ?? "";

$fullName = trim($firstName . " " . $lastName);


/* ============================================
   CHECK VALID ROLE
============================================ */

if (
    $role !== "student" &&
    $role !== "teacher"
) {

    session_destroy();

    header("Location: login.html");
    exit();
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

    <title>DCSA Grading System</title>

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

</head>


<body>


<!-- ============================================
     SIDEBAR
============================================= -->

<aside class="sidebar">


    <!-- LOGO -->

    <div class="logo-section">

        <div class="logo">
            D
        </div>

        <div class="logo-text">

            <h2>
                DCSA
            </h2>

            <p>
                Grading System
            </p>

        </div>

    </div>



    <!-- MENU -->

    <nav class="sidebar-menu">


        <a
            href="dashboard.php"
            class="menu-item active"
        >

            <span class="menu-icon">
                🏠
            </span>

            Dashboard

        </a>



        <!-- ====================================
             STUDENT MENU
        ===================================== -->

        <?php if ($role === "student"): ?>


            <a
                href="#grades"
                class="menu-item"
            >

                <span class="menu-icon">
                    📊
                </span>

                My Grades

            </a>


            <a
                href="#subjects"
                class="menu-item"
            >

                <span class="menu-icon">
                    📚
                </span>

                My Subjects

            </a>


            <a
                href="#profile"
                class="menu-item"
            >

                <span class="menu-icon">
                    👤
                </span>

                My Profile

            </a>


        <?php endif; ?>



        <!-- ====================================
             TEACHER MENU
        ===================================== -->

        <?php if ($role === "teacher"): ?>


            <a
                href="#students"
                class="menu-item"
            >

                <span class="menu-icon">
                    👨‍🎓
                </span>

                Students

            </a>


            <a
                href="#subjects"
                class="menu-item"
            >

                <span class="menu-icon">
                    📚
                </span>

                Subjects

            </a>


            <a
                href="#grades"
                class="menu-item"
            >

                <span class="menu-icon">
                    📝
                </span>

                Manage Grades

            </a>


            <a
                href="#profile"
                class="menu-item"
            >

                <span class="menu-icon">
                    👤
                </span>

                Profile

            </a>


        <?php endif; ?>



        <!-- LOGOUT -->

        <a
            href="logout.php"
            class="menu-item logout"
        >

            <span class="menu-icon">
                🚪
            </span>

            Logout

        </a>


    </nav>

</aside>



<!-- ============================================
     MAIN CONTENT
============================================= -->

<main class="main-content">


    <!-- ========================================
         TOP HEADER
    ========================================= -->

    <header class="topbar">


        <div class="page-title">

            <h1>
                Dashboard
            </h1>

            <p>
                DCSA School Grading System
            </p>

        </div>



        <!-- USER PROFILE -->

        <div class="user-profile">


            <div class="avatar">

                <?php

                echo htmlspecialchars(
                    strtoupper(
                        substr(
                            $firstName,
                            0,
                            1
                        )
                    )
                );

                ?>

            </div>


            <div class="user-info">

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $fullName
                    );
                    ?>

                </strong>

                <span>

                    <?php
                    echo htmlspecialchars(
                        ucfirst($role)
                    );
                    ?>

                </span>

            </div>


        </div>


    </header>



    <!-- ========================================
         WELCOME BANNER
    ========================================= -->

    <section class="welcome-banner">


        <div class="welcome-content">

            <p class="welcome-label">

                DCSA GRADING SYSTEM

            </p>


            <h2>

                Welcome,
                <?php
                echo htmlspecialchars(
                    $firstName
                );
                ?>!

            </h2>


            <?php if ($role === "student"): ?>

                <p>

                    View your subjects,
                    academic grades and
                    student information.

                </p>

            <?php endif; ?>


            <?php if ($role === "teacher"): ?>

                <p>

                    Manage your students,
                    subjects and academic
                    grades.

                </p>

            <?php endif; ?>


        </div>


        <div class="welcome-role">

            <?php
            echo htmlspecialchars(
                ucfirst($role)
            );
            ?>

        </div>


    </section>



    <!-- ========================================
         STUDENT DASHBOARD
    ========================================= -->
<!-- Student -->
<a href="student_dashboard.php">
    Dashboard
</a>

    <?php if ($role === "student"): ?>


        <!-- STUDENT SUMMARY CARDS -->

        <section class="cards-container">


            <div class="dashboard-card">

                <div class="card-icon">
                    📚
                </div>

                <div>

                    <p class="card-label">
                        Subjects
                    </p>

                    <h3>
                        6
                    </h3>

                    <span>
                        Enrolled subjects
                    </span>

                </div>

            </div>



            <div class="dashboard-card">

                <div class="card-icon">
                    📊
                </div>

                <div>

                    <p class="card-label">
                        General Average
                    </p>

                    <h3>
                        89
                    </h3>

                    <span>
                        Current average
                    </span>

                </div>

            </div>



            <div class="dashboard-card">

                <div class="card-icon">
                    ✅
                </div>

                <div>

                    <p class="card-label">
                        Status
                    </p>

                    <h3 class="green-text">
                        Active
                    </h3>

                    <span>
                        Enrolled student
                    </span>

                </div>

            </div>


        </section>



        <!-- ====================================
             STUDENT GRADES
        ===================================== -->

        <section
            class="content-card"
            id="grades"
        >


            <div class="section-heading">

                <div>

                    <h2>
                        My Grades
                    </h2>

                    <p>
                        Your current academic grades
                    </p>

                </div>

            </div>



            <div class="table-container">

                <table>


                    <thead>

                        <tr>

                            <th>
                                Subject Code
                            </th>

                            <th>
                                Subject
                            </th>

                            <th>
                                Prelim
                            </th>

                            <th>
                                Midterm
                            </th>

                            <th>
                                Final
                            </th>

                            <th>
                                Average
                            </th>

                            <th>
                                Remarks
                            </th>

                        </tr>

                    </thead>



                    <tbody>


                        <tr>

                            <td>
                                IT101
                            </td>

                            <td>
                                Introduction to Computing
                            </td>

                            <td>
                                90
                            </td>

                            <td>
                                88
                            </td>

                            <td>
                                92
                            </td>

                            <td>
                                <strong>
                                    90
                                </strong>
                            </td>

                            <td>

                                <span class="status passed">
                                    Passed
                                </span>

                            </td>

                        </tr>



                        <tr>

                            <td>
                                IT102
                            </td>

                            <td>
                                Computer Programming
                            </td>

                            <td>
                                87
                            </td>

                            <td>
                                89
                            </td>

                            <td>
                                91
                            </td>

                            <td>
                                <strong>
                                    89
                                </strong>
                            </td>

                            <td>

                                <span class="status passed">
                                    Passed
                                </span>

                            </td>

                        </tr>



                        <tr>

                            <td>
                                IT103
                            </td>

                            <td>
                                Database Management
                            </td>

                            <td>
                                85
                            </td>

                            <td>
                                88
                            </td>

                            <td>
                                90
                            </td>

                            <td>
                                <strong>
                                    88
                                </strong>
                            </td>

                            <td>

                                <span class="status passed">
                                    Passed
                                </span>

                            </td>

                        </tr>


                    </tbody>


                </table>

            </div>


        </section>



        <!-- ====================================
             STUDENT PROFILE
        ===================================== -->

        <section
            class="content-card"
            id="profile"
        >


            <div class="section-heading">

                <div>

                    <h2>
                        Student Information
                    </h2>

                    <p>
                        Your account information
                    </p>

                </div>

            </div>



            <div class="information-grid">


                <div class="information-item">

                    <span>
                        Full Name
                    </span>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $fullName
                        );
                        ?>

                    </strong>

                </div>



                <div class="information-item">

                    <span>
                        Username
                    </span>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $username
                        );
                        ?>

                    </strong>

                </div>



                <div class="information-item">

                    <span>
                        Account Type
                    </span>

                    <strong>
                        Student
                    </strong>

                </div>



                <div class="information-item">

                    <span>
                        Account Status
                    </span>

                    <strong class="green-text">
                        Active
                    </strong>

                </div>


            </div>


        </section>


    <?php endif; ?>



    <!-- ========================================
         TEACHER DASHBOARD
    ========================================= -->
<!-- Teacher -->
<a href="teacher_dashboard.php">
    Dashboard
</a>

    <?php if ($role === "teacher"): ?>


        <!-- TEACHER SUMMARY -->

        <section class="cards-container">


            <div class="dashboard-card">

                <div class="card-icon">
                    👨‍🎓
                </div>

                <div>

                    <p class="card-label">
                        Students
                    </p>

                    <h3>
                        30
                    </h3>

                    <span>
                        Total students
                    </span>

                </div>

            </div>



            <div class="dashboard-card">

                <div class="card-icon">
                    📚
                </div>

                <div>

                    <p class="card-label">
                        Subjects
                    </p>

                    <h3>
                        5
                    </h3>

                    <span>
                        Assigned subjects
                    </span>

                </div>

            </div>



            <div class="dashboard-card">

                <div class="card-icon">
                    📝
                </div>

                <div>

                    <p class="card-label">
                        Grades
                    </p>

                    <h3>
                        Manage
                    </h3>

                    <span>
                        Student grades
                    </span>

                </div>

            </div>


        </section>



        <!-- ====================================
             STUDENTS
        ===================================== -->

        <section
            class="content-card"
            id="students"
        >


            <div class="section-heading">

                <div>

                    <h2>
                        Students
                    </h2>

                    <p>
                        Student grade management
                    </p>

                </div>


                <button class="primary-button">

                    + Add Grade

                </button>

            </div>



            <div class="table-container">


                <table>


                    <thead>

                        <tr>

                            <th>
                                Student No.
                            </th>

                            <th>
                                Student
                            </th>

                            <th>
                                Subject
                            </th>

                            <th>
                                Prelim
                            </th>

                            <th>
                                Midterm
                            </th>

                            <th>
                                Final
                            </th>

                            <th>
                                Average
                            </th>

                            <th>
                                Remarks
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>



                    <tbody>


                        <tr>

                            <td>
                                2026-001
                            </td>

                            <td>
                                Juan Dela Cruz
                            </td>

                            <td>
                                IT101
                            </td>

                            <td>
                                90
                            </td>

                            <td>
                                88
                            </td>

                            <td>
                                92
                            </td>

                            <td>
                                90
                            </td>

                            <td>

                                <span class="status passed">
                                    Passed
                                </span>

                            </td>

                            <td>

                                <button class="edit-button">
                                    Edit
                                </button>

                            </td>

                        </tr>



                        <tr>

                            <td>
                                2026-002
                            </td>

                            <td>
                                Maria Santos
                            </td>

                            <td>
                                IT101
                            </td>

                            <td>
                                86
                            </td>

                            <td>
                                88
                            </td>

                            <td>
                                91
                            </td>

                            <td>
                                88
                            </td>

                            <td>

                                <span class="status passed">
                                    Passed
                                </span>

                            </td>

                            <td>

                                <button class="edit-button">
                                    Edit
                                </button>

                            </td>

                        </tr>


                    </tbody>


                </table>


            </div>


        </section>



        <!-- ====================================
             TEACHER PROFILE
        ===================================== -->

        <section
            class="content-card"
            id="profile"
        >


            <div class="section-heading">

                <div>

                    <h2>
                        Teacher Information
                    </h2>

                    <p>
                        Your account information
                    </p>

                </div>

            </div>



            <div class="information-grid">


                <div class="information-item">

                    <span>
                        Full Name
                    </span>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $fullName
                        );
                        ?>

                    </strong>

                </div>



                <div class="information-item">

                    <span>
                        Username
                    </span>

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $username
                        );
                        ?>

                    </strong>

                </div>



                <div class="information-item">

                    <span>
                        Account Type
                    </span>

                    <strong>
                        Teacher
                    </strong>

                </div>



                <div class="information-item">

                    <span>
                        Account Status
                    </span>

                    <strong class="green-text">
                        Active
                    </strong>

                </div>


            </div>


        </section>


    <?php endif; ?>


    <?php

session_start();

/* ==========================================
   PROTECT ADMIN PAGE
========================================== */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "admin"
) {

    header("Location: login.html");
    exit();

}


/* ==========================================
   GET SESSION DATA
========================================== */

$fullName = $_SESSION["full_name"] ?? "Administrator";
$username = $_SESSION["username"] ?? "";

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Admin Dashboard
    </title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f6f9;
        }


        /* =================================
           SIDEBAR
        ================================= */

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #17324d;
            color: white;
            padding: 25px 18px;
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo h2 {
            font-size: 28px;
        }

        .logo p {
            font-size: 13px;
            margin-top: 5px;
            color: #cbd5e1;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .menu a {
            color: white;
            text-decoration: none;
            padding: 13px 15px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .menu a:hover {
            background: #284d70;
        }

        .menu .active {
            background: #284d70;
        }

        .logout {
            background: #b42318;
            margin-top: 20px;
        }


        /* =================================
           MAIN CONTENT
        ================================= */

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;

            background: white;
            padding: 20px 25px;
            border-radius: 12px;

            box-shadow: 0 2px 10px
                rgba(0,0,0,0.06);

            margin-bottom: 25px;
        }

        .topbar h1 {
            font-size: 28px;
            color: #17324d;
        }

        .user-info {
            text-align: right;
        }

        .user-info strong {
            display: block;
        }

        .user-info span {
            font-size: 13px;
            color: #666;
        }


        /* =================================
           WELCOME
        ================================= */

        .welcome-box {
            background: #17324d;
            color: white;

            padding: 30px;
            border-radius: 14px;

            margin-bottom: 25px;
        }

        .welcome-box h2 {
            margin-bottom: 8px;
        }


        /* =================================
           CARDS
        ================================= */

        .cards {
            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(220px, 1fr)
                );

            gap: 20px;

            margin-bottom: 30px;
        }

        .card {
            background: white;

            padding: 25px;

            border-radius: 12px;

            box-shadow: 0 3px 12px
                rgba(0,0,0,0.08);
        }

        .card h3 {
            color: #555;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .card .number {
            font-size: 32px;
            font-weight: bold;
            color: #17324d;
        }

        .card p {
            margin-top: 8px;
            color: #777;
            font-size: 14px;
        }


        /* =================================
           TABLE SECTION
        ================================= */

        .content-card {
            background: white;

            padding: 25px;

            border-radius: 12px;

            box-shadow: 0 3px 12px
                rgba(0,0,0,0.08);

            margin-bottom: 25px;
        }

        .content-card h2 {
            color: #17324d;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f3f6f9;
            color: #17324d;
        }

        .status {
            background: #d1fae5;
            color: #166534;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;
        }

        .button {
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
        }

        .edit-button {
            background: #2563eb;
            color: white;
        }

        .delete-button {
            background: #dc2626;
            color: white;
        }


        /* =================================
           RESPONSIVE
        ================================= */

        @media (max-width: 768px) {

            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
            }

        }

    </style>

</head>


<body>


<!-- ==========================================
     SIDEBAR
========================================== -->

<aside class="sidebar">

    <div class="logo">

        <h2>
            DCSA
        </h2>

        <p>
            Grading System
        </p>

    </div>


    <nav class="menu">

        <a
            href="admin_dashboard.php"
            class="active"
        >
            🏠 Dashboard
        </a>

        <a href="#students">
            👨‍🎓 Manage Students
        </a>

        <a href="#teachers">
            👨‍🏫 Manage Teachers
        </a>

        <a href="#subjects">
            📚 Manage Subjects
        </a>

        <a href="#accounts">
            👤 Manage Accounts
        </a>

        <a href="#reports">
            📊 Reports
        </a>

        <a
            href="logout.php"
            class="logout"
        >
            🚪 Logout
        </a>

    </nav>

</aside>


<!-- ==========================================
     MAIN CONTENT
========================================== -->

<main class="main-content">


    <!-- TOPBAR -->

    <div class="topbar">

        <div>

            <h1>
                Admin Dashboard
            </h1>

            <p>
                DCSA School Grading System
            </p>

        </div>


        <div class="user-info">

            <strong>

                <?php
                    echo htmlspecialchars(
                        $fullName
                    );
                ?>

            </strong>

            <span>
                Administrator
            </span>

        </div>

    </div>



    <!-- WELCOME -->

    <section class="welcome-box">

        <h2>

            Welcome,
            <?php
                echo htmlspecialchars(
                    $fullName
                );
            ?>!

        </h2>

        <p>
            Manage students, teachers,
            subjects, grades and school accounts.
        </p>

    </section>



    <!-- SUMMARY CARDS -->

    <section class="cards">

        <div class="card">

            <h3>
                Total Students
            </h3>

            <div class="number">
                120
            </div>

            <p>
                Registered student accounts
            </p>

        </div>


        <div class="card">

            <h3>
                Total Teachers
            </h3>

            <div class="number">
                15
            </div>

            <p>
                Registered teacher accounts
            </p>

        </div>


        <div class="card">

            <h3>
                Total Subjects
            </h3>

            <div class="number">
                25
            </div>

            <p>
                Available subjects
            </p>

        </div>


        <div class="card">

            <h3>
                Total Accounts
            </h3>

            <div class="number">
                136
            </div>

            <p>
                All system accounts
            </p>

        </div>

    </section>



    <!-- STUDENTS -->

    <section
        class="content-card"
        id="students"
    >

        <h2>
            Manage Students
        </h2>


        <table>

            <thead>

                <tr>

                    <th>
                        Student No.
                    </th>

                    <th>
                        Name
                    </th>

                    <th>
                        Course
                    </th>

                    <th>
                        Year Level
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

                <tr>

                    <td>
                        2026-001
                    </td>

                    <td>
                        Juan Dela Cruz
                    </td>

                    <td>
                        BSIT
                    </td>

                    <td>
                        1st Year
                    </td>

                    <td>

                        <span class="status">
                            Active
                        </span>

                    </td>

                    <td>

                        <button
                            class="button edit-button"
                        >
                            Edit
                        </button>

                        <button
                            class="button delete-button"
                        >
                            Delete
                        </button>

                    </td>

                </tr>


                <tr>

                    <td>
                        2026-002
                    </td>

                    <td>
                        Maria Santos
                    </td>

                    <td>
                        BSIT
                    </td>

                    <td>
                        2nd Year
                    </td>

                    <td>

                        <span class="status">
                            Active
                        </span>

                    </td>

                    <td>

                        <button
                            class="button edit-button"
                        >
                            Edit
                        </button>

                        <button
                            class="button delete-button"
                        >
                            Delete
                        </button>

                    </td>

                </tr>

            </tbody>

        </table>

    </section>



    <!-- TEACHERS -->

    <section
        class="content-card"
        id="teachers"
    >

        <h2>
            Manage Teachers
        </h2>


        <table>

            <thead>

                <tr>

                    <th>
                        Employee No.
                    </th>

                    <th>
                        Teacher
                    </th>

                    <th>
                        Username
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

                <tr>

                    <td>
                        T-001
                    </td>

                    <td>
                        Teacher Sample
                    </td>

                    <td>
                        teacher01
                    </td>

                    <td>

                        <span class="status">
                            Active
                        </span>

                    </td>

                    <td>

                        <button
                            class="button edit-button"
                        >
                            Edit
                        </button>

                        <button
                            class="button delete-button"
                        >
                            Delete
                        </button>

                    </td>

                </tr>

            </tbody>

        </table>

    </section>



    <!-- SUBJECTS -->

    <section
        class="content-card"
        id="subjects"
    >

        <h2>
            Manage Subjects
        </h2>

        <p>
            You can add, edit and remove
            subjects here.
        </p>

    </section>



    <!-- ACCOUNTS -->

    <section
        class="content-card"
        id="accounts"
    >

        <h2>
            Manage Accounts
        </h2>

        <p>
            Manage student, teacher
            and administrator accounts.
        </p>

    </section>



    <!-- REPORTS -->

    <section
        class="content-card"
        id="reports"
    >

        <h2>
            Reports
        </h2>

        <p>
            View grading and student reports.
        </p>

    </section>


</main>




    <!-- ========================================
         FOOTER
    ========================================= -->

    <footer>

        © 2026 DCSA Grading System

    </footer>


</main>


</body>

</html>