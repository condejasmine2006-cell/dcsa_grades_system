<?php

session_start();

/* =========================================================
   TEACHER LOGIN CHECK
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "teacher"
) {
    header("Location: login.html");
    exit();
}


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "db_connect.php";

$teacherId = (int) $_SESSION["user_id"];

$message = "";
$messageType = "success";


/* =========================================================
   SAFE HTML OUTPUT
========================================================= */

function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   PHILIPPINE GWA CONVERSION
=========================================================

   YOUR GRADING SCALE:

   97.5 - 100   = 1.00
   96.5 - 97.4  = 1.20
   95.5 - 96.4  = 1.25
   94.5 - 95.4  = 1.30
   93.5 - 94.4  = 1.40
   92.5 - 93.4  = 1.50
   91.5 - 92.4  = 1.60
   90.5 - 91.4  = 1.70
   89.5 - 90.4  = 1.75
   88.5 - 89.4  = 1.80
   87.5 - 88.4  = 1.90
   86.5 - 87.4  = 2.00
   85.5 - 86.4  = 2.10
   84.5 - 85.4  = 2.20
   83.5 - 84.4  = 2.25
   82.5 - 83.4  = 2.30
   81.5 - 82.4  = 2.40
   80.5 - 81.4  = 2.50
   79.5 - 80.4  = 2.60
   78.5 - 79.4  = 2.70
   77.5 - 78.4  = 2.75
   76.5 - 77.4  = 2.80
   75.5 - 76.4  = 2.90
   74.5 - 75.4  = 3.00
   69.5 - 74.4  = 5.00 / INC
   0 - 69.4     = 5.00 / Failed

========================================================= */

function convertGWA($average)
{
    $average = (float)$average;


    /* 97.5 - 100 = 1.00 */

    if ($average >= 97.5 && $average <= 100) {
        return [
            "grade" => "1.00",
            "remarks" => "Passed"
        ];
    }


    /* 96.5 - 97.4 = 1.20 */

    if ($average >= 96.5) {
        return [
            "grade" => "1.20",
            "remarks" => "Passed"
        ];
    }


    /* 95.5 - 96.4 = 1.25 */

    if ($average >= 95.5) {
        return [
            "grade" => "1.25",
            "remarks" => "Passed"
        ];
    }


    /* 94.5 - 95.4 = 1.30 */

    if ($average >= 94.5) {
        return [
            "grade" => "1.30",
            "remarks" => "Passed"
        ];
    }


    /* 93.5 - 94.4 = 1.40 */

    if ($average >= 93.5) {
        return [
            "grade" => "1.40",
            "remarks" => "Passed"
        ];
    }


    /* 92.5 - 93.4 = 1.50 */

    if ($average >= 92.5) {
        return [
            "grade" => "1.50",
            "remarks" => "Passed"
        ];
    }


    /* 91.5 - 92.4 = 1.60 */

    if ($average >= 91.5) {
        return [
            "grade" => "1.60",
            "remarks" => "Passed"
        ];
    }


    /* 90.5 - 91.4 = 1.70 */

    if ($average >= 90.5) {
        return [
            "grade" => "1.70",
            "remarks" => "Passed"
        ];
    }


    /* 89.5 - 90.4 = 1.75 */

    if ($average >= 89.5) {
        return [
            "grade" => "1.75",
            "remarks" => "Passed"
        ];
    }


    /* 88.5 - 89.4 = 1.80 */

    if ($average >= 88.5) {
        return [
            "grade" => "1.80",
            "remarks" => "Passed"
        ];
    }


    /* 87.5 - 88.4 = 1.90 */

    if ($average >= 87.5) {
        return [
            "grade" => "1.90",
            "remarks" => "Passed"
        ];
    }


    /* 86.5 - 87.4 = 2.00 */

    if ($average >= 86.5) {
        return [
            "grade" => "2.00",
            "remarks" => "Passed"
        ];
    }


    /* 85.5 - 86.4 = 2.10 */

    if ($average >= 85.5) {
        return [
            "grade" => "2.10",
            "remarks" => "Passed"
        ];
    }


    /* 84.5 - 85.4 = 2.20 */

    if ($average >= 84.5) {
        return [
            "grade" => "2.20",
            "remarks" => "Passed"
        ];
    }


    /* 83.5 - 84.4 = 2.25 */

    if ($average >= 83.5) {
        return [
            "grade" => "2.25",
            "remarks" => "Passed"
        ];
    }


    /* 82.5 - 83.4 = 2.30 */

    if ($average >= 82.5) {
        return [
            "grade" => "2.30",
            "remarks" => "Passed"
        ];
    }


    /* 81.5 - 82.4 = 2.40 */

    if ($average >= 81.5) {
        return [
            "grade" => "2.40",
            "remarks" => "Passed"
        ];
    }


    /* 80.5 - 81.4 = 2.50 */

    if ($average >= 80.5) {
        return [
            "grade" => "2.50",
            "remarks" => "Passed"
        ];
    }


    /* 79.5 - 80.4 = 2.60 */

    if ($average >= 79.5) {
        return [
            "grade" => "2.60",
            "remarks" => "Passed"
        ];
    }


    /* 78.5 - 79.4 = 2.70 */

    if ($average >= 78.5) {
        return [
            "grade" => "2.70",
            "remarks" => "Passed"
        ];
    }


    /* 77.5 - 78.4 = 2.75 */

    if ($average >= 77.5) {
        return [
            "grade" => "2.75",
            "remarks" => "Passed"
        ];
    }


    /* 76.5 - 77.4 = 2.80 */

    if ($average >= 76.5) {
        return [
            "grade" => "2.80",
            "remarks" => "Passed"
        ];
    }


    /* 75.5 - 76.4 = 2.90 */

    if ($average >= 75.5) {
        return [
            "grade" => "2.90",
            "remarks" => "Passed"
        ];
    }


    /* 74.5 - 75.4 = 3.00 */

    if ($average >= 74.5) {
        return [
            "grade" => "3.00",
            "remarks" => "Passed"
        ];
    }


    /* 69.5 - 74.4 = INC / 5.00 */

    if ($average >= 69.5) {
        return [
            "grade" => "5.00",
            "remarks" => "INC"
        ];
    }


    /* 0 - 69.4 = FAILED / 5.00 */

    return [
        "grade" => "5.00",
        "remarks" => "Failed"
    ];
}


