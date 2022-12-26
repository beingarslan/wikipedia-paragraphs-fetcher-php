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
    // extractLanguageCode form url
    $languageCode = substr($url, 8, 2);
    // get page content
    $api_url_media = "https://$languageCode.wikipedia.org/api/rest_v1/page/media-list/$page_name";
    $api_url = "https://$languageCode.wikipedia.org/api/rest_v1/page/summary/$page_name";
    $data_media = file_get_contents($api_url_media);
    $json_media = json_decode($data_media, true);
    $data = file_get_contents($api_url);
    $json = json_decode($data, true);
    // echo json_encode($json);

    $title = $json['title'];
    $paragraph = $json['extract'];

    // Download the images and get their paths
    $images = array();
    if (is_array($json_media['items'])) {
        $i = 0;
        foreach ($json_media['items'] as $item) {
            if ($item['type'] == 'image') {
                if ($i++ >= 3) {
                    break;
                }
                $image_url = 'https:' . $item['srcset'][0]['src'];
                $images[] = $image_url;
            }
        }
    }

    // Escape the values for insertion into the database
    $title = mysqli_real_escape_string($conn, $title);
    $paragraph = mysqli_real_escape_string($conn, $paragraph);
    $url = mysqli_real_escape_string($conn, $url);
    $images_store = mysqli_real_escape_string($conn, implode(",", $images));

    // Insert the article data into the database
    $sql = "INSERT INTO articles (title, paragraph, url, images) VALUES ('$title', '$paragraph', '$url', '$images_store')";


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
