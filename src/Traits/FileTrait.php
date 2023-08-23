<?php

namespace ultimateUploader\Traits;

/***********************************************************
* #### PHP - Ultimate Image Uploader Class ####
***********************************************************/


/**
 * @property $mimeType
 * @property $extensionType
 */
trait FileTrait{
    

    /**
     * file text formatting
     */
    private function fileText($count = 0)
    {
        return $count > 1 ? 'files' : 'file';
    }

    /**
     * Get image width and height
     * @param  string $path_to_file string full path to file
     * @return object|array|void|null 
     */
    public function getImageAttribute(string $path_to_file = null)
    {
        if(empty($filename) == false || !file_exists($path_to_file)){
            return;
        }
        
        $ext = strtolower(pathinfo($path_to_file, PATHINFO_EXTENSION));
        if($ext == 'png'){
            $new = @imagecreatefrompng($path_to_file);
        }else{
            if($ext == 'webp'){
                $new = @imagecreatefromwebp($path_to_file);
            }else{
                $new = @imagecreatefromjpeg($path_to_file);
            }
        }
        
        //if image object response
        if(is_object($new) || is_resource($new)){
            return [
                'height'    => imagesy($new), //height -y
                'width'     => imagesx($new) //width -x
            ];
        }
    }

    /**
     * Image size attribute allowed check
     */
    private function checkImageAttributeError($image_error, $fileUploadName)
    {
        $count = count($image_error);
        if($count > 0){
            //check for default same error handler
            if(!isset($image_error['same']))
                $image_error['same'] = false;


            //get temp image upload attribute
            $imageSize = $this->getAttribute($fileUploadName);

            //if an image is found
            if(is_null($imageSize))
                return ['response' => false, 'message' => "Image size could'nt be found. Please check if uploaded image is valid"];
            
            //check for size error handler type
            if($image_error['same'])
            {
                //for one size error check
                if($count === 2){
                    if(isset($image_error['width']) && isset($imageSize['width'])){
                        if($image_error['width'] != $imageSize['width']){
                            return [
                                'response'  => false,
                                'message'   => sprintf("Image dimension allowed is width/%spx", $image_error['width'])
                            ];
                        }
                    }
                    if(isset($image_error['height']) && isset($imageSize['height'])){
                        if($image_error['height'] != $imageSize['height']){
                            return [
                                'response'  => false,
                                'message'   => sprintf("Image dimension allowed is height/%spx", $image_error['height'])
                            ];
                        }
                    }
                }
                //for both size error check
                else{
                    if($image_error['width'] != $imageSize['width']
                        || $image_error['height'] != $imageSize['height']){
                            return [
                                'response'  => false,
                                'message'   => sprintf("Image dimensions allowed is width/%spx by height/%spx", $image_error['width'], $image_error['height'])
                            ];
                    }
                }
            }   

            //check if size is greather than or equal to
            else
            {
                //for one size error check
                if($count === 2){
                    if(isset($image_error['width']) && isset($imageSize['width'])){
                        if($image_error['width'] > $imageSize['width']){
                            return [
                                'response'  => false,
                                'message'   => sprintf("Image dimension allowed must be greater or equal to width/%spx", $image_error['width'])
                            ];
                        }
                    }
                    if(isset($image_error['height']) && isset($imageSize['height'])){
                        if($image_error['height'] > $imageSize['height']){
                            return [
                                'response'  => false,
                                'message'   => sprintf("Image dimension allowed must be greater or equal to height/%spx", $image_error['height'])
                            ];
                        }
                    }
                }
                //for both size error check
                else{
                    if($image_error['width'] > $imageSize['width']
                        || $image_error['height'] > $imageSize['height']){
                            return [
                                'response'  => false,
                                'message'   => sprintf("Image dimensions allowed must be greater or equal to width/%spx by height/%spx", $image_error['width'], $image_error['height'])
                            ];
                    }
                }
            }
            
        }

        return ['response' => true, 'message' => ''];
    }
    
