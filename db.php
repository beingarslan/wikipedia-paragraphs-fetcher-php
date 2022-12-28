<?php

$host = "menu-app-db-do-user-12682515-0.b.db.ondigitalocean.com:25060";
$user = "doadmin";
$password = "AVNS_QtWQVwd3swI-_-D_-Ul";
$dbname = "wikipedia-paragraphs-fetcher";

$conn = mysqli_connect($host, $user, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");