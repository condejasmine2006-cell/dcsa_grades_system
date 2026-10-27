<?php

$host = "localhost";
$dbUser = "root";
$dbPassword = "";
$dbName = "dcsa_grades";

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

try {

    $conn = new mysqli(
        $host,
        $dbUser,
        $dbPassword,
        $dbName
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {

    die(
        "Database connection failed: " .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            "UTF-8"
        )
    );
}

?>