    /**
     * Get image width and height
     * 
     */
    private function getAttribute(string $filename)
    {
        if(empty($filename) || file_exists($_FILES[$filename]['tmp_name'][0]) == FALSE){
            return;
        }
        
        $img = $_FILES[$filename]['tmp_name'][0];
        $ext = strtolower(pathinfo($_FILES[$filename]['name'][0], PATHINFO_EXTENSION));
        if($ext == 'png'){
            $new = @imagecreatefrompng($img);
        }else{
            if($ext == 'webp'){
                $new = @imagecreatefromwebp($img);
            }else{
                $new = @imagecreatefromjpeg($img);
            }
        }

        //if image object response
        if(is_object($new) || is_resource($new)){
            return [
                'height'    => imagesy($new), //height -y
                'width'     => imagesx($new) //width -x
            ];
        }
        return;
    }

    /**
     * filter error to remove unwanted error response
     */
    private function filterError($errorDisallowed)
    {
        if(is_array($errorDisallowed) && count($errorDisallowed) > 0){

            foreach($errorDisallowed as $value){
                if(in_array($value, array_keys($this->error) )){
                    if($value != '500')
                        unset($this->error[$value]);
                }
            }
        }
    }

    /**
     * Get url real path
     * @param string @path 
     * @return string path slash '/' replacement
     */
    private function clean_path(?string $path = null)
    {
        return str_replace('\\', '/', $path) . '/';
    }
    
    /**
     * get each image upload folder storage data
     */
    private function getFolderStorage($folder_create, $upload_dir, $new_gen_file)
    {
        switch ($folder_create) 
        {
            case 'year':
                $filePath   = $this->timeBaseFolder($upload_dir)['year'] . '/' . $new_gen_file;
                $folderPath = str_replace($this->base_dir, '', $filePath);
                break;
            case 'month':
                $filePath   = $this->timeBaseFolder($upload_dir)['month'] . '/' . $new_gen_file;
                $folderPath = str_replace($this->base_dir, '', $filePath);
                break;
            case 'day':
                $filePath   = $this->timeBaseFolder($upload_dir)['day'] . '/' . $new_gen_file;
                $folderPath = str_replace($this->base_dir, '', $filePath);
                break;
            default:
                $filePath   = $this->base_dir . $upload_dir .'/'. $new_gen_file;
                $folderPath = str_replace($this->base_dir, '', $filePath);
                break;
        }

        return ['filePath' => $filePath, 'folderPath' => $folderPath];
    }

    /**
     * Create Non Existable Base Folder
     */
    private function baseFolderCreate($upload_dir)
    {
        //Create folder if not exist
        if(!file_exists($this->base_dir . $upload_dir))
        {
            @mkdir($this->base_dir . $upload_dir, 0777);

            //Create index file
            $this->createDefaultRestrict($this->base_dir . $upload_dir);
        }
    }
    
    /**
     * creating new child folder storage structure
     */
    private function createChildrenFolder($folder_create, $upload_dir)
    {
        //Creating our folder structure
        $folder = $this->timeBaseFolder($upload_dir);
        switch ($folder_create) 
        { 
            case 'year':
                if (!file_exists($this->timeBaseFolder($upload_dir)['year'])) {
                    mkdir($folder['year'], 0777);
                    $this->createDefaultRestrict($folder['year']);
                }
                break;
            case 'month':
                if (!file_exists($this->timeBaseFolder($upload_dir)['year'])) {
                    mkdir($folder['year'], 0777);
                    $this->createDefaultRestrict($folder['year']);
                }
                if (!file_exists($this->timeBaseFolder($upload_dir)['month'])) {
                    mkdir($folder['month'], 0777);
                    $this->createDefaultRestrict($folder['month']);
                }
                break;
            case 'day':
                if (!file_exists($this->timeBaseFolder($upload_dir)['year'])) {
                    mkdir($folder['year'], 0777);
                    $this->createDefaultRestrict($folder['year']);
                }
                if (!file_exists($this->timeBaseFolder($upload_dir)['month'])) {
                    mkdir($folder['month'], 0777);
                    $this->createDefaultRestrict($folder['month']);
                }
                if (!file_exists($this->timeBaseFolder($upload_dir)['day'])) {
                    mkdir($folder['day'], 0777);
                    $this->createDefaultRestrict($folder['day']);
                }
                break;
        }
    }
    
