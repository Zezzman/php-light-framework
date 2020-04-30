<?php
namespace System\Controllers;

use System\Controller;
use System\Helpers\DataCleanerHelper;
use System\ViewModels\ViewModel;

/**
 * 
 */
final class StorageController extends Controller
{
    /**
     * Storage Index
     */
    public function Index($location, array $extensions = null)
    {
        $viewModel = new ViewModel();
        if (is_array($location) && ! empty($location)) {
            $path = array_reduce($location, function ($str, $item) { return $str . $item . '/'; });
        }
        return $this->view('storage', $viewModel, ['path' => $path ?? '', 'extensions' => $extensions]);
    }
}