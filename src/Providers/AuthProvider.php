<?php
namespace System\Providers;

use System\Traits\Feedback;
use System\Interfaces\IUserAuth;
use System\Repositories\UserAuthRepository;
use System\Helpers\HTTPHelper;
use System\Helpers\DataCleanerHelper;
use System\Models\UserAuthModel;
use System\Providers\SessionProvider;
use System\Exceptions\RespondException;
/**
 * Provides Authorization of users
 * 
 * Allows users to login, signUp or signOut
 */
final class AuthProvider
{
    use Feedback;

    private function getUserPasswordWithUsername(string $username)
    {
        $repo = new UserAuthRepository();
        $user = $repo->getUserAuthWithUsername($username);
        if (is_array($user)) {
            if (isset($user['password'])) {
                return $user['password'];
            } else {
                $this->feedback('No password found');
                return false;
            }
        } else {
            $this->mergeFeedback($repo);
            return false;
        }
    }

    public function getLoginAuth()
    {
        $data = $this->getLoginPost();
        if (is_array($data)) {
            $user = new UserAuthModel();
            $user->username = $data['username'];
            $user->password = $data['password'];
            return $user;
        }
        return false;
    }
    private function getLoginPost()
    {
        // Get Post Authorization details
        $post = HTTPHelper::post();
        if (is_array($post)) {
            $post = DataCleanerHelper::cleanArray($post);
            return $post;
        }
        $this->feedback('No post data');
        return false;
    }
    public function verifyLoginAuth(IUserAuth $user)
    {
        if ($user->hasRequiredLoginFields()) {
            $username = $user->username;
            $password = $user->password;
            $hashPassword = $this->getUserPasswordWithUsername($username);
            if (is_string($hashPassword) && ! empty($hashPassword)) {
                if (password_verify($password, $hashPassword)) {
                    return true;
                } else {
                    $this->feedback('Incorrect password', 0, 'Password');
                    return false;
                }
            } else {
                $this->feedback('Invalid Hash format', 1, 'HashPassword');
                return false;
            }
        } else {
            $this->mergeFeedback($user);
            return false;
        }
    }
    public function loginAuth(IUserAuth $user)
    {
        if ($this->authorize($user)){
            return true;
        } else {
            return false;
        }
    }

    public function getSignUpAuth()
    {
        // get post data
        $data = $this->getSignUpPost();
        if (is_array($data)) {
            // generate model
            $user = new UserAuthModel();
            $user->username = $data['username']?? null;
            $user->password = $data['password']?? null;
            $user->email = $data['email']?? null;
            $user->firstName = $data['first_name']?? null;
            $user->lastName = $data['last_name']?? null;
            return $user;
        }
        return false;
    }
    private function getSignUpPost()
    {
        // Get Post Authorization details
        $post = HTTPHelper::post();
        if (is_array($post)) {
            $post = DataCleanerHelper::cleanArray($post);
            // Hash Password field
            if (isset($post['password'])) {
                $post['password'] = password_hash($post['password'], PASSWORD_DEFAULT);
            }
            return $post;
        }
        $this->feedback('No post data');
        return false;
    }
    public function verifySignUpAuth(IUserAuth $user)
    {
        if ($user->hasRequiredSignUpFields()) {
            $repo = new UserAuthRepository();
            if (! $repo->uniqueUsername($user->username)) {
                $this->feedback('Username is not unique', 0, 'Username');
                return false;
            }
            if (! $repo->uniqueEmail($user->email)) {
                $this->feedback('Email is not unique', 0, 'Email');
                return false;
            }
            return true;
        } else {
            $this->mergeFeedback($user);
            return false;
        }
    }
    public function signUpAuth(IUserAuth $user)
    {
        if ($this->createUser($user)) {
            // login
            if ($this->authorize($user)){
                return true;
            }
        }
        return false;
    }
    private function createUser(IUserAuth $user)
    {
        $repo = new UserAuthRepository();
        if ($repo->addUser($user)) {
            return $user;
        }
        $this->mergeFeedback($repo);
        return false;
    }

    private function authorize(IUserAuth $user)
    {
        // log user in with new session
        SessionProvider::resetSession();
        if (SessionProvider::set('username', $user->username)) {
            return true;
        } else {
            throw new RespondException(500, 'No active session');
        }
    }
    /**
     * Is User already authorized
     * 
     * @return  bool    return true if username session is set
     */
    public static function isAuthorized(int $access = -1)
    {
        SessionProvider::startSession();
        if (SessionProvider::hasSession()) {
            if (isset($_SESSION['username'])) {
                if ($access > -1) {
                    return isset($_SESSION['access_level']) 
                        && $_SESSION['access_level'] === $access;
                }
                return true;
            }
        }
        return false;
    }
    public static function signOut()
    {
        if (self::isAuthorized()) {
            SessionProvider::destroySession();
            return true;
        }
        return false;
    }
}