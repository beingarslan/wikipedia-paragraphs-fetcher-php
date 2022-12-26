<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <title>Wikipedia Article</title>
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center mb-5">Wikipedia Article</h1>
        <!-- msg box -->
        <div id="msg"></div>
        <form method="post" id="form">
            <div class="form-group">
                <label for="url">Wikipedia URL</label>
                <input type="text" class="form-control" id="url" name="url" placeholder="Enter Wikipedia URL">
            </div>
            <button type="submit" class="btn btn-primary mt-2">Submit</button>
        </form>
        <hr>
        <h2 id="title"></h2>
        <div id="paragraph"></div>
        <div id="images"></div>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ajaxy/1.6.1/scripts/jquery.ajaxy.min.js" integrity="sha512-bztGAvCE/3+a1Oh0gUro7BHukf6v7zpzrAb3ReWAVrt+bVNNphcl2tDTKCBr5zk7iEDmQ2Bv401fX3jeVXGIcA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js" integrity="sha512-rstIgDs0xPgmG6RX1Aba4KV5cWJbAMcvRCVmglpam9SoHZiUCyQVDdH2LPlxoHtrv17XWblE/V/PP+Tr04hbtA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        $(document).ready(function() {
            $('#form').validate({
                rules: {
                    url: {
                        required: true,
                        url: true
                    }
                },
                messages: {
                    url: {
                        required: "Please enter a Wikipedia URL",
                        url: "Please enter a valid Wikipedia URL"
                    }
                },
                submitHandler: function(form) {
                    $.ajax({
                        url: 'fetchData.php',
                        type: 'POST',
                        data: $(form).serialize(),
                        success: function(response) {
                            console.log(response);
                            if (!response.response) {
                                $('#msg').html('<div class="alert alert-danger" role="alert">' + response.error + '</div>');
                            } else {
                                $('#msg').html('<div class="alert alert-success" role="alert">Data fetched successfully</div>');

                                $('#title').html(response.title);
                                $('#paragraph').html(response.paragraph);
                                var images = response.images;
                                var html = '';
                                for (var i = 0; i < images.length; i++) {
                                    // image with download option
                                    html += '<a href="' + images[i] + '" download target="_blank"><img src="' + images[i] + '" class="img-fluid" style="width: 200px; height: 200px;"></a>';
                                }
                                $('#images').html(html);
                            }
                        },
                        error: function(error) {
                            console.log(error);
                            $('#msg').html('<div class="alert alert-danger" role="alert">Something went wrong</div>');
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>