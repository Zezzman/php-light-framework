<?php
namespace System\Providers\Files;

use System\Providers\FileProvider;
/**
 * File Manager
 * 
 * Upload/Download/Manage Client uploaded files
 * 
 * Allow paths are relative to storage directory
 */
class JSONFileProvider extends FileProvider
{
    const MAX_SIZE = 4000000;
    const EXTENSIONS = ['json'];
    const MIME = ['text/plain'];
}