<?php
use OliverKlee\Insecurity\Service\DatabaseService;

/**
 * Initializes the login/logout process and logs users in or out if requested.
 *
 * @return void
 */
function checkLoginLogout()
{
    startSession();

    processLogoutIfProvided();
    processNewLoginIfProvided();
    processExistingLogin();
}

/**
 * Starts the session (if it has not been started already).
 *
 * @return void
 */
function startSession()
{
    /** @var bool $sessionHasBeenStarted */
    static $sessionHasBeenStarted = false;
    if (!$sessionHasBeenStarted) {
        session_start();
        $sessionHasBeenStarted = true;
        $GLOBALS['logged_in'] = false;
    }
}

/**
 * Processes a new login request (if there is one).
 *
 * @return void
 */
function processNewLoginIfProvided()
{
    $userId = getUserIdForLoginData();
    if ($userId > 0) {
        logInUser($userId);
    }
}

/**
 * Returns the User ID for the login data provided in the request.
 *
 * @return int the user ID for login or 0 if there is none
 */
function getUserIdForLoginData()
{
    $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
    $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
    if ($email == '' || $password == '') {
        return 0;
    }

    $databaseService = DatabaseService::getInstance();
    $where = "email = \"$email\" AND password = \"$password\"";
    $queryResult = $databaseService->select('insecurity_users', $where);
    $userId = !empty($queryResult) ? $queryResult[0]['id'] : 0;

    return $userId;
}

/**
 * Logs in the user with the ID $id.
 *
 * @param int $id the ID, must be > 0
 *
 * @return void
 */
function logInUser($id)
{
    $_SESSION['logged_in'] = true;
    $GLOBALS['logged_in'] = true;
    setcookie('user_id', $id);
    $_COOKIE['user_id'] = $id;
}

/**
 * Checks whether a login already is in the session and then marks the user as logged in.
 *
 * @return void
 */
function processExistingLogin()
{
    $GLOBALS['logged_in'] = isset($_SESSION['logged_in']) ? (bool)$_SESSION['logged_in'] : false;
}

/**
 * Checks whether a user is logged in.
 *
 * @return bool
 */
function isLoggedIn()
{
    return $GLOBALS['logged_in'];
}

/**
 * Logs the user out if the page is "logout.php".
 *
 * @return void
 */
function processLogoutIfProvided()
{
    if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'logout.php') {
        logOutUser();
    }
}

/**
 * Logs out any user.
 *
 * @return void
 */
function logOutUser()
{
    unset($_SESSION['logged_in']);
    $GLOBALS['logged_in'] = false;
    setcookie('user_id', null);
}

/**
 * Gets the logged-in user (or null if no user is logged in).
 *
 * @return \OliverKlee\Insecurity\Domain\Model\User|null
 */
function getLoggedInUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    $userRepository = \OliverKlee\Insecurity\Domain\Repository\UserRepository::getInstance();
    $userId = $_COOKIE['user_id'];

    return $userRepository->findOneById($userId);
}