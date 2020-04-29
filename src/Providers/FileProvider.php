<?php
namespace System\Providers;

use System\Helpers\DataCleanerHelper;
use System\Models\FileModel;
/**
 * File Manager
 * 
 * Upload/Download/Manage Client uploaded files
 * 
 * Allow paths are relative to storage directory
 */
abstract class FileProvider
{
    const EXTENSIONS = [];
    const MIME = [];
    
    protected function __construct(){}

    /**
     * Creates file instance
     * 
     * @param   string      $path              file path
     * 
     * @return  self    file instance
     */
    public static function create(string $path)
    {
        if (empty($path)) {
            return;
        }
        $path = DataCleanerHelper::cleanValue($path);
        $root = requireConfig('PATHS.ROOT');
        if (self::checkFile($root . $path)) {
            $file = new FileModel($path, pathinfo($root . $path));
            if (self::checkExtension($file->extension(), static::EXTENSIONS)
            && self::checkType($file->type(), static::MIME))
            {
                return $file;
            }
        }
    }
    /**
     * Scan folder for files
     * 
     * @param   string      $dir                        directory of files
     * @param   array       $allowExtensions            all allowed extensions
     * 
     * @return  array    An array of file instances
     */
    public static function scan(string $folder)
    {
        $files = [];
        $dir = requireConfig('PATHS.ROOT') . $folder;
        if (is_dir($dir)) {
            $names = scandir($dir);
            foreach ($names as $name) {
                if (! preg_match('/(^|\s)[\.]/', $name)) {
                    if (is_file($dir . $name)) {
                        $file = self::create($folder . $name);
                        if ($file->isValid()) {
                            if (self::checkExtension($file->extension(), static::EXTENSIONS)) {
                                $files[] = $file;
                            }
                        }
                    }
                }
            }
        }
        
        return $files;
    }
    /**
     * Scan folders for files
     * 
     * @param   array       $folders                    directories of files
     * @param   array       $allowExtensions            all allowed extensions
     * 
     * @return  array    An array of file instances for each folder
     */
    public static function scanFolders(array $folders)
    {
        $files = [];
        $root = requireConfig('PATHS.ROOT');
        foreach ($folders as $folder) {
            $dir = $root . $folder;
            if (is_dir($dir)) {
                $files[$folder] = [];
                $names = scandir($dir);
                foreach ($names as $name) {
                    if (! preg_match('/(^|\s)[\.]/', $name)) {
                        if (is_file($dir . $name)) {
                            $file = self::create($folder . $name);
                            if ($file->isValid()) {
                                if (self::checkExtension($file->extension(), static::EXTENSIONS)) {
                                    $files[$folder][] = $file;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $files;
    }
    /**
     * List file within directory
     * 
     * @param   string      $dir                        directory of files
     * @param   bool        $includeFolders             include folder names
     * 
     * @return  array    An array of file names
     */
    public static function listFiles(string $dir, bool $includeFolders = false)
    {
        $folders = [];
        $files = [];
        $dir = trim($dir, '/');
        $path = requireConfig('PATHS.ROOT') . $dir . DIRECTORY_SEPARATOR;
        if (! empty($dir) && is_dir($path)) {
            $names = scandir($path);
            foreach ($names as $name) {
                if (! preg_match('/(^|\s)[\.]/', $name)) {
                    if (is_file($path . $name)
                    && file_exists($path . $name)) {
                        $files[] = DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $name;
                    } elseif ($includeFolders) {
                        $folders[] = DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $name;
                    }
                }
            }
        }
        if ($includeFolders) {
            $files = array_merge($folders, $files);
        }
        return $files;
    }
    /**
     * Check if file extension is allowed
     * 
     * @param   string      $ext                        file extension
     * @param   array       $allowExtensions            all allowed extensions
     * 
     * @return  bool      true if extension is allowed
     */
    public static function checkExtension(string $ext, array $allowExtensions = [])
    {
        if (! empty($allowExtensions)) {
            if (in_array($ext, $allowExtensions)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }
    /**
     * Check if file type is allowed
     * 
     * @param   string      $type                       file type
     * 
     * @return  bool      true if type is allowed
     */
    public static function checkType(string $type, array $allowTypes = [])
    {
        if (! empty($allowTypes)) {
            if (in_array($type, $allowTypes)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }
    /**
     * Check if file path exist
     * 
     * @param   string      $path               file path
     * 
     * @return  bool      true if file exist
     */
    public static function checkFile(string $path)
    {
        if (empty($path)) {
            return false;
        }
        if (file_exists($path) && is_file($path)) {
            return true;
        }
        return false;
    }
}