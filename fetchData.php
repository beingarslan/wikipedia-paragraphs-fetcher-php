<?php
header("Content-Type: application/json; charset=UTF-8");

// Connect to the MySQL database
include "db.php";

try {

    // Check if the article is already in the database
    $url = $_POST['url'];
    $sql = "SELECT * FROM articles WHERE url='$url'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        // The article is already in the database, so display a message
        $response = array("error" => "This article is already in the database.", "response" => false);
        echo json_encode($response);
        exit;
    }

    // Fetch the first paragraph of the article and the title using the Wikipedia API
    $page_name = substr($url, strrpos($url, '/') + 1);
    $api_url = "https://en.wikipedia.org/api/rest_v1/page/summary/$page_name";
    $data = file_get_contents($api_url);
    $json = json_decode($data, true);

    $title = $json['title'];
    $paragraph = $json['extract'];

    // Download the images and get their paths
    $images = array();
    if (is_array($json['thumbnail']['source'])) {
        foreach ($json['thumbnail']['source'] as $image_url) {
            // Generate a unique file name for the image
            $image_path = uniqid() . ".jpg";

            // Download the image and save it to the server
            file_put_contents($image_path, file_get_contents($image_url));

            // Add the image path to the array
            $images[] = $image_path;
        }
    }

    // Escape the values for insertion into the database
    $title = mysqli_real_escape_string($conn, $title);
    $paragraph = mysqli_real_escape_string($conn, $paragraph);
    $url = mysqli_real_escape_string($conn, $url);
    $images = mysqli_real_escape_string($conn, implode(",", $images));

    // Insert the article data into the database
    $sql = "INSERT INTO articles (title, paragraph, url, images) VALUES ('$title', '$paragraph', '$url', '$images')";

    $data = array();
    $data = $json;

    if (mysqli_query($conn, $sql)) {
        $response = array(
            "success" => "Article added successfully.",
            "title" => $title,
            "paragraph" => $paragraph,
            "url" => $url,
            "images" => $images,
            "response" => true
        );
        echo json_encode($response);
    } else {
        $response = array("error" => "Error: " . $sql . "<br>" . mysqli_error($conn),  "response" => false);
        echo json_encode($response);
    }

    mysqli_close($conn);
} catch (\Throwable $th) {
    //throw $th;
    $response = array("error" => "Error: " . $th,  "response" => false);
    echo json_encode($response);
}
