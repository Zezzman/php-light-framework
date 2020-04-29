<?php
namespace System\Models;

use System\Interfaces\IUserAuth;
use System\Models\UserModel;
/**
 * 
 */
class UserAuthModel extends UserModel implements IUserAuth
{
    public $password = null;
    
    /**
     * 
     */
    public function hasRequiredSignUpFields(){
        // Validate user values
        if (! isset($this->username)
        || ! is_string($this->username)
        || strlen($this->username) < 5
        || strlen($this->username) > 255) {
            $this->feedback('Invalid username', 0, 'Username');
            return false;
        }
        if (! isset($this->password)
        || ! is_string($this->password)
        || strlen($this->password) < 5
        || strlen($this->password) > 255) {
            $this->feedback('Invalid password', 0, 'Password');
            return false;
        }
        if (! isset($this->email)
        || ! is_string($this->email)
        || strlen($this->email) > 255
        || ! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->feedback('Invalid email', 0, 'Email');
            return false;
        }
        if (! isset($this->firstName)
        || ! is_string($this->firstName)
        || strlen($this->firstName) > 255) {
            $this->feedback('Invalid first name', 0, 'FirstName');
            return false;
        }
        if (! isset($this->lastName)
        || ! is_string($this->lastName)
        || strlen($this->lastName) > 255) {
            $this->feedback('Invalid last name', 0, 'LastName');
            return false;
        }
        return true;
    }
    /**
     * 
     */
    public function hasRequiredLoginFields(){
        $pass = true;

        // Validate user values
        if (! isset($this->username)
        || ! is_string($this->username)) {
            $this->feedback('Invalid username', 0, 'Username');
            $pass = false;
        }
        if (strlen($this->username) < 5) {
            $this->feedback('Username too short', 0, 'Username');
            $pass = false;
        } elseif (strlen($this->username) > 255) {
            $this->feedback('Username too long', 0, 'Username');
            $pass = false;
        }

        if (! isset($this->password)
        || ! is_string($this->password)) {
            $this->feedback('Invalid password', 0, 'Password');
            $pass = false;
        }
        if (strlen($this->password) < 5) {
            $this->feedback('Password too short', 0, 'Password');
            $pass = false;
        } elseif (strlen($this->password) > 255) {
            $this->feedback('Password too long', 0, 'Password');
            $pass = false;
        }
        return $pass;
    }
}