<?php

session_start();

/* ==========================================
   CHECK STUDENT LOGIN
========================================== */

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: login.html");
    exit();
}


/* ==========================================
   DATABASE CONNECTION
========================================== */

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "dcsa_grades"
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


/* ==========================================
   GET LOGGED-IN STUDENT
========================================== */

$user_id = (int) $_SESSION["user_id"];

$student_sql = "
    SELECT
        u.id AS user_id,
        u.student_number,
        u.full_name,
        st.id AS student_id,
        st.first_name,
        st.last_name
    FROM users u
    INNER JOIN students st
        ON TRIM(st.student_id) = TRIM(u.student_number)
    WHERE u.id = ?
      AND u.role = 'student'
    LIMIT 1
";

$stmt = $conn->prepare($student_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student || empty($student["student_id"])) {
    die(
        "Your student record is not linked correctly. "
        . "Please contact the administrator."
    );
}

$student_id = (int) $student["student_id"];


/* ==========================================
   GET STUDENT GRADES
========================================== */

$sql = "
    SELECT
        subjects.subject_code,
        subjects.subject_name,
        subjects.units,
        subjects.year_level,
        subjects.semester,
        subjects.school_year,
        student_subjects.enrollment_type,
        grades.prelim,
        grades.midterm,
        grades.pre_final,
        grades.final_grade,
        grades.average,
        grades.remarks
    FROM student_subjects
    INNER JOIN subjects
        ON student_subjects.subject_id = subjects.id
    LEFT JOIN grades
        ON grades.student_subject_id = student_subjects.id
    WHERE student_subjects.student_id = ?
    ORDER BY
        subjects.year_level,
        subjects.semester,
        subjects.subject_code
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Grades</title>


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


        /* SIDEBAR */

        .sidebar {

            position: fixed;

            width: 240px;

            height: 100vh;

            background: #17324d;

            color: white;

            padding: 30px 20px;
        }


        .sidebar h2 {

            margin-bottom: 35px;

        }


        .sidebar a {

            display: block;

            color: white;

            text-decoration: none;

            padding: 12px;

            margin-bottom: 10px;

            border-radius: 8px;
        }


        .sidebar a:hover {

            background: #284d70;

        }


        .logout {

            background: #c0392b;

        }


        /* MAIN */

        .main {

            margin-left: 240px;

            padding: 30px;

        }


        .header {

            background: white;

            padding: 25px;

            border-radius: 12px;

            margin-bottom: 25px;

        }


        .header h1 {

            margin-bottom: 8px;

        }


        /* TABLE */

        .table-container {

            background: white;

            padding: 25px;

            border-radius: 12px;

            overflow-x: auto;

            box-shadow:
                0 3px 12px
                rgba(0,0,0,0.08);
        }


        table {

            width: 100%;

            border-collapse: collapse;

            margin-top: 20px;

            min-width: 1000px;
        }


        th {

            background: #17324d;

            color: white;

            padding: 13px;

            text-align: center;
        }


        td {

            padding: 12px;

            border-bottom: 1px solid #ddd;

            text-align: center;
        }


        td:nth-child(2) {

            text-align: left;

        }


        tr:hover {

            background: #f5f5f5;

        }


        .no-grade {

            color: #888;

        }


        .passed {

            color: green;

            font-weight: bold;

        }


        .failed {

            color: red;

            font-weight: bold;

        }


    </style>

</head>


<body>


<!-- ==========================================
     SIDEBAR
========================================== -->

<div class="sidebar">

    <h2>
        Student Portal
    </h2>


    <a href="student_dashboard.php">
        Dashboard
    </a>


    <a href="my_grades.php">
        My Grades
    </a>


    <a href="subjects.php">
        Subjects
    </a>


    <a href="#">
        Profile
    </a>


    <a
        href="logout.php"
        class="logout"
    >
        Logout
    </a>

</div>



<!-- ==========================================
     MAIN CONTENT
========================================== -->

<div class="main">


    <div class="header">

        <h1>
            My Grades
        </h1>

        <p>

            Student:
            <strong>

                <?php

                echo htmlspecialchars(
                    $student["first_name"]
                );

                ?>

                <?php

                echo htmlspecialchars(
                    $student["last_name"]
                );

                ?>

            </strong>

        </p>

    </div>



    <div class="table-container">

        <h2>
            Academic Grades
        </h2>


        <?php if ($result->num_rows > 0): ?>


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
                        Units
                    </th>

                    <th>
                        Year
                    </th>

                    <th>
                        Semester
                    </th>

                    <th>
                        Prelim
                    </th>

                    <th>
                        Midterm
                    </th>

                    <th>
                        Pre-Final
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

                    <th>Enrollment Type</th>

                </tr>

            </thead>


            <tbody>


            <?php while ($row = $result->fetch_assoc()): ?>


                <tr>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row["subject_code"]
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row["subject_name"]
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row["units"]
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row["year_level"]
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row["semester"]
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo $row["prelim"] !== null
                            ? htmlspecialchars($row["prelim"])
                            : '<span class="no-grade">N/A</span>';

                        ?>

                    </td>


                    <td>

                        <?php

                        echo $row["midterm"] !== null
                            ? htmlspecialchars($row["midterm"])
                            : '<span class="no-grade">N/A</span>';

                        ?>

                    </td>

                    <td>

                        <?php

                        echo $row["pre_final"] !== null
                            ? htmlspecialchars($row["pre_final"])
                            : '<span class="no-grade">N/A</span>';

                        ?>

                    </td>


                    <td>

                        <?php

                        echo $row["final_grade"] !== null
                            ? htmlspecialchars($row["final_grade"])
                            : '<span class="no-grade">N/A</span>';

                        ?>

                    </td>


                    <td>

                        <?php

                        echo $row["average"] !== null
                            ? htmlspecialchars($row["average"])
                            : '<span class="no-grade">N/A</span>';

                        ?>

                    </td>

                    <td>
    <?= htmlspecialchars(
        $row["enrollment_type"] ?? "REGULAR",
        ENT_QUOTES,
        "UTF-8"
    ) ?>
</td>


                    <td>

                        <?php

                        if (
                            $row["remarks"] !== null &&
                            $row["remarks"] !== ""
                        ) {

                            $remarks =
                                htmlspecialchars(
                                    $row["remarks"]
                                );

                            if (
                                strtolower(
                                    $row["remarks"]
                                ) === "passed"
                            ) {

                                echo
                                    '<span class="passed">'
                                    . $remarks .
                                    '</span>';

                            } elseif (
                                strtolower(
                                    $row["remarks"]
                                ) === "failed"
                            ) {

                                echo
                                    '<span class="failed">'
                                    . $remarks .
                                    '</span>';

                            } else {

                                echo $remarks;

                            }

                        } else {

                            echo
                                '<span class="no-grade">
                                    N/A
                                </span>';

                        }

                        ?>

                    </td>


                </tr>


            <?php endwhile; ?>


            </tbody>

        </table>


        <?php else: ?>


            <p style="margin-top:20px;">

                No subjects or grades have been assigned
                to your account yet.

            </p>


        <?php endif; ?>


    </div>

</div>


</body>

</html>


<?php

$conn->close();

?>