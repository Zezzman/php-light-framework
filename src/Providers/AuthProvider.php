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
        if (! is_array($user))
        {
            $this->mergeFeedback($repo);
            return false;
        }
        if (! isset($user['password'])) {
            $this->feedback('No password found');
            return false;
        }
        return $user['password'];
    }

    /**
     * Get Login Details From Post
     */
    public function getLoginPostAuth()
    {
        // Get Post Authorization details
        $data = DataCleanerHelper::cleanArray(HTTPHelper::post());
        if (! is_array($data)) {
            $this->feedback('No post data');
            return false;
        }
        
        $user = new UserAuthModel();
        $user->username = $data['username'];
        $user->password = $data['password'];
        return $user;
    }
    public function verifyLoginAuth(IUserAuth $user)
    {
        if (! $user->hasRequiredLoginFields())
        {
            $this->mergeFeedback($user);
            return false;
        }
        $username = $user->username;
        $password = $user->password;
        $hashPassword = $this->getUserPasswordWithUsername($username);
        if (! is_string($hashPassword) || empty($hashPassword))
        {
            $this->feedback('Invalid Hash format', 1, 'HashPassword');
            return false;
        }
        if (! password_verify($password, $hashPassword))
        {
            $this->feedback('Incorrect password', 0, 'Password');
            return false;
        }
        return true;
    }
    public function loginAuth(IUserAuth $user, int $day = null)
    {
        return $this->authorize($user, $days);
    }

    /**
     * Get SignUp Details From Post
     */
    public function getSignUpPostAuth()
    {
        $data = DataCleanerHelper::cleanArray(HTTPHelper::post());
        if (! is_array($data)) {
            $this->feedback('No post data');
            return false;
        }
        
        $user = new UserAuthModel();
        $user->username = $data['username'] ?? null;
        $user->password = password_hash(($data['password'] ?? ''), PASSWORD_DEFAULT);
        $user->email = $data['email'] ?? null;
        $user->firstName = $data['first_name'] ?? null;
        $user->lastName = $data['last_name'] ?? null;
        return $user;
    }
    public function verifySignUpAuth(IUserAuth $user)
    {
        if (! $user->hasRequiredSignUpFields()) {
            $this->mergeFeedback($user);
            return false;
        }
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
    }
    public function signUpAuth(IUserAuth $user)
    {
        if (! $this->createUser($user)) return false;

        return $this->authorize($user);
    }
    private function createUser(IUserAuth $user)
    {
        $repo = new UserAuthRepository();
        if (! $repo->addUser($user)) {
            $this->mergeFeedback($repo);
            return false;
        }
        return $user;
    }

    private function authorize(IUserAuth $user, int $days = null)
    {
        $username = $user->username;
        $token = SessionProvider::generateKeyword($user->username);
        // log user in with new session
        SessionProvider::resetSession($days);
        if (SessionProvider::set('username', $username)
        && SessionProvider::set('session_token', $token)) {
            // Update database login token
            $repo = new UserAuthRepository();
            return $repo->updateSession($token);
        } else {
            SessionProvider::destroySession();
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
        if (SessionProvider::hasSession()) return false;
        if ($access <= -1) return true;

        return (isset($_SESSION['access_level']) && $_SESSION['access_level'] === $access);
    }
    /**
     * Compare Login Token to Database Token
     */
    public function authCheck()
    {
        SessionProvider::startSession();
        if (empty($username = SessionProvider::get('username'))) return false;
        if (empty($session_token = SessionProvider::get('session_token'))) return false;

        $repo = new UserAuthRepository();
        $userData = $repo->getUserAuthWithUsername($username);
        if (! $userData || $userData['session_token'] !== $token)
        {
            SessionProvider::destroySession();
            return false;
        }
        return true;
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