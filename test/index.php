<?php

//on using comoposer autoload
include_once __DIR__  . "/path_to/vendor/autoload.php";

//instantiate class
$upload = new ultimateUploader\ultimateUploader();
$upload->setDir(__DIR__ . "/..");

$upload->run('avatar', 'default', 'upload', 'images', '1.5mb', 2)
    ->error(function($response){
        
        //error message
        echo $response->data['message'];
    })->success(function($response){

        //run auto resize
        $response->imageAutoResize(200, 100, false);

        //run watermark
        $response->waterMark('watermark.png', '50', '100', true); //Add watermark automatically

        //run compression
        $response->compress(true); //will replace original to compressred -v


        //uplodaded image data
        // $response->data;

        var_dump( $response->get() );
        var_dump( $response->first() );
    });

    
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Document</title>
</head>
<body>
<center>
    <form method="post" enctype="multipart/form-data">
        
            <h3 class="valign-wrapper prod_hding_main mb-3">Upload file</h3>
            
            <!--file upload-->
            <div class="col-sm-12 mt-3">
                <div class="form-group">
                    <label for="upload">Image</label>
                    <input type="file" class="form-control-file" id="upload" 
                            name="avatar[]" multiple>
                </div>
            </div>

            <button type="submit" style="margin-top: 40px;">
                Upload File
            </button>
        
    </form>
</center>
</body>
</html>

