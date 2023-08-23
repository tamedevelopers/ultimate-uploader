<?php

namespace ultimateUploader\Traits;

/***********************************************************
* #### PHP - Ultimate Image Uploader Class ####
***********************************************************/

trait ProcessorTrait{
    
    /**
     * @param  string base directory path.
     * @return string|void|null 
     */
    protected function optionDir(?string $dir = null)
    {
        if(empty($dir)){
            // for laravel or other framework that supports public path func
            if (function_exists('public_path')) {
                $dir = $this->clean_path(public_path());
            }else{
                // get default project root document path
                $dir = $this->clean_path(realpath("."));
            }
        }else{
            $dir = $this->clean_path($dir);
        }

        return $dir;
    }

    /**
     * Generate new file data
     * @param string  $extension image/file extension
     * @return array
     */
    private function generateNewFileData($extension)
    {
        $new_name = $this->timeBaseFolder()['now'] . str_shuffle(substr(md5(rand(1000000000, 999999999)), 0, 15));
        return ['name' => strtolower("{$new_name}.$extension"), 'extension' => $extension];
    }

    /**
     * remove uploaded files
     */
    private function removeUploadedFile($folder_upload)
    {
        if(is_array($folder_upload)){
            foreach($folder_upload as $key => $value)
            {
                if (file_exists($this->base_dir . $value)) {
                    @unlink($this->base_dir . $value);
                }
            }
        }
    }

    /**
     * @param  string $path url path.
     * @return string|null
     */
    protected function optionUrl(?string $path = null)
    {
        if(empty($path)){
            $path = $this->getURL();
        }

        return $path;
    } 

    /**
     * Get url real path
     *
     * @param  mixed $atRoot
     * @param  mixed $atCore
     * @param  mixed $parse
     * @return string|null
     */
    private function getURL($atRoot = false, $atCore = false, $parse = false)
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $http       = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
            $hostname   = $_SERVER['HTTP_HOST'];
            $dir        =  str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

            $core       = preg_split('@/@', str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(dirname(__FILE__))), PREG_SPLIT_NO_EMPTY);
            $core       = $core[0];

            $tmplt      = $atRoot ? ($atCore ? "%s://%s/%s/" : "%s://%s/") : ($atCore ? "%s://%s/%s/" : "%s://%s%s");
            $end        = $atRoot ? ($atCore ? $core : $hostname) : ($atCore ? $core : $dir);
            $base_url   = sprintf( $tmplt, $http, $hostname, $end );
        }
        else $base_url  = 'http://localhost/';

        if ($parse) {
            $base_url   = parse_url($base_url);
            if (isset($base_url['path'])) if ($base_url['path'] == '/') $base_url['path'] = '';
        }
        return $base_url;
    }

    /**
     * Image watermarks
     * @param  string $stamp path to watermark image 
     * Example is --- assets/img/wateramrk.png | no need for base dir or full image path
     * @param  float|int @marge_right position of watermark margin right
     * @param  float|int @marge_bottom position of watermark margin bottom
     * @param  boolean @waterMark default is set to false, set to true to execute the watermark.
     * @return void|null 
     */
    public function waterMark($stamp, $marge_right = 50, $marge_bottom = 50, $waterMark = false)
    {
        //file does not exists
        if(!file_exists($this->base_dir . $stamp) || is_dir($this->base_dir . $stamp))
            return;
        
        // Load the watermark stamp 
        @$stamp = @imagecreatefrompng($this->base_dir . $stamp);

        if(!is_null($this->folder) && $waterMark){
            //Loop through folder
            foreach($this->folder as $key => $folder_val)
            {
                //Loop through image
                foreach($this->image as $i_key => $i_val)
                {
                    //Get file extension
                    $file_extension = strtolower(pathinfo($i_val, PATHINFO_EXTENSION));

                    //path to destination image
                    if($file_extension == 'png'){
                        $im = @imagecreatefrompng($folder_val);
                    }else{
                        if($file_extension == 'webp'){
                            $im = @imagecreatefromwebp($folder_val);
                        }else{
                            $im = @imagecreatefromjpeg($folder_val);
                        }
                    }

                    // Set the margins for the stamp and get the height/width of the stamp image
                    $marge_right = $marge_right;
                    $marge_bottom = $marge_bottom;
                    @$sx = imagesx($stamp); //height -x
                    @$sy = imagesy($stamp); //width -y

                    // Copy the stamp image onto our photo using the margin offsets and the photo 
                    // width to calculate positioning of the stamp. 
                    @imagecopy($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));

                    // Output and free memory
                    //header('Content-type: image/png');
                    //imagepng($im);

                    if($file_extension == 'png'){
                        @imagepng($im, $folder_val, 8, PNG_FILTER_AVG);
                    }else{
                        if($file_extension == 'webp'){
                            @imagewebp($im, $folder_val, 100);
                        }else{
                            @imagejpeg($im, $folder_val, 90);
                        }
                    }
                    //Free up memory
                    imagedestroy($im);
                }
            }
        }
    }

    /**
     * Image resize
     * @param  float|int @crop_width width
     * @param  float|int @crop_height height
     * @param  boolean @autoResize default is set to false, set to true to execute the resize.
     * @return void|null 
     */
    public function imageAutoResize($crop_width = null, $crop_height = null, $autoResize = false)
    {
        if(!is_null($this->folder) && $autoResize){
            //Loop through the image data arrays
            foreach($this->folder as $key => $folder_val)
            {
                //Loop through image
                foreach($this->image as $i_key => $i_val)
                {
                    //Get file extension
                    $file_extension = strtolower(pathinfo($i_val, PATHINFO_EXTENSION));

                    //path to destination image
                    if($file_extension == 'png'){
                        $new = @imagecreatefrompng("$this->settings/$i_val");
                    }else{
                        if($file_extension == 'webp'){
                            $new = @imagecreatefromwebp("$this->settings/$i_val");
                        }else{
                            $new = @imagecreatefromjpeg("$this->settings/$i_val");
                        }
                    }

                    //Get the image size from the image
                    $get_width = imagesy($new); //width -y
                    $get_height = imagesx($new); //height -x

                    //get the min length from both size
                    $size = min($get_width, $get_height);
                    //$size = min($crop_width, $crop_height);

                    //Start cropping
                    if($get_width >= $get_height) {
                        $newy = ($get_width - $get_height)/2;
                        $im2 = imagecrop($new, ['x' => 0, 'y' => $newy, 'width' => $size, 'height' => $size]);
                    }
                    else {
                        $newx = ($get_height - $get_width)/2;
                        $im2 = imagecrop($new, ['x' => $newx, 'y' => 0, 'width' => $size, 'height' => $size]);
                    }

                    //Finish image crop
                    if($file_extension == 'png'){
                        imagepng($im2, "$this->settings/$i_val", 8, PNG_FILTER_AVG);
                    }else{
                        if($file_extension == 'webp'){
                            @imagewebp($im2, "$this->settings/$i_val", 100);
                        }else{
                            @imagejpeg($im2, "$this->settings/$i_val", 90);
                        }
                    }
                    //Free up memory
                    imagedestroy($im2);
                }
            }
        }
    }

}