<?php
namespace System\Controllers;

use System\Controller;
use System\ViewModels\ViewModel;
/**
 * 
 */
final class HomeController extends Controller
{
    public function Index()
    {
        return $this->view('home');
    }
    public function Document()
    {
        $viewModel = new ViewModel();
        return $this->view('document', $viewModel);
    }
}