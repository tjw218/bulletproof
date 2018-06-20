<?php
/**
 * BULLETPROOF
 *
 * A single-class PHP library to upload images securely.
 *
 * PHP support 5.3+
 *
 * @package     bulletproof
 * @version     4.0.0
 * @author      https://twitter.com/_samayo
 * @link        https://github.com/samayo/bulletproof
 * @license     MIT
 */
namespace Bulletproof;


class Image implements \ArrayAccess
{
    /**
     * @var string The new image name, to be provided or will be generated.
     */
    protected $name;

    /**
     * @var int The image width in pixels
     */
    protected $width;

    /**
     * @var int The image height in pixels
     */
    protected $height;

    /**
     * @var string The image mime type (extension)
     */
    protected $mime;

    /**
     * @var string The full image path (dir + image + mime)
     */
    protected $fullPath;

    /**
     * @var string The folder or image storage location
     */
    protected $location;

    /**
     * @var array The min and max image size allowed for upload (in bytes)
     */
    protected $size = array(100, 500000);

    /**
     * @var array The max height and width image allowed
     */
    protected $dimensions = array(5000, 5000);

    /**
     * @var array The mime types allowed for upload
     */
    protected $mimeTypes = array('jpeg', 'png', 'gif', 'jpg');

    /**
     * @var array list of known image types
     */
    protected $acceptedMimes = array(
      1 => 'gif', 'jpeg', 'png', 'swf', 'psd',
            'bmp', 'tiff', 'tiff', 'jpc', 'jp2', 'jpx',
            'jb2', 'swc', 'iff', 'wbmp', 'xbm', 'ico'
    );

    /**
     * @var array error messages strings
     */
    protected $commonUploadErrors = array(
      UPLOAD_ERR_OK         => '',
      UPLOAD_ERR_INI_SIZE   => 'Image is larger than the specified amount set by the server',
      UPLOAD_ERR_FORM_SIZE  => 'Image is larger than the specified amount specified by browser',
      UPLOAD_ERR_PARTIAL    => 'Image could not be fully uploaded. Please try again later',
      UPLOAD_ERR_NO_FILE    => 'Image is not found',
      UPLOAD_ERR_NO_TMP_DIR => 'Can\'t write to disk, due to server configuration ( No tmp dir found )',
      UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Please check you file permissions',
      UPLOAD_ERR_EXTENSION  => 'A PHP extension has halted this file upload process'
    );
    
    /**
     * @var array storage for the $_FILES global array
     */
    private $_files = array();

    /**
     * @var string storage for any errors
     */
    private $error = '';

