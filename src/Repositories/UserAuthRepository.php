<?php
namespace System\Repositories;

use System\Interfaces\IUserAuth;
use System\Traits\Feedback;
/**
 * access and store information i.e. interact with database
 */
class UserAuthRepository extends UserRepository
{
    use Feedback;
    /**
     * 
     */
    public function getUserAuthWithUsername(string $username){
        if (is_null($this->connection())) {
            $this->feedback('No database connection');
            return false;
        }

        $sql = "SELECT username, password"
                ." FROM vw_users_auth"
                ." WHERE username LIKE :username";
        $result = $this->connection()::prepare($sql, ['username' => $username], \PDO::FETCH_ASSOC);
        if (is_array($result)) {
            return $result;
        }
        $this->feedback('Username does not exists', 0, 'Username');
        return false;
    }
    /**
     * 
     */
    public function addUser(IUserAuth $user){
        if (is_null($this->connection())) {
            $this->feedback('No database connection');
            return false;
        }
        if ($user->hasRequiredSignUpFields()) {
            // add new user to database
            $sql = "CALL sp_users_addUser(:username, :password, :email, :firstName, :lastName)";
            $result = $this->connection()::prepare($sql, [
                'username' => $user->username,
                'password' => $user->password,
                'email' => $user->email,
                'firstName' => $user->firstName,
                'lastName' => $user->lastName
            ]);
            if ($result !== false) {
                return true;
            } else {
                $this->feedback('Failed to create user');
                return false;
            }
        } else {
            $this->mergeFeedback($user);
        }
        $this->feedback('Required fields not provided', 0, 'Required');
        return false;
    }
}