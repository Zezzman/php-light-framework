<?php
namespace System\Models;

use System\Interfaces\IUser;
use System\Traits\Feedback;
/**
 * 
 */
class UserModel implements IUser
{
    use Feedback;

    public $id = null;
    public $username = null;
    public $email = null;
    public $firstName = null;
    public $lastName = null;

    /**
     * 
     */
    public function hasRequiredUpdateFields(){
        if (! is_string($this->username)
        || empty($this->username)) {
            $this->feedback('Invalid username');
            return false;
        }
        if (! is_string($this->email)
        || empty($this->email)) {
            $this->feedback('Invalid email');
            return false;
        }
        if (! is_string($this->firstName)
        || empty($this->firstName)) {
            $this->feedback('Invalid first name');
            return false;
        }
        if (! is_string($this->lastName)
        || empty($this->lastName)) {
            $this->feedback('Invalid last name');
            return false;
        }
        return true;
    }
}