    /**
     * creating folder time base structure
     */
    private function timeBaseFolder($upload_dir = null)
    {
        $now = strtotime("now");
        $time = [
            "year"  => date("Y", $now),
            "month" => date("n", $now),
            "day"   => date("j", $now),
            "now"   => $now
        ];
        return [
            'year'  => $this->base_dir . $upload_dir . '/' . $time['year'],
            'month' => $this->base_dir . $upload_dir . '/' . $time['year'] . '/' . $time['month'],
            'day'   => $this->base_dir . $upload_dir . '/' . $time['year'] . '/' . $time['month'] . '/' . $time['day'], 
            'now'   => $time['now']
        ];
    }
    
    /**
     * create default folder restricted files
     */
    private function createDefaultRestrict($path)
    {
        //Create index file
        if (!file_exists("{$path}/index.html") ) {
            @$fsource = fopen("{$path}/index.html", 'w+');
            if(is_resource($fsource)){
                fwrite($fsource, "Restricted Access");
                fclose($fsource);
            }
        }

        //Create apache file -- .htaccess
        if (!file_exists("{$path}/.htaccess") ) {
            @$fsource = fopen("{$path}/.htaccess", 'w+');
            if(is_resource($fsource)){
                fwrite($fsource, "");
                fclose($fsource);
            }
        }
    }

    /**
     * allowed MimeType and Extension Types
     */
    private function allowedType()
    {
        //Extension MimeType
        $this->mimeType = [
            'video'         =>  ['video/mp4','video/mpeg','video/quicktime','video/x-msvideo','video/x-ms-wmv'],
            'audio'         =>  ['audio/mpeg','audio/x-wav'],
            'files'         =>  ['application/msword','application/pdf','text/plain'],
            'images'        =>  ['image/jpeg', 'image/png'],
            'general_image' =>  ['image/jpeg', 'image/png', 'image/webp'],
            'general_file'  =>  [
                'application/msword','application/pdf','text/plain','application/zip', 'application/x-zip-compressed', 'multipart/x-zip',
                'application/x-zip-compressed', 'application/x-rar-compressed', 'application/octet-stream', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ],
            'general_media' =>  ['audio/mpeg','audio/x-wav', 'video/mp4','video/mpeg','video/quicktime','video/x-msvideo','video/x-ms-wmv']
        ];        

        //Extension Type
        $this->extensionType = [
            'video'         =>  ['.mp4', '.mpeg', '.mov', '.avi', '.wmv'],
            'audio'         =>  ['.mp3', '.wav'],
            'files'         =>  ['.docx', '.pdf', '.txt'],
            'images'        =>  ['.jpg', '.jpeg', '.png'],
            'general_file'  =>  ['.docx', '.pdf', '.txt', '.zip', '.rar', '.xlsx', '.xls'],
            'general_image' =>  ['.jpg', '.jpeg', '.png', '.webp'],
            'general_media' =>  ['.mp3', '.wav', '.mp4', '.mpeg', '.mov', '.avi', '.wmv']
        ];
        
        return ['mime' => $this->mimeType, 'extension' => $this->extensionType];
    }
    
    /**
     * Form size to bytes | formatted values * 1024
     * @return int of size formated to bytes
     */
    private function sizeToBytes($size)
    {
        $size       = str_replace(',', '.', $size);
        $replace    = trim(str_replace('mb', '', strtolower($size)));
        $point      = strpos($replace, '.');

        if($point)
            $replace    = $replace . '00';
        else $replace   = $replace . '000';

        $replace = str_replace('.', '', $replace);
        return $replace * 1024;
    }
    
    /**
     * Form size to bytes | formatted values * 1024
     * @return int of size formated to bytes
     */
    private function sizeLimit($size)
    {
        return round(($size / 1024) / 1024, 2);
    }
    
    /**
     * Rearrange arrays files
     */
    private function rearrange($array)
    {
        foreach( $array as $key => $all ){
            foreach( $all as $i => $val ){
                $new[$i][$key] = $val;   
            }   
        }
        return @$new;
    }
    
    /**
     * Re-arrange data array files
     */
    private function reArrangePostFiles(&$file_post)
    {
        if(!is_array($file_post['name'])){
            return $this->error['500'];
        }
        
        $file_ary   = array();
        $file_count = count($file_post['name']);
        $file_keys  = array_keys($file_post);

        for ($i=0; $i<$file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }
        return $file_ary;
    }

}