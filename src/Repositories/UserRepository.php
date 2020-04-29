<?php
namespace System\Repositories;

use System\Interfaces\IUser;
use System\Repository;
use System\Traits\Feedback;
use System\Helpers\QueryHelper;
use System\Helpers\ArrayHelper;
use System\Providers\DatabaseProvider;
/**
 * access and store information i.e. interact with database
 */
class UserRepository extends Repository
{
    use Feedback;

    public function connect()
    {
        return DatabaseProvider::connectMySQL();
    }
    /**
     * 
     */
    public function getUserWithUsername(string $username){
        if (is_null($this->connection())) {
            $this->feedback('No database connection');
            return false;
        }

        $sql = "SELECT id, username, email, first_name, last_name"
                ." FROM vw_users"
                ." WHERE username LIKE :username";
        $result = $this->connection()::prepare($sql, ['username' => $username], \PDO::FETCH_ASSOC);
        if (is_array($result)) {
            return $result;
        }
        $this->feedback('Username does not exists', 0, 'UsernameNotExists');
        return false;
    }
    /**
     * 
     */
    public function getUserWithID(int $id){
        if (is_null($this->connection())) {
            $this->feedback('No database connection');
            return false;
        }

        $sql = "SELECT id, username, email, first_name, last_name"
                ." FROM vw_users"
                ." WHERE id LIKE :id";
        $result = $this->connection()::prepare($sql, ['id' => $id], \PDO::FETCH_ASSOC);
        if (is_array($result)) {
            return $result;
        }
        $this->feedback('User does not exists', 0, 'IDNotExists');
        return false;
    }
    /**
     * 
     */
    public function getUserUploadsWithID(int $id){
        if (is_null($this->connection())) {
            $this->feedback('No database connection');
            return false;
        }

        $sql = "SELECT user_id, file_path, upload_timestamp, updated_timestamp"
                ." FROM vw_user_uploads"
                ." WHERE user_id LIKE :user_id";
        $result = $this->connection()::prepare($sql, ['user_id' => $id], \PDO::FETCH_ASSOC, true);
        if (is_array($result)) {
            return $result;
        }
        $this->feedback('User does not exists', 0, 'IDNotExists');
        return false;
    }
    /**
     * 
     */
    public function updateUserFromID(int $id, IUser $user){
        if (is_null($this->connection())) {
            $this->feedback('No database connection');
            return false;
        }
        
        if ($id > 0) {
            if ($user->hasRequiredUpdateFields()) {
                $sql = "CALL sp_users_updateUser(:id, :username, :email, :first_name, :last_name)";
                $result = $this->connection()::prepare($sql, [
                    'id' => $id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'first_name' => $user->firstName,
                    'last_name' => $user->lastName,
                ]);
                if ($result !== false) {
                    return true;
                } else {
                    $this->feedback('Failed to update user');
                    return false;
                }
            }
            $this->feedback('Required fields not provided', 0, 'Required');
            return false;
        }
        
        $this->feedback('Invalid user id');
        return false;
    }
    /**
     * 
     */
    public function getUsers(){
        if (is_null($this->connection())) {
            $this->feedback('No database connection');
            return false;
        }

        $sql = "SELECT id, username, email, first_name, last_name"
                ." FROM vw_users";
        $result = $this->connection()::prepare($sql, null, \PDO::FETCH_ASSOC, true);
        if (is_array($result)) {
            return $result;
        }
        $this->feedback('No users found');
        return false;
    }
    /**
     * 
     */
    public function getUsersWithID(array $id){
        if (is_null($this->connection())) {
            $this->feedback('No database connection');
            return false;
        }
        $id = ArrayHelper::collectKeys($id, ['id']);
        if (count($id) > 0) {
            $id = ArrayHelper::customKeys($id, 'id');
            $sql = ("SELECT id, username, email, first_name, last_name"
                    ." FROM vw_users"
                    ." WHERE " . QueryHelper::arrayToStatements($id, 'id', 'LIKE', 'OR'));
            $result = $this->connection()::prepare($sql, $id, \PDO::FETCH_ASSOC, true);
            if (is_array($result)) {
                return $result;
            } else {
                $this->feedback('Users does not exists', 0, 'IDNotExists');
                return false;
            }
        } else {
            $this->feedback('No IDs given');
            return false;
        }
    }
    /**
     * 
     */
    public function uniqueUsername(string $username){
        
        if (is_null($this->connection())) {
            $this->feedback('No database connection');
            return false;
        }
        $sql = "CALL sp_users_usernameExist(:username, @exists)";
        $result = $this->connection()::prepare($sql, [
            'username' => $username
        ]);
        $sql = "SELECT @exists as 'exists'";
        $result = $this->connection()::prepare($sql, null, \PDO::FETCH_ASSOC);
        if (is_array($result) && isset($result['exists']) && $result['exists'] === 1) {
            $this->feedback('Username already exists', 0, 'Username');
            return false;
        }
        return true;
    }
    /**
     * 
     */
    public function uniqueEmail(string $email){
        
        if (is_null($this->connection())) {
            $this->feedback('No database connection');
            return false;
        }
        $sql = "CALL sp_users_emailExist(:email, @exists)";
        $result = $this->connection()::prepare($sql, [
            'email' => $email
        ]);
        $sql = "SELECT @exists as 'exists'";
        $result = $this->connection()::prepare($sql, null, \PDO::FETCH_ASSOC);
        if (is_array($result) && isset($result['exists']) && $result['exists'] === 1) {
            $this->feedback('Email already exists', 0, 'Email');
            return false;
        }
        return true;
    }
}