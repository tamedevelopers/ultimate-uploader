<?php

namespace ultimateUploader;

use ImgCompressor\ImgCompressor;
use ultimateUploader\Traits\FileTrait;
use ultimateUploader\Traits\ProcessorTrait;
use ultimateUploader\Traits\uploaderTrait;

/***********************************************************
* #### PHP - Ultimate Image Uploader Class ####
***********************************************************/

class ultimateUploader{
    
    use UploaderTrait, FileTrait, ProcessorTrait;
    
    
    /**
     * Constructor
     * @param errorDisallowed|null   index array - error to dis*allow
     * @param base_dir|null          base directory (needed) for upload
     * @param base_url|null          base url
     * 
     */
    public function __construct(array $errorDisallowed = [], ?array $attribute = null) 
    {
        /**
        * base root directory path
        */
        $this->base_dir = $this->optionDir();

        /**
        * base url/domain - full url path
        */
        $this->base_url = $this->optionUrl();

        /**
        * gain access to global attributes
        */
        $this->attribute = $attribute;
        
        /**
        * Error for public consumption
        */
        $this->allError = [
            '400' => "-> ERROR_400 - no file upload",
            '401' => "-> ERROR_401 - select file to upload",
            '402' => "-> ERROR_402 - File upload size is bigger than allowed size limit",
            '403' => "-> ERROR_403 - Maximum file allowed exceeded ",
            '404' => "-> ERROR_404 - Uploaded file format not allowed",
            '405' => "-> ERROR_405 - Image size allowed error",
            '500' => "-> ERROR_500 - Input file `name[]` must be passed as an array"
        ];

        /**
        * Private Errors for internal usage
        */
        $this->error = [
            '400' => 400,
            '401' => 401,
            '402' => 402,
            '403' => 403,
            '404' => 404,
            '405' => 405,
            '500' => 500,
            '200' => 200
        ];

        /**
        * Remove non-allowed error
        */
        $this->filterError($errorDisallowed);
    }


