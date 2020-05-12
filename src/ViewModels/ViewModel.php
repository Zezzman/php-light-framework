<?php
namespace System\ViewModels;

use System\Controller;
use System\Interfaces\IViewModel;
use System\Traits\Feedback;
use System\Helpers\QueryHelper;
use Exception;
/**
 * 
 */
class ViewModel implements IViewModel
{
    use Feedback;

    private $title = '';

    function getTitle()
    {
        return $this->title;
    }
    function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }

    public function messages($types = 0, string $name = '', string $style = "{message}<br>", int $length = 0)
    {
        $message = '';
        $types = (array)$types;
        foreach ($types as $type)
        {
            if ($name !== '') {
                $message .= QueryHelper::scanCodes($this->feedbackWithName($type, $name) ?? [], $style, [], true, $length);
            }
            else
            {
                $message .= QueryHelper::scanCodes($this->feedbackWithType($type) ?? [], $style, [], true, $length);
            }
        }
        return $message;
    }
    public function respond(int $code, $types = 0, string $name = '', string $style = "{message}<br>", int $length = 0, Exception $exception = null)
    {
        Controller::respond($code, $this->messages($types, $name, $style, $length), null, $exception);
        exit();
    }
}