    /**
     * @param array $_files represents the $_FILES array passed as dependency
     */
    public function __construct(array $_files = array())
    {
      /* check if php_exif is enabled */
      if (!function_exists('exif_imagetype')) {
          $this->error = 'Function \'exif_imagetype\' Not found. Please enable \'php_exif\' in your php.ini';
      }

      $this->_files = $_files;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * 
     * @return null
     */
    public function offsetSet($offset, $value){}

    /**
     * @param mixed $offset
     * 
     * @return null
     */
    public function offsetExists($offset){}

    /**
     * @param mixed $offset
     * 
     * @return null
     */
    public function offsetUnset($offset){}

    /**
     * Gets array value \ArrayAccess
     * 
     * @param mixed $offset
     * @return string|boolean
     */
    public function offsetGet($offset)
    { 
      
      // return false if $image['key'] isn't found
      if (!isset($this->_files[$offset])) {
          $this->error = sprintf('No file input found with name: (%s)', $offset); 
          return false;
      }

      $this->_files = $this->_files[$offset];

      // check for common upload errors
      if(isset($this->_files['error'])){
          $this->error = $this->commonUploadErrors[$this->_files['error']];
      }

      return true;
    }

    /**
     * Sets max image height and width limit
     *
     * @param $maxWidth int max width value
     * @param $maxHeight int max height value
     * 
     * @return $this
     */
    public function setDimension($maxWidth, $maxHeight)
    {
      $this->dimensions = array($maxWidth, $maxHeight);
      return $this;
    }

    /**
     * Returns the full path of the image ex 'location/image.mime'
     *
     * @return string
     */
    public function getFullPath()
    {
      return $this->fullPath = $this->getLocation() . '/' . $this->getName() . '.' . $this->getMime();
    }

    /**
     * Returns the image size in bytes
     *
     * @return int
     */
    public function getSize()
    {
      return (int) $this->_files['size'];
    }

    /**
     * Define a min and max image size for uploading
     *
     * @param $min int minimum value in bytes
     * @param $max int maximum value in bytes
     *
     * @return $this
     */
    public function setSize($min, $max)
    {
      $this->size = array($min, $max);
      return $this;
    }

    /**
     * Returns a JSON format of the image width, height, name, mime ...
     *
     * @return string
     */
    public function getJson()
    {
      return json_encode (
        array(
          'name'      => $this->name,
          'mime'      => $this->mime,
          'height'    => $this->height,
          'width'     => $this->width,
          'size'      => $this->_files['size'],
          'location'  => $this->location,
          'fullpath'  => $this->fullPath
        )
      );
    }

    /**
     * Returns the image mime type
     *
     * @return null|string
     */
    public function getMime()
    {
      if(!$this->mime){
        $this->mime = $this->getImageMime($this->_files['tmp_name']);
      }
      
      return $this->mime;
    }

    /**
     * Define a mime type for uploading
     *
     * @param array $fileTypes
     * 
     * @return $this
     */
    public function setMime(array $fileTypes)
    {
      $this->mimeTypes = $fileTypes;
      return $this;
    }

    /**
     * Gets the real image mime type
     *
     * @param $tmp_name string The upload tmp directory
     * 
     * @return null|string
     */
    protected function getImageMime($tmp_name)
    {
      $this->mime = @$this->acceptedMimes[exif_imagetype($tmp_name)];
      if (!$this->mime) {
        return null;
      }

      return $this->mime;
    }

    /**
     * Returns error string or false if no errors occurred
     *
     * @return string|false
     */
    public function getError()
    {
      return $this->error;
    }

    /**
     * Returns the image name
     *
     * @return string
     */
    public function getName()
    {
      if (!$this->name) {
        $this->name = uniqid('', true) . '_' . str_shuffle(implode(range('e', 'q')));
      }

      return $this->name;
    }

    /**
     * Provide image name if not provided
     *
     * @param null $isNameProvided
     * @return $this
     */
    public function setName($isNameProvided = null)
    {
      if ($isNameProvided) {
        $this->name = filter_var($isNameProvided, FILTER_SANITIZE_STRING);
      }

      return $this;
    }

    /**
     * Returns the image width
     *
     * @return int
     */
    public function getWidth()
    {
      if ($this->width != null) {
        return $this->width;
      }

      list($width) = getImageSize($this->_files['tmp_name']);
      return $width;
    }

    /**
     * Returns the image height in pixels
     *
     * @return int
     */
    public function getHeight()
    {
      if ($this->height != null) {
        return $this->height;
      }

      list(, $height) = getImageSize($this->_files['tmp_name']);
      return $height;
    }

    /**
     * Returns the storage / folder name
     *
     * @return string
     */
    public function getLocation()
    {
      if (!$this->location) {
        $this->setLocation();
      }

      return $this->location;
    }

    /**
     * Validate directory/permission before creating a folder
     * 
     * @param $dir string the folder name to check
     * @return bool
     */
    private function isDirectoryValid($dir) 
    {
      return !file_exists($dir) && !is_dir($dir) || is_writable($dir); 
    }

    /**
     * Creates a location for upload storage
     *
     * @param $dir string the folder name to create
     * @param int $permission chmod permission
     * @return $this
     */
    public function setLocation($dir = 'bulletproof', $permission = 0666)
    {
      $isDirectoryValid = $this->isDirectoryValid($dir); 

      if(!$isDirectoryValid){
        $this->error = 'Can not create a directory  \'' . $dir . '\', please check your dir permission';
        return false;
      }
    
      $create = !is_dir($dir) ? @mkdir('' . $dir, (int) $permission, true) : true; 

      if (!$create) {
        $this->error = 'Error! directory \'' . $dir . '\' could not be created';
        return false;
      }

      $this->location = $dir;
      return $this;
    }


    /**
     * Validate image and upload
     * 
     * @return false|null|Image
     */
    public function upload()
    {
      $image = $this;
      $files = $this->_files;

      if ($this->error || !isset($files['tmp_name'])) {
        return false;
      }

      /* check image for valid mime types and return mime */
      $image->getImageMime($files['tmp_name']);
      /* validate image mime type */
      if (!in_array($image->mime, $image->mimeTypes)) {
        $ext = implode(', ', $image->mimeTypes);
        $image->error = sprintf('Invalid File! Only (%s) image types are allowed', $ext);
        return false;
      }

      /* initialize image properties */
      $image->name = $image->getName();
      $image->width = $image->getWidth();
      $image->height = $image->getHeight();
      $image->getFullPath();
      

      /* get image sizes */
      list($minSize, $maxSize) = $image->size;

      /* check image size based on the settings */
      if ($files['size'] < $minSize || $files['size'] > $maxSize) {
        $min = intval($minSize / 1000) ?: 1;
        $max = intval($maxSize / 1000) ?: 1;
        $image->error = 'Image size should be at least ' . $min . ' KB, and no more than ' . $max . ' KB';
        return false;
      }

      /* check image dimension */
      list($allowedWidth, $allowedHeight) = $image->dimensions;

      if (
          $image->height > $allowedHeight || 
          $image->width > $allowedWidth ||
          $image->height < 2 ||
          $image->width < 2
        ) {
        $image->error = 'Image height/width should be less than ' . $allowedHeight . '/' . $allowedWidth . ' pixels';
        return false;
      }

      $isSaved = $image->isSaved($files['tmp_name'], $image->fullPath);

      return $isSaved ? $image : false;
    }


    /**
     * Final upload method to be called, isolated for testing purposes
     *
     * @param $tmp_name int the temporary location of the image file
     * @param $destination int upload destination
     *
     * @return bool
     */
    protected function isSaved($tmp_name, $destination)
    {
      return move_uploaded_file($tmp_name, $destination);
    }
}