/* =========================================================
   SAVE / UPDATE GRADE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $enrollmentId = filter_input(
        INPUT_POST,
        "student_subject_id",
        FILTER_VALIDATE_INT
    );


    $prelim = $_POST["prelim"] ?? "";
    $midterm = $_POST["midterm"] ?? "";
    $preFinal = $_POST["pre_final"] ?? "";
    $finalGrade = $_POST["final_grade"] ?? "";

    $gradeStatus = $_POST["grade_status"] ?? "normal";


    $values = [
        $prelim,
        $midterm,
        $preFinal,
        $finalGrade
    ];


    $valid = true;

    $numericGrades = [];


    /* =====================================================
       VALIDATE ENROLLMENT
    ====================================================== */

    if (!$enrollmentId) {
        $valid = false;
    }


    /* =====================================================
       VALIDATE GRADE STATUS
    ====================================================== */

    if (
        !in_array(
            $gradeStatus,
            ["normal", "udrl"],
            true
        )
    ) {
        $gradeStatus = "normal";
    }


    /* =====================================================
       VALIDATE GRADES
    ====================================================== */

    foreach ($values as $value) {

        if (
            $value === "" ||
            !is_numeric($value) ||
            (float)$value < 0 ||
            (float)$value > 100
        ) {
            $valid = false;
            break;
        }


        $numericGrades[] = (float)$value;
    }


    /* =====================================================
       VERIFY TEACHER ASSIGNMENT
    ====================================================== */

    if ($valid) {

        $check = $conn->prepare(

            "SELECT ss.id
             FROM student_subjects ss

             INNER JOIN teacher_subjects ts
                ON ts.subject_id = ss.subject_id

             WHERE ss.id = ?
               AND ts.teacher_id = ?

             LIMIT 1"

        );


        if (!$check) {

            die(
                "Database error: "
                . $conn->error
            );

        }


        $check->bind_param(
            "ii",
            $enrollmentId,
            $teacherId
        );


        $check->execute();


        $result =
            $check->get_result();


        $allowed =
            $result->num_rows > 0;


        $check->close();


        if (!$allowed) {
            $valid = false;
        }
    }


    /* =====================================================
       SAVE GRADE
    ====================================================== */

    if (!$valid) {

        $message =
            "Please select a student/subject and enter four grades from 0 to 100.";

        $messageType = "error";

    } else {

        /* =================================================
           CALCULATE RAW AVERAGE INTERNALLY
           DO NOT DISPLAY IT.
        ================================================== */

        $average = round(

            (
                $numericGrades[0] +
                $numericGrades[1] +
                $numericGrades[2] +
                $numericGrades[3]
            ) / 4,

            2
        );


        /* =================================================
           DETERMINE GWA / REMARKS
        ================================================== */

        if ($gradeStatus === "udrl") {

            $gwa = "UDRL";

            $remarks =
                "Unofficially Dropped";

            /*
             * Since the database average column
             * may be numeric, store NULL for UDRL.
             */

            $databaseAverage = null;

        } else {

            $result =
                convertGWA($average);


            $gwa =
                $result["grade"];


            $remarks =
                $result["remarks"];


            $databaseAverage =
                (float)$gwa;
        }


        /* =================================================
           CHECK EXISTING GRADE
        ================================================== */

        $find = $conn->prepare(

            "SELECT id
             FROM grades
             WHERE student_subject_id = ?
             LIMIT 1"

        );


        if (!$find) {

            die(
                "Database error: "
                . $conn->error
            );

        }


        $find->bind_param(
            "i",
            $enrollmentId
        );


        $find->execute();


        $existing =
            $find
            ->get_result()
            ->fetch_assoc();


        $find->close();


        /* =================================================
           UPDATE EXISTING GRADE
        ================================================== */

        if ($existing) {

            $gradeId =
                (int)$existing["id"];


            $save = $conn->prepare(

                "UPDATE grades

                 SET
                    prelim = ?,
                    midterm = ?,
                    pre_final = ?,
                    final_grade = ?,
                    average = ?,
                    remarks = ?

                 WHERE id = ?"

            );


            if (!$save) {

                die(
                    "Database error: "
                    . $conn->error
                );

            }


            $save->bind_param(

                "ddddssi",

                $numericGrades[0],
                $numericGrades[1],
                $numericGrades[2],
                $numericGrades[3],
                $databaseAverage,
                $remarks,
                $gradeId

            );

        } else {

            /* =================================================
               INSERT NEW GRADE
            ================================================== */

            $save = $conn->prepare(

                "INSERT INTO grades
                (
                    student_subject_id,
                    prelim,
                    midterm,
                    pre_final,
                    final_grade,
                    average,
                    remarks
                )

                VALUES (?, ?, ?, ?, ?, ?, ?)"

            );


            if (!$save) {

                die(
                    "Database error: "
                    . $conn->error
                );

            }


            $save->bind_param(

                "iddddss",

                $enrollmentId,

                $numericGrades[0],
                $numericGrades[1],
                $numericGrades[2],
                $numericGrades[3],

                $databaseAverage,

                $remarks

            );
        }


        /* =================================================
           EXECUTE SAVE
        ================================================== */

        if ($save->execute()) {

            if ($existing) {

                $message =
                    "Grade updated successfully.";

            } else {

                $message =
                    "Grade saved successfully.";
            }

        } else {

            $message =
                "Unable to save the grade: "
                . $save->error;

            $messageType = "error";
        }


        $save->close();
    }
}