    /**
     * set non existing method
     * @param setDir|setDirectory| function call to set optional base dir 
     * @param setUrl|setURL| function call to set optional base dir 
     * @usage ->setURL('www.domain.com') | ->setDir('path_to_dir')
     */
    public function __call( $key, $value )
    {
        /**
        * base root directory path setting
        */
        if(in_array($key, ['setDir', 'setDirectory'])){
            $this->base_dir = $this->optionDir(@$value[0]);
        }

        /**
        * base url path setting
        */
        if(in_array($key, ['setUrl', 'setURL'])){
            $this->base_url = $this->optionUrl(@$value[0]);
        }
    }
    
    
    /**
    * Image upload run
    * @param filename           string - the html input file name (image).
    * @param folder_create      string - for creating folder (default, year, month, day)
    * @param upload_dir         string - for dir upload folder (image/new | images)
    * @param type               string - for file mime type (video, audio, files, image, general_image, general_file, general_media)
    * @param size               string - for allowed file size (1.5mb)
    * @param limit              int - for allowed upload limit (2)
    * @param dimension_size     array - for image dimension size.
    * 
    * @return $this
    */
    public function run(string $fileUploadName, string $folder_create = "default", string $upload_dir, string $type = NULL, string $size = '2mb', int $limit_max = 1, array $dimension_size = [])
    {
        if (isset($_FILES[$fileUploadName])) 
        {
            //Create base folder
            $this->baseFolderCreate($upload_dir);
            
            //format size to bytes
            $this->internal['size'] = $this->sizeToBytes($size);

            //size limit
            $this->internal['size_limit'] = $this->sizeLimit($this->internal['size']); 

            //First we rearrange our upload file data
            $this->internal['rearrange'] = $this->reArrangePostFiles($_FILES[$fileUploadName]);

            //storage
            $storage = [];

            /**
            * if input name is not an array - error 500
            */
            if($this->internal['rearrange'] === $this->error['500'])
            {
                $this->data['status']   = $this->error['500'];
                $this->data['message']  = sprintf("Input file `name[]` (%s) must be passed as an array.", $fileUploadName);
            }
            
            //check if upload data is an array
            if(is_array($this->internal['rearrange'])){

                //count total files
                $this->internal['count'] = count($this->internal['rearrange']);

                //Start loop process
                foreach($this->internal['rearrange'] as $key => $file)
                {
                    //Collect img datas 
                    $file_name      =   $this->internal['rearrange'][$key]['name'];
                    $file_type      =   $this->internal['rearrange'][$key]['type'];
                    $file_tmp_name  =   $this->internal['rearrange'][$key]['tmp_name'];
                    $file_error     =   $this->internal['rearrange'][$key]['error'];
                    $file_size      =   $this->internal['rearrange'][$key]['size'];
                    $this->success  =   false;

                    /**
                    * check image size error
                    */
                    $attrError = $this->checkImageAttributeError($dimension_size, $fileUploadName);

                    /**
                    * Get file extension
                    */
                    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

                    /**
                    * Please select a file to upload - error 401
                    */
                    if (empty($file_name)) { 
                        if(in_array(isset($this->error['401']), array_keys($this->error))){
                            $this->data['status']   = $this->error['401'];
                            $this->data['message']  = sprintf("Please select a file to upload", $fileUploadName);
                        }
                        break;
                    }

                    /**
                    * if upload is an image & image size allowed error. - error 405
                    */
                    elseif(!$attrError['response'] && in_array($type, ['images', 'general_image']))
                    {
                        if(in_array(isset($this->error['405']), array_keys($this->error))){
                            $this->data['status']   = $this->error['405'];
                            $this->data['message']  = sprintf($attrError['message'], $fileUploadName);
                        }
                        break;
                    }
                    
                    /**
                    * File upload size is bigger than allowed size limit. - error 402
                    */
                    elseif($file_size > $this->internal['size'])
                    {
                        $this->removeUploadedFile($this->internal['folder_upload']);
                        if(in_array(isset($this->error['402']), array_keys($this->error))){
                            $this->data = [
                                "status"    => $this->error['402'], 
                                "message"   => sprintf("%s <br> File upload size is bigger than allowed size limit of %smb <br>", $fileUploadName, $this->internal['size_limit']), 
                                "file"      => $file_name, 
                                "ext"       => $file_extension
                            ];
                        }
                        break;
                    }

                    /**
                    * Maximum upload allowed exceeded. - error 403
                    */
                    elseif($this->internal['count'] > $limit_max)
                    {
                        $text = $this->fileText($limit_max);
                        if(in_array(isset($this->error['403']), array_keys($this->error))){
                            $this->data = [
                                "status"    => $this->error['403'], 
                                "message"   => sprintf("%s <br> Maximum upload allowed exceeded, you can upload upto %s%s only.<br>", $fileUploadName, $limit_max, $text), 
                                "file"      => $file_name, 
                                "ext"       => $file_extension
                            ];
                        }
                        break;
                    }

                    /**
                    * Uploded file format not allowed. - error 404
                    */
                    elseif(!in_array($file_type, $this->allowedType()['mime'][$type]))
                    {
                        $splint_img_ext = implode(' ', $this->allowedType()['extension'][$type]);
                        if(isset($this->internal['data'])){ $this->removeUploadedFile($this->internal['folder_upload']); }
                        if(in_array(isset($this->error['404']), array_keys($this->error))){
                            $this->data = [
                                "status"    => $this->error['404'], 
                                "message"   => sprintf("%s <br> Uploaded file format not allowed; allowed formats are %s <br>", $fileUploadName, $splint_img_ext), 
                                "file"      => $file_name,
                                "ext"       => $file_extension
                            ];
                        }
                        break;
                    }

                    /**
                    * No error response (Uploading successfully started). - error 200
                    */
                    elseif($this->send = true)
                    {
                        $this->success = true;

                        //create children folder structure
                        $this->createChildrenFolder($folder_create, $upload_dir);

                        //new generated file name
                        $new_gen_file = $this->generateNewFileData($file_extension);

                        //get storage path
                        $storage = $this->getFolderStorage($folder_create, $upload_dir, $new_gen_file['name']);
                        
                        if(in_array($file_type, $this->allowedType()['mime'][$type])){    
                            if( move_uploaded_file($file_tmp_name, $storage['filePath']) ){
                                array_push($this->internal['data'], $new_gen_file['name']); //new uploaded - generated image
                                array_push($this->internal['raw_upload'], $file_name); //raw image file name
                                
                                array_push($this->internal['folder_upload'], $storage['folderPath']); // folder upload path
                                array_push($this->internal['folder_real_path'], dirname($storage['filePath']) . "/{$new_gen_file['name']}"); // folder real upload path
                                array_push($this->internal['folder_upload_url'], $this->base_url . "{$storage['folderPath']}"); // folder url upload path
                                array_push($this->internal['ext_files'], $new_gen_file['extension']); // extension for uploaded files
                            }
                        }
                    } 
                }
            }

            //Return on successful upload
            if($this->send && $this->success){
                //add to instance of this for internal space usage
                $this->folder = $this->internal['folder_real_path'];
                $this->image = $this->internal['data'];
                $this->settings = dirname($storage['filePath']); //compressed dir path

                $this->data = [
                    "status"    => $this->error['200'], 
                    "message"   => sprintf("%s <br> Uploaded successfully <br>", $fileUploadName), 
                    "file"      => [
                        "image"             => $this->internal['raw_upload'],  
                        "new_image"         => $this->internal['data'],
                        "file"             => $this->internal['raw_upload'],  
                        "new_file"         => $this->internal['data'],
                        "folder"            => $this->internal['folder_upload'],
                        "folder_real_path"  => $this->internal['folder_real_path'], 
                        "folder_url"        => $this->internal['folder_upload_url']
                    ],
                    "ext" => $this->internal['ext_files']
                ];
            }
            
        }
        
        /**
        * No File Upload found. ERROR_400
        */
        else
        {
            if(isset($this->error['400'])){
                $this->data['status']   = $this->error['400'];
                $this->data['message']  = "No File Upload found";
            }
        }

        return $this;
    }


