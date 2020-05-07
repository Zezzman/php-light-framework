<?php
namespace System\Models;

use System\Traits\Feedback;
use System\Helpers\FileHelper;
/**
 * 
 */
class FileModel
{
    use Feedback;

    private $subPath = '';
    private $info = [];

    public function __construct($subPath, $info)
    {
        $this->subPath = $subPath;
        $this->info = $info;
    }
    /**
     * Check if file exist
     * 
     * @return  bool        true if file exist
     */
    public function isValid()
    {
        return \System\Providers\FileProvider::checkFile($this->path());
    }
    /**
     * File extension
     * 
     * @return  string      file extension
     */
    public function extension()
    {
        if ($this->info && isset($this->info['extension'])) {
            return $this->info['extension'];
        } else {
            return false;
        }
    }
    /**
     * File mime type
     * 
     * @return  string          file mime type
     */
    public function type()
    {
        if ($this->isValid()) {
            $path = $this->path();
            return mime_content_type($path);
        } else {
            return false;
        }
    }
    /**
     * File name
     * 
     * @return  string      file name
     */
    public function name()
    {
        if ($this->info && isset($this->info['basename'])) {
            return $this->info['basename'];
        } else {
            return '';
        }
    }
    /**
     * File path
     * 
     * @return  string      file path
     */
    public function path()
    {
        if (! empty($this->info)) {
            return $this->info['dirname'] . DIRECTORY_SEPARATOR . $this->name();
        } else {
            return '';
        }
    }
    /**
     * File sub path
     * 
     * @return  string      file sub path
     */
    public function subPath()
    {
        return $this->subPath;
    }
    /**
     * link
     * 
     * @return  string      link to file
     */
    public function link()
    {
        return config('LINKS.PUBLIC') . $this->subPath;
    }
    /**
     * Read file
     * 
     * @return  bool        false if file cannot be read
     */
    public function read()
    {
        return FileHelper::readFile($this->subPath(), $this->type());
    }
    /**
     * Print file
     * 
     * @return  string      html of embedded file
     */
    public function print(string $description = '', string $style = '<img src="data:{type};{base},{data}" alt="{description}">')
    {
        return FileHelper::printImage($this->path(), $this->type(), $description, $style);
    }
    /**
     * Check if file is within directory
     * 
     * @param   string      $dir               directory
     * 
     * @return  bool        true if file directory is the directory or a sub-directory of it
     */
    public function withinDirectory(string $dir)
    {
        if (empty($dir)) {
            return false;
        }
        if (substr($this->path(), 0, strlen($dir)) === $dir) {
            return true;
        }
        return false;
    }
}