/* =========================================================
   LOAD STUDENTS AND SUBJECTS
========================================================= */

$enrollmentQuery = $conn->prepare(

    "SELECT

        ss.id AS enrollment_id,

        s.student_id,
        s.first_name,
        s.last_name,

        sub.subject_code,
        sub.subject_name

     FROM student_subjects ss

     INNER JOIN students s
        ON s.id = ss.student_id

     INNER JOIN subjects sub
        ON sub.id = ss.subject_id

     INNER JOIN teacher_subjects ts
        ON ts.subject_id = ss.subject_id

     WHERE ts.teacher_id = ?

     ORDER BY
        s.last_name,
        s.first_name,
        sub.subject_name"

);


if (!$enrollmentQuery) {

    die(
        "Database error: "
        . $conn->error
    );

}


$enrollmentQuery->bind_param(
    "i",
    $teacherId
);


$enrollmentQuery->execute();


$enrollments =
    $enrollmentQuery->get_result();


/* =========================================================
   LOAD SAVED GRADES
========================================================= */

$gradesQuery = $conn->prepare(

    "SELECT

        g.id,

        g.prelim,
        g.midterm,
        g.pre_final,
        g.final_grade,
        g.average,
        g.remarks,

        s.student_id,
        s.first_name,
        s.last_name,

        sub.subject_code,
        sub.subject_name

     FROM grades g

     INNER JOIN student_subjects ss
        ON ss.id = g.student_subject_id

     INNER JOIN students s
        ON s.id = ss.student_id

     INNER JOIN subjects sub
        ON sub.id = ss.subject_id

     INNER JOIN teacher_subjects ts
        ON ts.subject_id = ss.subject_id

     WHERE ts.teacher_id = ?

     ORDER BY
        s.last_name,
        sub.subject_name"

);


