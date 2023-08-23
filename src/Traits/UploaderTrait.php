<?php

namespace ultimateUploader\Traits;

/***********************************************************
* #### PHP - Ultimate Image Uploader Class ####
***********************************************************/

trait UploaderTrait{
    
    /**
     * data response
     */
    public $data = [
        'message'   => '',
        'status'    => 0,
        'file'      => null,
        'ext'       => null
    ];
    
    /**
     * all error response
     */
    public $allError;
    
    /**
     * base_url / domain url
     */
    public $base_url;
    
    /**
     * server base dir
     */
    public $base_dir;
    
    /**
     * pass other params to attribute to 
     * gain access to them globally
     */
    public $attribute;

    /**
     * mime Type
     */
    private $mimeType;
    
    /**
     * extension Type
     */
    private $extensionType;
    
    /**
     * uploaded folder with image source
     */
    private $folder;
    
    /**
     * Image
     */
    private $image;
    
    /**
     * Image Compressor Settings
     */
    private $settings;
    
    /**
     * Success response tracking 
     */
    private $success = false;

    /**
     * image upload run
     */
    private $send = false;

    /**
     * error handler and response
     */
    private $error;

    /**
     * internal build property
     */
    private $internal = [
        'size'              => null,
        'size_limit'        => 0,
        'count'             => 0,
        'rearrange'         => null,
        'data'              => [],
        'raw_upload'        => [],
        'folder_upload'     => [],
        'folder_real_path'  => [],
        'folder_upload_url' => [],
        'ext_files'         => [],
        'time'              => 0
    ];

}