    /**
     * @param  callable     function - for error message handling.
     * @return $this
     */
    public function error(callable $function)
    {
        if(!$this->success){
            if(is_callable($function)){
                if($this->data['status'] !== 0){
                    $function($this);
                }
            }
        }
        return $this;
    }


    /**
     * @param  callable     function - for success on upload handling.
     * @return $this
     */
    public function success(callable $function)
    {
        if($this->send && $this->success){
            if(is_callable($function)){
                $function($this);
            }
        }
        return $this;
    }
    
    /**
     * Image compressor
     * @param boolean $compress
     * @return void|null
     */
    public function compress($compress = false)
    {
        if($this->success && $compress){

            $ImgCompressor = new ImgCompressor($this->settings);
            if(!is_null($this->folder)){
                //Loop through folder images
                foreach($this->folder as $key => $folder_val){
                    //Loop through image
                    foreach($this->image as $i_key => $i_val){
                        $ImgCompressor->run($folder_val, pathinfo($i_val, PATHINFO_EXTENSION), 5);
                    }
    
                }
            }
        }
    }

    /**
     * Return first uploaded data
     * @return array|null
     */
    public function first()
    {
        // get upload data
        $data = $this->data['file'];

        // return only first data
        if(isset($data['image'][0])){
            return [
                'image' => $data['image'][0],
                'new_image' => $data['new_image'][0],
                'file' => $data['file'][0],
                'new_file' => $data['new_file'][0],
                'folder' => $data['folder'][0],
                'folder_real_path' => $data['folder_real_path'][0],
                'folder_url' => $data['folder_url'][0] 
            ];
        }
    }
    
    /**
     * Get First File Upload
     *
     * @return string|null
     */
    public function getFile()
    {
        $data = $this->first();

        return $data['new_file'] ?? null;
    }

    /**
     * Get File Url
     *
     * @return string|null
     */
    public function getFileUrl()
    {
        $data = $this->first();

        return $data['folder_url'] ?? null;
    }
    
    /**
     * Get First Folder File Path
     *
     * @return string|null
     */
    public function getFolder()
    {
        $data = $this->first();

        return $data['folder'] ?? null;
    }
    
    /**
     * Get First Full Folder Path
     *
     * @return string|null
     */
    public function getFolderPath()
    {
        $data = $this->first();

        return $data['folder_real_path'] ?? null;
    }

    /**
     * Return all uploaded data
     * @return array|null
     */
    public function get()
    {
        return $this->data['file'];
    }

    /**
     * @param  int|float  $response interger or float passer.
     * @param  string|array|object     $message can be any data type for display.
     * @return string       
     * - Returns encoded JSON object of response and message
     */
    public function echoJson(?int $response = 0, $message = null)
    {
        echo json_encode(['response' => $response, 'message' => $message]);
    }
    
}



