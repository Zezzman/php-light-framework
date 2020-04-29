<?php
namespace System\Traits;

use System\Helpers\QueryHelper;
use System\Helpers\ArrayHelper;
/**
 * 
 */
trait Feedback
{
    protected $feedbackTypes = ['message', 'warning', 'error'];
    protected $feedbacks = [];

    public function feedback(string $message, int $type = 0, string $name = '')
    {
        if ($type >= 0 && $type < count($this->feedbackTypes)) {
            $this->feedbacks[$this->feedbackTypes[$type]][] = ['type' => $this->feedbackTypes[$type], 'name' => $name, 'message' => $message];
        }
    }
    public function mergeFeedback($feedback)
    {
        if (is_array($feedback)) {
            $this->feedbacks = array_merge($this->feedbacks, $feedback);
        } else if (method_exists($feedback, 'hasFeedback')) {
            if ($feedback->hasFeedback()) {
                $this->feedbacks = array_merge($this->feedbacks, $feedback->getFeedback());
            }
        }
    }
    public function getFeedback()
    {
        return $this->feedbacks;
    }
    public function printFeedback(string $style = "{message}\n", int $length = 0)
    {
        return QueryHelper::scanCodes(ArrayHelper::outerMerge($this->feedbacks) ?? [], $style, [], true, $length);
    }
    public function hasFeedback()
    {
        return (count($this->feedbacks) > 0);
    }
    public function hasFeedbackWithName(int $type, string $name)
    {
        return ! is_null($this->feedbackWithName($type, $name));
    }
    public function feedbackWithName(int $type, string $name)
    {
        $feedbacks = $this->feedbackWithType($type);
        if (! is_null($feedbacks)) {
            foreach ($feedbacks as $feedback) {
                if ($feedback['name'] === $name) {
                    return $feedback;
                }
            }
        }
        
        return null;
    }
    public function hasFeedbackWithType($types)
    {
        $types = (array)$types;
        $hasType = false;
        foreach ($types as $type)
        {
            if (! is_null($this->feedbackWithType($type)))
            {
                $hasType = true;
            }
        }
        return $hasType;
    }
    public function feedbackWithType(int $type)
    {
        if (isset($this->feedbacks[$this->feedbackTypes[$type]])) {
            return $this->feedbacks[$this->feedbackTypes[$type]];
        }
        
        return null;
    }
}