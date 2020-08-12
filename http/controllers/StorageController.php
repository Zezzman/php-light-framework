<?php
namespace System\Controllers;

use System\Controller;
use System\Helpers\DataCleanerHelper;
use System\Helpers\HTTPHelper;
use System\Helpers\FileHelper;
use System\Providers\Files\MediaFileProvider;
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
    public function ImageConvert()
    {
        $viewModel = new ViewModel();
        if (HTTPHelper::isFile('file')) {
             $temp = MediaFileProvider::temp('file');
            $image = $temp->print();
        }
        else if (HTTPHelper::isGet('url')) {
            $url = HTTPHelper::get('url');
            $ch = curl_init($url);
            $options = array(

                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "", 
                CURLOPT_AUTOREFERER => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_MAXREDIRS => 5,
            );
            curl_setopt_array( $ch, $options );
            $content = curl_exec($ch);
            if (! curl_errno($ch)) {
                $info = curl_getinfo($ch);
                if(! empty(($info['content_type'] ?? null))
                && strpos($info['content_type'], 'image') == 0)
                {
                    $image = FileHelper::printImage($content, $info['content_type']);
                }
            }
            curl_close($ch);
        }
        return $this->view('file/imageConverter', $viewModel, ['image' => $image ?? '', 'info' => ($info ?? '')]);
    }
}