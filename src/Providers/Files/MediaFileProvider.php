<?php
namespace System\Providers\Files;

use System\Providers\FileProvider;
use System\Models\FileModel;
use System\Helpers\DataCleanerHelper;
use System\Helpers\HTTPHelper;
/**
 * File Manager
 * 
 * Upload/Download/Manage Client uploaded files
 * 
 * Allow paths are relative to storage directory
 */
class MediaFileProvider extends FileProvider
{
    const MAX_SIZE = 4000000;
    const EXTENSIONS = ['jpeg','jpg','png'];
    const MIME = ['image/jpeg', 'image/jpg', 'image/png'];
    
    /**
     * Uploads File
     * 
     * Manage uploads to storage folder through form file post uploading
     * 
     * @param   string      $fileIndex                  name used within $_FILES when uploading file using form
     * @param   string      $folder                     folder within storage
     * @param   bool        $overwrite                  overwrite file that has similar name
     * @param   string      $customName                 if customName is not null, this custom name will be used but if it is a empty string it will generate a custom name
     * @param   array       $allowExtensions            all allowed extensions
     * @param   int         $maxSize                    max file size
     * 
     * @return  self        newly created file instance
     */
    public static function upload($fileIndex, string $folder = '', $overwrite = false, string $customName = null, array $allowExtensions = [], $maxSize = 0)
    {
        if (! config('PERMISSIONS.ALLOW_UPLOADS') || ! HTTPHelper::isFile($fileIndex) || ($_FILES[$fileIndex]['error'] !== 0)) {
            return false;
        }
        $storagePath = config('PATHS.ROOT~STORAGE');
        if (empty($storagePath)) {
            return false;
        }

        // Get file
        $file = self::create($_FILES[$fileIndex]['name'] ?? '');
        $name = $file->name();
        $ext = $file->extension();

        // check if upload directory is a directory and is writable
        if (! is_dir($storagePath)) {
            $file->feedback('Upload directory does not exist', 1, 'FileFolder');
            return $file;
        }
        if (! is_writable($storagePath)) {
            $file->feedback('Upload directory is not writable', 1, 'FileFolder');
            return $file;
        }
        
        // check if folder is valid
        if ($folder) {
            $folder = str_replace('/', '', $folder);
            $folder = DataCleanerHelper::cleanValue($folder);
            if (file_exists($storagePath . $folder) && is_dir($storagePath . $folder)) {
                $folder = $folder . '/';
            } else {
                $file->feedback('Folder does not exist ' . $storagePath . $folder, 1, 'FileFolder');
                return $file;
            }
        } else {
            $folder = '';
        }

        $errors = [];
        $type = $_FILES[$fileIndex]['type'];
        $size = $_FILES[$fileIndex]['size'];
        $tmp_name = $_FILES[$fileIndex]['tmp_name'];

        // Check file extension
        if (! self::checkExtension($ext, $allowExtensions)) {
            $file->feedback('File extension not allowed', 0, 'FileExtension');
        }

        // Check file type
        if (! self::checkType($type)) {
            $file->feedback('File type not allowed', 0, 'FileType');
        }

        // Check file size
        if (! empty($maxSize)) {
            if ($size > $maxSize) {
                $file->feedback('File size too large', 0, 'FileSize');
            }
        } elseif ($size > self::MAX_SIZE) {
            $file->feedback('File size too large', 0, 'FileSize');
        }

        // Create a new file name
        if (! is_null($customName)) {
            if (is_string($customName) && ! empty($customName)) {
                $customName = str_replace('/', '', $customName);
                $customName = str_replace('.', '', $customName);
                $customName = str_replace(',', '', $customName);
                $customName = str_replace(' ', '', $customName);
                $customName = DataCleanerHelper::cleanValue($customName);
                if (empty ($customName)) {
                    $file->feedback('Empty file name created', 1, 'FileName');
                }
                $file_name = $folder . $customName . '.' . $ext;
            } else {
                $tmp = str_replace(array('.',' '), array('',''), microtime());
                if (empty ($tmp)) {
                    $file->feedback('Empty file name created', 1, 'FileName');
                }
                $file_name = $folder . $tmp . '.' . $ext;
            }
        } else {
            $name = str_replace(' ', '_', DataCleanerHelper::cleanValue($name));
            $file_name = $folder . $name . '.' . $ext;
        }

        if ( !$overwrite && self::checkFile($storagePath . $file_name)) {
            $file->feedback('File name already exist', 0, 'FileName');
        }
        if ($file->hasFeedbackWithType(1)) {
            $file->feedback('Something went wrong with the upload', 0, 'FileUpload');
        }
        if ($file->hasFeedback()) {
            return $file;
        }

        if (move_uploaded_file($tmp_name, $storagePath . $file_name)) {
            $file = self::create(config('PATHS.STORAGE') . $file_name);
            if ($file->isValid()) {
                return $file;
            } else {
                $file->feedback('Could not find file', 0, 'FileUpload');
                return $file;
            }
        } else {
            $file->feedback('Failed to create file', 0, 'FileUpload');
        }
        return $file;
    }
}