if (!$gradesQuery) {

    die(
        "Database error: "
        . $conn->error
    );

}


$gradesQuery->bind_param(
    "i",
    $teacherId
);


$gradesQuery->execute();


$savedGrades =
    $gradesQuery->get_result();


$teacherName =
    h(
        $_SESSION["full_name"] ?? "Teacher"
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

<title>
    Manage Grades
</title>


<style>

* {
    box-sizing: border-box;

    font-family:
        Arial,
        sans-serif;
}


body {

    margin: 0;

    padding: 30px;

    background:
        #f4f7fb;

    color:
        #222;
}


.container {

    max-width:
        1300px;

    margin:
        auto;
}


.panel {

    background:
        white;

    padding:
        25px;

    margin-bottom:
        22px;

    border-radius:
        12px;

    box-shadow:
        0 3px 12px
        rgba(0,0,0,.07);
}


.back {

    display:
        inline-block;

    margin-bottom:
        18px;

    color:
        #205c4a;

    text-decoration:
        none;

    font-weight:
        bold;
}


h1,
h2 {

    color:
        #205c4a;
}


label {

    display:
        block;

    margin:
        15px 0 7px;

    font-weight:
        bold;
}


select,
input {

    width:
        100%;

    padding:
        11px;

    border:
        1px solid #ccc;

    border-radius:
        6px;
}


.grade-fields {

    display:
        grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap:
        12px;
}


button {

    margin-top:
        20px;

    padding:
        12px 22px;

    background:
        #205c4a;

    color:
        white;

    border:
        none;

    border-radius:
        7px;

    cursor:
        pointer;

    font-size:
        15px;
}


button:hover {

    background:
        #327861;
}


.message {

    padding:
        13px;

    border-radius:
        7px;

    margin-bottom:
        18px;

    background:
        #e0f4e8;

    color:
        #205c4a;
}


.error {

    background:
        #fde8e7;

    color:
        #a12622;
}


.table-wrapper {

    overflow-x:
        auto;
}


table {

    width:
        100%;

    border-collapse:
        collapse;

    margin-top:
        15px;
}


th,
td {

    padding:
        12px;

    border-bottom:
        1px solid #ddd;

    text-align:
        left;

    white-space:
        nowrap;
}


th {

    background:
        #205c4a;

    color:
        white;
}


tr:hover {

    background:
        #f1f7f4;
}


.edit-button {

    margin:
        0;

    padding:
        8px 12px;
}


.gwa {

    font-weight:
        bold;

    font-size:
        16px;
}


.failed {

    color:
        #b00020;

    font-weight:
        bold;
}


.inc {

    color:
        #d17a00;

    font-weight:
        bold;
}


.udrl {

    color:
        #7b1fa2;

    font-weight:
        bold;
}


.passed {

    color:
        #20843b;

    font-weight:
        bold;
}


.gwa-box {

    background:
        #f1f7f4;

    border:
        2px solid #205c4a;

    font-weight:
        bold;

    font-size:
        17px;
}


@media (max-width: 650px) {

    body {
        padding:
            12px;
    }


    .grade-fields {

        grid-template-columns:
            1fr;
    }

}

</style>

</head>


<body>


<div class="container">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="panel">

        <a
            class="back"
            href="teacher_dashboard.php"
        >
            &larr; Back to Dashboard
        </a>


        <h1>
            Manage Grades
        </h1>


        <p>
            Welcome,
            <?php echo $teacherName; ?>!
        </p>


        <p>
            Enter and manage your students' grades.
        </p>

    </div>



    <!-- =================================================
         ENTER GRADES
    ================================================== -->

    <div class="panel">

        <h2>
            Enter Student Grades
        </h2>


        <?php if ($message !== ""): ?>

            <div
                class="message
                <?php
                    echo
                        $messageType === "error"
                        ? "error"
                        : "";
                ?>"
            >

                <?php
                    echo h($message);
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="teacher_grades.php"
        >


            <!-- STUDENT / SUBJECT -->

            <label
                for="student_subject_id"
            >
                Student and Subject
            </label>


            <select
                name="student_subject_id"
                id="student_subject_id"
                required
            >

                <option value="">
                    -- Select Student and Subject --
                </option>


                <?php
                while (
                    $enrollment =
                        $enrollments->fetch_assoc()
                ):
                ?>

                    <option
                        value="<?php
                            echo
                                (int)
                                $enrollment[
                                    "enrollment_id"
                                ];
                        ?>"
                    >

                        <?php
                        echo h(

                            $enrollment[
                                "student_id"
                            ]

                            . " - "

                            . $enrollment[
                                "first_name"
                            ]

                            . " "

                            . $enrollment[
                                "last_name"
                            ]

                            . " | "

                            . $enrollment[
                                "subject_code"
                            ]

                            . " - "

                            . $enrollment[
                                "subject_name"
                            ]

                        );
                        ?>

                    </option>

                <?php endwhile; ?>

            </select>



            <!-- =================================================
                 GRADE STATUS
            ================================================== -->

            <label
                for="grade_status"
            >
                Grade Status
            </label>


            <select
                name="grade_status"
                id="grade_status"
            >

                <option
                    value="normal"
                >
                    Normal
                </option>


                <option
                    value="udrl"
                >
                    UDRL - Unofficially Dropped
                </option>

            </select>



            <!-- =================================================
                 NUMERICAL GRADES
            ================================================== -->

            <div
                class="grade-fields"
                id="numeric_grade_section"
            >


                <div>

                    <label
                        for="prelim"
                    >
                        Prelim (0–100)
                    </label>


                    <input
                        type="number"
                        name="prelim"
                        id="prelim"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                    >

                </div>



                <div>

                    <label
                        for="midterm"
                    >
                        Midterm (0–100)
                    </label>


                    <input
                        type="number"
                        name="midterm"
                        id="midterm"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                    >

                </div>



                <div>

                    <label
                        for="pre_final"
                    >
                        Pre-Final (0–100)
                    </label>


                    <input
                        type="number"
                        name="pre_final"
                        id="pre_final"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                    >

                </div>



                <div>

                    <label
                        for="final_grade"
                    >
                        Final Grade (0–100)
                    </label>


                    <input
                        type="number"
                        name="final_grade"
                        id="final_grade"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                    >

                </div>


            </div>



            <!-- =================================================
                 GWA
            ================================================== -->

            <label
                for="gwa_preview"
            >
                GWA
            </label>


            <input
                type="text"
                id="gwa_preview"
                class="gwa-box"
                placeholder="GWA will appear automatically"
                readonly
            >



            <!-- =================================================
                 REMARKS
            ================================================== -->

            <label
                for="remarks_preview"
            >
                Remarks
            </label>


            <input
                type="text"
                id="remarks_preview"
                placeholder="Automatically calculated"
                readonly
            >


            <input
                type="hidden"
                name="remarks"
                id="remarks"
            >


            <button
                type="submit"
            >
                Save Grade
            </button>


        </form>

    </div>



    <!-- =================================================
         SAVED GRADES
    ================================================== -->

    <div class="panel">

        <h2>
            Saved Student Grades
        </h2>


        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            Student ID
                        </th>

                        <th>
                            Student Name
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
                            Pre-Final
                        </th>

                        <th>
                            Final Grade
                        </th>

                        <th>
                            GWA
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


                <?php if (
                    $savedGrades->num_rows > 0
                ): ?>


                    <?php
                    while (
                        $grade =
                            $savedGrades->fetch_assoc()
                    ):
                    ?>


                        <?php

                        /*
                         * If remarks are UDRL,
                         * display UDRL.
                         */

                        if (
                            $grade["remarks"] ===
                            "Unofficially Dropped"
                        ) {

                            $gwa =
                                "UDRL";

                            $displayRemarks =
                                "Unofficially Dropped";

                            $remarksClass =
                                "udrl";

                        } else {

                            /*
                             * Recalculate from the
                             * four numerical grades.
                             *
                             * Raw average is NOT displayed.
                             */

                            $average = round(

                                (
                                    (float)$grade["prelim"] +
                                    (float)$grade["midterm"] +
                                    (float)$grade["pre_final"] +
                                    (float)$grade["final_grade"]
                                ) / 4,

                                2
                            );


                            $converted =
                                convertGWA(
                                    $average
                                );


                            $gwa =
                                $converted["grade"];


                            $displayRemarks =
                                $converted["remarks"];


                            $remarksClass =
                                "";


                            if (
                                $displayRemarks ===
                                "Failed"
                            ) {

                                $remarksClass =
                                    "failed";

                            } elseif (
                                $displayRemarks ===
                                "INC"
                            ) {

                                $remarksClass =
                                    "inc";

                            } else {

                                $remarksClass =
                                    "passed";
                            }

                        }

                        ?>


                        <tr>


                            <!-- STUDENT ID -->

                            <td>

                                <?php
                                echo h(
                                    $grade[
                                        "student_id"
                                    ]
                                );
                                ?>

                            </td>


                            <!-- STUDENT NAME -->

                            <td>

                                <?php
                                echo h(

                                    $grade[
                                        "first_name"
                                    ]

                                    . " "

                                    . $grade[
                                        "last_name"
                                    ]

                                );
                                ?>

                            </td>


                            <!-- SUBJECT -->

                            <td>

                                <?php
                                echo h(

                                    $grade[
                                        "subject_code"
                                    ]

                                    . " - "

                                    . $grade[
                                        "subject_name"
                                    ]

                                );
                                ?>

                            </td>


                            <!-- PRELIM -->

                            <td>

                                <?php
                                echo h(
                                    $grade[
                                        "prelim"
                                    ]
                                );
                                ?>

                            </td>


                            <!-- MIDTERM -->

                            <td>

                                <?php
                                echo h(
                                    $grade[
                                        "midterm"
                                    ]
                                );
                                ?>

                            </td>


                            <!-- PRE-FINAL -->

                            <td>

                                <?php
                                echo h(
                                    $grade[
                                        "pre_final"
                                    ]
                                );
                                ?>

                            </td>


                            <!-- FINAL -->

                            <td>

                                <?php
                                echo h(
                                    $grade[
                                        "final_grade"
                                    ]
                                );
                                ?>

                            </td>


                            <!-- GWA -->

                            <td
                                class="gwa"
                            >

                                <?php
                                echo h(
                                    $gwa
                                );
                                ?>

                            </td>


                            <!-- REMARKS -->

                            <td
                                class="<?php
                                    echo h(
                                        $remarksClass
                                    );
                                ?>"
                            >

                                <?php
                                echo h(
                                    $displayRemarks
                                );
                                ?>

                            </td>


                            <!-- EDIT -->

                            <td>

                                <button
                                    type="button"
                                    class="edit-button"
                                    onclick="editGrade(
                                        <?php
                                        echo json_encode(
                                            (string)$grade[
                                                'prelim'
                                            ]
                                        );
                                        ?>,

                                        <?php
                                        echo json_encode(
                                            (string)$grade[
                                                'midterm'
                                            ]
                                        );
                                        ?>,

                                        <?php
                                        echo json_encode(
                                            (string)$grade[
                                                'pre_final'
                                            ]
                                        );
                                        ?>,

                                        <?php
                                        echo json_encode(
                                            (string)$grade[
                                                'final_grade'
                                            ]
                                        );
                                        ?>"
                                >

                                    Edit

                                </button>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="10"
                        >

                            No grades have been
                            saved for your assigned
                            subjects.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>


        </div>

    </div>


</div>



<script>

/* =========================================================
   GRADE INPUTS
========================================================= */

const prelimInput =
    document.getElementById("prelim");

const midtermInput =
    document.getElementById("midterm");

const preFinalInput =
    document.getElementById("pre_final");

const finalInput =
    document.getElementById("final_grade");

const gwaPreview =
    document.getElementById("gwa_preview");

const remarksPreview =
    document.getElementById("remarks_preview");

const remarksHidden =
    document.getElementById("remarks");

const gradeStatus =
    document.getElementById("grade_status");

const numericGradeSection =
    document.getElementById(
        "numeric_grade_section"
    );


/* =========================================================
   GWA CONVERSION
========================================================= */

function convertGWA(average)
{
    average = Number(average);


    /* 97.5 - 100 = 1.00 */

    if (average >= 97.5) {

        return {
            grade: "1.00",
            remarks: "Passed"
        };

    }


    /* 96.5 - 97.4 = 1.20 */

    if (average >= 96.5) {

        return {
            grade: "1.20",
            remarks: "Passed"
        };

    }


    /* 95.5 - 96.4 = 1.25 */

    if (average >= 95.5) {

        return {
            grade: "1.25",
            remarks: "Passed"
        };

    }


    /* 94.5 - 95.4 = 1.30 */

    if (average >= 94.5) {

        return {
            grade: "1.30",
            remarks: "Passed"
        };

    }


    /* 93.5 - 94.4 = 1.40 */

    if (average >= 93.5) {

        return {
            grade: "1.40",
            remarks: "Passed"
        };

    }


    /* 92.5 - 93.4 = 1.50 */

    if (average >= 92.5) {

        return {
            grade: "1.50",
            remarks: "Passed"
        };

    }


    /* 91.5 - 92.4 = 1.60 */

    if (average >= 91.5) {

        return {
            grade: "1.60",
            remarks: "Passed"
        };

    }


    /* 90.5 - 91.4 = 1.70 */

    if (average >= 90.5) {

        return {
            grade: "1.70",
            remarks: "Passed"
        };

    }


    /* 89.5 - 90.4 = 1.75 */

    if (average >= 89.5) {

        return {
            grade: "1.75",
            remarks: "Passed"
        };

    }


    /* 88.5 - 89.4 = 1.80 */

    if (average >= 88.5) {

        return {
            grade: "1.80",
            remarks: "Passed"
        };

    }


    /* 87.5 - 88.4 = 1.90 */

    if (average >= 87.5) {

        return {
            grade: "1.90",
            remarks: "Passed"
        };

    }


    /* 86.5 - 87.4 = 2.00 */

    if (average >= 86.5) {

        return {
            grade: "2.00",
            remarks: "Passed"
        };

    }


    /* 85.5 - 86.4 = 2.10 */

    if (average >= 85.5) {

        return {
            grade: "2.10",
            remarks: "Passed"
        };

    }


    /* 84.5 - 85.4 = 2.20 */

    if (average >= 84.5) {

        return {
            grade: "2.20",
            remarks: "Passed"
        };

    }


    /* 83.5 - 84.4 = 2.25 */

    if (average >= 83.5) {

        return {
            grade: "2.25",
            remarks: "Passed"
        };

    }


    /* 82.5 - 83.4 = 2.30 */

    if (average >= 82.5) {

        return {
            grade: "2.30",
            remarks: "Passed"
        };

    }


    /* 81.5 - 82.4 = 2.40 */

    if (average >= 81.5) {

        return {
            grade: "2.40",
            remarks: "Passed"
        };

    }


    /* 80.5 - 81.4 = 2.50 */

    if (average >= 80.5) {

        return {
            grade: "2.50",
            remarks: "Passed"
        };

    }


    /* 79.5 - 80.4 = 2.60 */

    if (average >= 79.5) {

        return {
            grade: "2.60",
            remarks: "Passed"
        };

    }


    /* 78.5 - 79.4 = 2.70 */

    if (average >= 78.5) {

        return {
            grade: "2.70",
            remarks: "Passed"
        };

    }


    /* 77.5 - 78.4 = 2.75 */

    if (average >= 77.5) {

        return {
            grade: "2.75",
            remarks: "Passed"
        };

    }


    /* 76.5 - 77.4 = 2.80 */

    if (average >= 76.5) {

        return {
            grade: "2.80",
            remarks: "Passed"
        };

    }


    /* 75.5 - 76.4 = 2.90 */

    if (average >= 75.5) {

        return {
            grade: "2.90",
            remarks: "Passed"
        };

    }


    /* 74.5 - 75.4 = 3.00 */

    if (average >= 74.5) {

        return {
            grade: "3.00",
            remarks: "Passed"
        };

    }


    /* 69.5 - 74.4 = INC / 5.00 */

    if (average >= 69.5) {

        return {
            grade: "5.00",
            remarks: "INC"
        };

    }


    /* 0 - 69.4 = FAILED */

    return {
        grade: "5.00",
        remarks: "Failed"
    };
}


/* =========================================================
   AUTOMATIC GWA CALCULATION
========================================================= */

function calculateGWA()
{

    /*
     * If UDRL is selected,
     * don't calculate a numerical GWA.
     */

    if (gradeStatus.value === "udrl") {

        gwaPreview.value =
            "UDRL";

        remarksPreview.value =
            "Unofficially Dropped";

        remarksHidden.value =
            "Unofficially Dropped";

        return;
    }


    const values = [

        prelimInput.value.trim(),

        midtermInput.value.trim(),

        preFinalInput.value.trim(),

        finalInput.value.trim()

    ];


    /*
     * Wait until all grades
     * are entered.
     */

    if (
        values.some(
            value => value === ""
        )
    ) {

        gwaPreview.value = "";

        remarksPreview.value = "";

        remarksHidden.value = "";

        return;
    }


    /*
     * Convert to numbers.
     */

    const grades =
        values.map(
            value => Number(value)
        );


    /*
     * Validate.
     */

    if (
        grades.some(
            grade =>
                !Number.isFinite(grade) ||
                grade < 0 ||
                grade > 100
        )
    ) {

        gwaPreview.value =
            "Invalid";

        remarksPreview.value =
            "Invalid grade";

        remarksHidden.value =
            "Invalid grade";

        return;
    }


    /*
     * Calculate raw average internally.
     *
     * IMPORTANT:
     * This value is NEVER displayed.
     */

    const average = (

        grades[0] +
        grades[1] +
        grades[2] +
        grades[3]

    ) / 4;


    /*
     * Round internally to two decimals.
     */

    const roundedAverage =
        Number(
            average.toFixed(2)
        );


    /*
     * Convert to GWA.
     */

    const result =
        convertGWA(
            roundedAverage
        );


    /*
     * Display GWA.
     */

    gwaPreview.value =
        result.grade;


    /*
     * Display remarks.
     */

    remarksPreview.value =
        result.remarks;


    /*
     * Store remarks.
     */

    remarksHidden.value =
        result.remarks;
}


/* =========================================================
   WATCH GRADE INPUTS
========================================================= */

prelimInput.addEventListener(
    "input",
    calculateGWA
);

midtermInput.addEventListener(
    "input",
    calculateGWA
);

preFinalInput.addEventListener(
    "input",
    calculateGWA
);

finalInput.addEventListener(
    "input",
    calculateGWA
);


/* =========================================================
   WATCH UDRL STATUS
========================================================= */

gradeStatus.addEventListener(
    "change",
    function () {

        if (
            this.value === "udrl"
        ) {

            /*
             * UDRL does not need
             * numerical grades.
             */

            numericGradeSection
                .style
                .opacity = "0.5";


            /*
             * Remove required temporarily.
             */

            prelimInput.required = false;
            midtermInput.required = false;
            preFinalInput.required = false;
            finalInput.required = false;


            calculateGWA();

        } else {

            numericGradeSection
                .style
                .opacity = "1";


            /*
             * Numerical grades
             * are required again.
             */

            prelimInput.required = true;
            midtermInput.required = true;
            preFinalInput.required = true;
            finalInput.required = true;


            calculateGWA();
        }

    }
);


/* =========================================================
   EDIT GRADE
========================================================= */

function editGrade(
    prelim,
    midterm,
    preFinal,
    finalGrade
)
{

    /*
     * Make sure status is normal.
     */

    gradeStatus.value =
        "normal";


    numericGradeSection
        .style
        .opacity = "1";


    prelimInput.required = true;
    midtermInput.required = true;
    preFinalInput.required = true;
    finalInput.required = true;


    /*
     * Put existing grades
     * into the form.
     */

    prelimInput.value =
        prelim;

    midtermInput.value =
        midterm;

    preFinalInput.value =
        preFinal;

    finalInput.value =
        finalGrade;


    /*
     * Automatically
     * recalculate GWA.
     */

    calculateGWA();


    /*
     * Scroll to form.
     */

    document
        .querySelector("form")
        .scrollIntoView({
            behavior: "smooth"
        });

}

</script>


</body>

</html>


<?php

/* =========================================================
   CLOSE DATABASE RESOURCES
========================================================= */

$enrollmentQuery->close();

$gradesQuery->close();

$conn->close();

?>