<?php
header("Content-Type: application/json; charset=UTF-8");

// Connect to the MySQL database
include "db.php";

function url_get_contents($url, $useragent='cURL', $headers=false, $follow_redirects=true, $debug=false) {

    // initialise the CURL library
    $ch = curl_init();

    // specify the URL to be retrieved
    curl_setopt($ch, CURLOPT_URL,$url);

    // we want to get the contents of the URL and store it in a variable
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

    // specify the useragent: this is a required courtesy to site owners
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);

    // ignore SSL errors
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // return headers as requested
    if ($headers==true){
        curl_setopt($ch, CURLOPT_HEADER,1);
    }

    // only return headers
    if ($headers=='headers only') {
        curl_setopt($ch, CURLOPT_NOBODY ,1);
    }

    // follow redirects - note this is disabled by default in most PHP installs from 4.4.4 up
    if ($follow_redirects==true) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
    }

    // if debugging, return an array with CURL's debug info and the URL contents
    if ($debug==true) {
        $result['contents']=curl_exec($ch);
        $result['info']=curl_getinfo($ch);
    }

    // otherwise just return the contents as a variable
    else $result=curl_exec($ch);

    // free resources
    curl_close($ch);

    // send back the data
    return $result;
}

try {

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
    $data_media = url_get_contents($api_url_media);
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
    // $images_store = mysqli_real_escape_string($conn, implode(",", $images));

    // variable for the path of the images folder must end with '/'
    $path = "images/";

    // saving images into storage
    $image_paths = array();
    foreach($images as $image)
    {
        $ext = pathinfo($image, PATHINFO_EXTENSION);
        $file_name = $path . uniqid() . '.' . $ext;
        file_put_contents($file_name, url_get_contents($image));
        $image_paths[] = $file_name;
    }

    // Insert the article data into the database
    $sql = "INSERT INTO articles (title, paragraph, url) VALUES ('$title', '$paragraph', '$url')";

    // getting the article id and inserting into images table
    $flag = true;
    if (count($images)) {
        if (mysqli_query($conn, $sql)) {
            $article_id = $conn->insert_id;
            $flag = false;
            foreach ($image_paths as $image) {
                $sql = "INSERT INTO article_images (article_id, image_url) VALUES ('$article_id', '$image')";
                if (mysqli_query($conn, $sql)) {
                    $flag = true;
                    continue;
                } else {
                    $flag = false;
                    $response = array("error" => "Error: " . $sql . "<br>" . mysqli_error($conn), "response" => false);
                    echo json_encode($response);
                }
            }
        }

        
    }

    if ($flag) {
        $response = array(
            "success" => "Article added successfully.",
            "title" => strip_tags($title),
            "paragraph" => strip_tags($paragraph),
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