<?php

use OAuth\OAuth2\Service\GitHub;
use OAuth\ServiceFactory;
use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\UriFactory;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('session.gc_maxlifetime', 7*24*3600);

header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);

date_default_timezone_set('America/New_York');

include dirname(__FILE__).'/../API.class.php';

define("ORGANIZATION_WHITELIST_PATH", dirname(__FILE__)."/../../organizationWhitelist.txt");
define("USER_TO_SERVER_RATIO", 100);
define("WORKER_LIMIT", 50);

class WebsiteAPI extends API{
    public function __construct($request) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->loadConfig();
        $this->initDB();
        $this->sanitizeHTTPParameters();

        parent::__construct($request);
    }
    
    /* Apply MYSQL sanitization to all incoming parameters
     * 
     * TODO: take this out, switch to just sanitizing when necessary
     * Sanitizing everything breaks json parsing of params
     * Also, bad practice
     */
    private function sanitizeHTTPParameters() {
        foreach ($_POST as $key => $value) {
            $_POST[$key] = $this->mysqli->real_escape_string($value);
        }
    }
    
    // Gets the id of one of our users in the discourse forums system
    private function getForumsID($userID) {
        $url = "http://forums.halite.io/users/by-external/{$userID}.json/?".http_build_query(array('api_key' => $this->config['forums']['apiKey'], 'api_username' => $this->config['forums']['apiUsername']));
        $contents = file_get_contents($url);
        return intval(json_decode($contents, true)['user']['id']);
    }
    
    // Log a users out of forums.halite.io
    private function logOutForums($forumsID) {
        $options = array('http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query(array('api_key' => $this->config['forums']['apiKey'], 'api_username' => $this->config['forums']['apiUsername']))
        ));
        file_get_contents("http://forums.halite.io/admin/users/{$forumsID}/log_out", false, stream_context_create($options));
    }

    private function isLoggedIn() {
        return isset($_SESSION['userID']) && mysqli_query($this->mysqli, "SELECT * FROM User WHERE userID={$_SESSION['userID']}")->num_rows == 1;
    }

    private function getUsers($query, $privateInfo=false) {
        $users = $this->selectMultiple($query);
        $numUsers = $this->numRows("SELECT COUNT(*) FROM User WHERE isRunning=1");
        foreach($users as &$user) {
            if($privateInfo == false) {
                unset($user['email']);
                unset($user['githubEmail']);
                unset($user['verificationCode']);
            }
            
            if(intval($user['isRunning']) == 1) {
                $percentile = intval($user['rank']) / $numUsers;
                if($percentile < 1/64) $user['tier'] = "Diamond";
                else if($percentile < 1/16) $user['tier'] = "Gold";
                else if($percentile < 1/4) $user['tier'] = "Silver";
                else $user['tier'] = "Bronze";
                

                $user['score'] = round(floatval($user['mu']) - 3*floatval($user['sigma']), 2);
            }
        }
        return $users;
    }

    private function getLoggedInUser() {
        if(isset($_SESSION['userID'])) return $this->getUsers("SELECT * FROM User WHERE userID={$_SESSION['userID']}", true)[0];
    }

    /**
     * Helper to interface with the database and get high schools based on the filters.
     */
    private function getHS($name=null, $state=null) {
        $query_string = "SELECT * FROM HighSchool ";
        if(!empty($name) && isset($name)) {
            $query_string = $query_string."WHERE name='".$this->mysqli->real_escape_string($name)."' ";
        } 
        if(!empty($state) && isset($state)) {
            $query_string = $query_string.(!empty($name) && isset($name)?"AND":"WHERE")." state='".$this->mysqli->real_escape_string($state)."' ";
        }
        return $this->selectMultiple($query_string."ORDER BY name ASC");
    }

    private function getOrganizationForEmail($email) {
        $emailDomain = explode('@', $email)[1];
        $rows = explode("\n", rtrim(file_get_contents(ORGANIZATION_WHITELIST_PATH)));
        foreach($rows as $row) {
            $components = explode(" - ", $row);
            if(strcmp($components[1], $emailDomain) == 0) {
                return $components[0];
            }
        }
        return "Other";
    }


    //------------------------------------- API ENDPOINTS ----------------------------------------\\
    // Endpoint associated with a users credentials (everything in the User table; i.e. name, oauthID, etc.)
    // -------------------------------------------------------------------------------------------\\

    /* User Endpoint
     *
     * Encapsulates user information.
     */ 
    protected function user() {
        // Get a user's info with a username        
        if(isset($_GET["username"])) {
            $results = $this->getUsers("SELECT * FROM User WHERE username = '".$this->mysqli->real_escape_string($_GET['username'])."'");
            if(count($results) > 0) return $results[0];
            else return null;
        } 
        
        // Get a user's info with a userID
        else if (isset($_GET["userID"])) {
            $results = $this->getUsers("SELECT * FROM User WHERE userID = ".intval($_GET['userID']), isset($_SESSION['userID']) && $_GET['userID'] == $_SESSION['userID']);
            if(count($results) > 0) return $results[0];
            else return null;
        } 
        
        // Get a set of filtered users
        else if(isset($_GET['fields']) && isset($_GET['values'])) {
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $whereClauses = array_map(function($a) {
                return $this->mysqli->real_escape_string($_GET['fields'][$a])." = '".$this->mysqli->real_escape_string($_GET['values'][$a])."'";
            }, range(0, count($_GET['fields'])-1));
            $orderBy = isset($_GET['orderBy']) ? $this->mysqli->real_escape_string($_GET['orderBy']) : 'rank';
            $page = isset($_GET['page']) ? intval($_GET['page']) : 0;

            $results = $this->getUsers("SELECT * FROM User WHERE ".implode(" and ", $whereClauses)." ORDER BY ".$orderBy." LIMIT ".$limit." OFFSET ".($limit*$page));
            $isNextPage = count($this->getUsers("SELECT * FROM User WHERE ".implode(" and ", $whereClauses)." ORDER BY ".$orderBy." LIMIT 1 OFFSET ".($limit*($page+1)))) > 0;

            foreach(array_keys($results) as $key) unset($results[$key]["email"]);

            return array("isNextPage" => $isNextPage, "users" => $results);
        } 

        // Get all of the user's with active submissions
        else if(isset($_GET['active'])) {
            return $this->getUsers("SELECT * FROM User WHERE isRunning=1");
        } 
        
        // Github calls this once a user has granted us access to their profile info
        if(isset($_GET["githubCallback"]) && isset($_GET["code"])) {
            $code = $_GET["code"];

            $serviceFactory = new ServiceFactory();
            $credentials = new Credentials($this->config['oauth']['githubClientID'], $this->config['oauth']['githubClientSecret'], NULL);
            $gitHub = $serviceFactory->createService('GitHub', $credentials, new Session(), array('user', 'user:email'));
            $gitHub->requestAccessToken($code);
            $githubUser = json_decode($gitHub->request('user'), true);
            $email = json_decode($gitHub->request('user/emails'), true)[0];

            if($this->numRows("SELECT COUNT(*) FROM User WHERE oauthProvider=1 and oauthID={$githubUser['id']}") > 0) { // Already signed up
                
                $_SESSION['userID'] = $this->select("SELECT userID FROM User WHERE oauthProvider=1 and oauthID={$githubUser['id']}")['userID'];
            } else { // New User
                $numActiveUsers = $this->numRows("SELECT COUNT(*) FROM User WHERE isRunning=1"); 
                $this->insert("INSERT INTO User (username, githubEmail, oauthID, oauthProvider, rank) VALUES ('{$githubUser['login']}', '{$email}', {$githubUser['id']}, 1, {$numActiveUsers})");
                $_SESSION['userID'] = $this->mysqli->insert_id;
            }

            if(isset($_GET['redirectURL'])) header("Location: {$_GET['redirectURL']}");
            else header("Location: ".WEB_DOMAIN);
            die();
        } 
    }

    /* Email Endpoint
     *
     * Hitting this endpoint allows a user to handle the choosing of their email. 
     */
    protected function email() {
        $user = $this->getLoggedInUser();

        if($user != null && isset($_GET['validate'])) {
            $organization = $this->getOrganizationForEmail($user["githubEmail"]);
            $this->insert("UPDATE User SET email=githubEmail, organization='$organization', isEmailGood=1 WHERE userID = {$user['userID']}");
        } else if($user != null && isset($_GET['newEmail'])) {
            $verificationCode = rand(0, 9999999999);
            if(isset($_GET['newLevel']) && $_GET['newLevel'] == 'High School') {
                if(empty($this->getHS($_GET['newInstitution'], null))) { 
                    # The only way this error should occur is if users manually try to game it (i.e.: REST calls)
                    # As such we can just print their input is incorrect rather than getting a better landing page.
                    echo "INVALID INPUT: EITHER INSTITUTION OR SCRIMMAGE ARE NOT FROM AVAILABLE OPTIONS.";
                    die();
                }
                $this->insert("UPDATE User SET email='".$this->mysqli->real_escape_string($_GET['newEmail']).
                "', level='".$this->mysqli->real_escape_string($_GET['newLevel']).
                "', organization='".$this->mysqli->real_escape_string($_GET['newInstitution']).
                "', verificationCode = '{$verificationCode}' WHERE userID = {$user['userID']}");
            } else if(isset($_GET['newLevel'])) {
                $this->insert("UPDATE User SET email='".$this->mysqli->real_escape_string($_GET['newEmail']).
                "', level='".$this->mysqli->real_escape_string($_GET['newLevel']).
                "', organization='".$this->getOrganizationForEmail($this->mysqli->real_escape_string($_GET['newEmail'])).
                "', verificationCode = '{$verificationCode}' WHERE userID = {$user['userID']}");
            } else {
                $this->insert("UPDATE User SET email='".$this->mysqli->real_escape_string($_GET['newEmail'])."', verificationCode = '{$verificationCode}' WHERE userID = {$user['userID']}");
            }

            $user["email"] = $_GET["newEmail"];
            $this->sendNotification($user, "Email Verification", "<p>Click <a href='".WEB_DOMAIN."api/web/email?verificationCode=$verificationCode'>here</a> to verify your email address.</p>", 0, false, true);
        } else if(isset($_GET['verificationCode'])) {
            if($user == null) {
                $emailCallbackURL = urlencode(WEB_DOMAIN."api/web/email?".$_SERVER['QUERY_STRING']);
                $githubCallbackURL = urlencode(WEB_DOMAIN."api/web/user?githubCallback=1&redirectURL={$emailCallbackURL}");
                header("Location: https://github.com/login/oauth/authorize?scope=user:email&client_id=2b713362b2f331e1dde3&redirect_uri={$githubCallbackURL}");
                die();
            }

            if(strcmp($user['verificationCode'], $_GET['verificationCode']) != 0) {
                return "Invalid verification code";
            }

            $organization = $this->getOrganizationForEmail($user["email"]);
            $this->insert("UPDATE User SET isEmailGood=1, organization='$organization' WHERE userID = {$user['userID']}");

            header("Location: ".WEB_DOMAIN."index.php?emailVerification=1");
            die();
        } 
    }

    /* Email List Endpoint
     *
     * Hitting this endpoint allows a user to subscribe and unsubscribe from all Halite emails. 
     */
    protected function emailList() {
        $user = $this->getLoggedInUser();
        if($user == null) {
            $emailCallbackURL = urlencode(WEB_DOMAIN."api/web/emailList?".$_SERVER['QUERY_STRING']);
            $githubCallbackURL = urlencode(WEB_DOMAIN."api/web/user?githubCallback=1&redirectURL={$emailCallbackURL}");
            header("Location: https://github.com/login/oauth/authorize?scope=user:email&client_id=2b713362b2f331e1dde3&redirect_uri={$githubCallbackURL}");
        }

        if(isset($_GET['unsubscribe'])) {
            $this->insert("UPDATE User SET onEmailList=0 WHERE userID = {$user['userID']}");
            header("Location: ../../index.php?unsubscribeEmails=1");
        } else if(isset($_GET['subscribe'])) {
            $this->insert("UPDATE User SET onEmailList=1 WHERE userID = {$user['userID']}");
            header("Location: ../../index.php?subscribeEmails=1");
        } 

        die();
    }

    /* User History Endpoint
     *
     * We store the a history of user's bot submissions.
     * This mitigates the 'stealth' submission problem, 
     * where users submit a bot, wait for their rank to stabilize, and take it down for the sake of secrecy.
     */
    protected function history() {
        if(isset($_GET["userID"])) {
            return $this->selectMultiple("SELECT * FROM UserHistory WHERE userID=".intval($_GET["userID"])." ORDER BY versionNumber DESC");
        }
    }

    /* High School Endpoint
     *
     * Simple retrieval from the high school store. All participating high schools should be here.
     */
    protected function highSchool() {
        return $this->getHS(isset($_GET["name"])?$_GET["name"]:null, isset($_GET["state"])?$_GET["state"]:null);
    }

    /* User Notification Endpoint
     *
     * Allows the downloading of all of the notifications a user has recieved over email.
     * Notifications include "Compilation Success", "Bot received", etc
     */
    protected function notification() {
        if($this->isLoggedIn()) {
            $userID = $this->getLoggedInUser()['userID'];
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            return $this->selectMultiple("SELECT * FROM UserNotification WHERE userID={$userID} ORDER BY userNotificationID DESC LIMIT $limit");
        }
    }

    /* Game Endpoint
     *
     * Games are continuously run on our servers and exposed on our website
     */
    protected function game() {
        if(isset($_GET['userID'])) {
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
            $startingID = isset($_GET['startingID']) ? intval($_GET['startingID']) : PHP_INT_MAX;
            $userID = intval($_GET['userID']);
            $versionNumber = isset($_GET['versionNumber']) ? intval($_GET['versionNumber']) : $this->select("SELECT numSubmissions FROM User WHERE userID=$userID")['numSubmissions']; 

            $gameArrays = $this->selectMultiple("SELECT g.* FROM GameUser gu INNER JOIN Game g ON g.gameID = gu.gameID WHERE gu.userID = $userID and gu.versionNumber = $versionNumber and gu.gameID < $startingID ORDER BY gu.gameID DESC LIMIT $limit");
        } else {
            $previousID = isset($_GET['previousID']) ? intval($_GET['previousID']) : 0;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $gameArrays = $this->selectMultiple("SELECT * FROM Game WHERE gameID > $previousID ORDER BY gameID DESC LIMIT $limit"); 
        }

        // Get each game's info
        foreach ($gameArrays as &$gameArray) {
            $gameID = $gameArray['gameID'];

            // Get information about users
            $gameArray['users'] = $this->selectMultiple("SELECT gu.userID, gu.versionNumber, gu.errorLogName, gu.rank, u.username, u.oauthID, u.mu, u.sigma, u.rank AS userRank FROM GameUser gu INNER JOIN User u ON u.userID=gu.userID WHERE gu.gameID = $gameID");
        }
        return $gameArrays;
    }
    
    /* Bot File Endpoint
     *
     * Handles the user's submission of a new bot
     */
    protected function botFile() {
        // Mark a new botfile for compilation if valid. Return error otherwise 
        if($this->isLoggedIn() && isset($_FILES['botFile']['name'])) {
            $user = $this->getLoggedInUser();

            if(isset($this->config["compState"]["closeSubmissions"]) && $this->config["compState"]["closeSubmissions"]) {
                return "Sorry, bot submissions are closed.";
            }
            
            if($user['compileStatus'] != 0) {
                return "Compiling";
            }
            
            if ($_FILES["botFile"]["size"] > 20000000) {
                $megabytes = $_FILES["botFile"]["size"]/1000000;
                $this->sendNotification($user, "Bot TOO LARGE", "<p>Your bot archive was {$megabytes} Megabytes. Our limit on bot zip files is 20 Megabytes.</p>", -1);
                return "Sorry, your file is too large.";
            }

            $this->loadAwsSdk()->createS3()->putObject([
                'Key'    => "{$user['userID']}",
                'Body'   => file_get_contents($_FILES['botFile']['tmp_name']),
                'Bucket' => COMPILE_BUCKET
            ]);
            $this->insert("UPDATE User SET compileStatus = 1 WHERE userID = {$user['userID']}");

            if(intval($this->config['test']['isTest']) == 0) $this->sendNotification($user, "Bot Received", "<p>We have received and processed the zip file of your bot's source code. In a few minutes, our servers will compile your bot, and you will receive another email notification, even if your bot has compilation errors.</p>", 0);

            // AWS auto scaling
            $numActiveUsers = $this->numRows("SELECT COUNT(*) FROM User WHERE isRunning=1"); 
            $numWorkers = $this->numRows("SELECT COUNT(*) FROM Worker");
            if($numWorkers > 0 && $numWorkers < WORKER_LIMIT && $numActiveUsers / $numWorkers > USER_TO_SERVER_RATIO) {
                echo shell_exec("python3 openNewWorker.py > /dev/null 2>/dev/null &");
            }

            return "Success";
        }
    }
    
    /* Forums Endpoint
     *
     * Handle the Discourse forums (forums.halite.io) single sign on authentication.
     */
    protected function forums() {
        // Follows the Discource sso detailed here: https://meta.discourse.org/t/official-single-sign-on-for-discourse/13045
        if(isset($_GET['sso']) && isset($_GET['sig'])) {
            if(!$this->isLoggedIn()) {
                $forumsCallbackURL = urlencode(WEB_DOMAIN."api/web/forums?".http_build_query(array("sig" => $_GET['sig'], "sso" => $_GET['sso'])));
                $githubCallbackURL = urlencode(WEB_DOMAIN."api/web/user?githubCallback=1&redirectURL={$forumsCallbackURL}");
                header("Location: https://github.com/login/oauth/authorize?scope=user:email&client_id=2b713362b2f331e1dde3&redirect_uri={$githubCallbackURL}");
                die();
            }

            $user = $this->getLoggedInUser();


            $initialBase64Payload = stripcslashes($_GET['sso']);
            $signature = $_GET['sig'];

            $correctSignature = hash_hmac("sha256", $initialBase64Payload, $this->config['sso']['secret']);

            if($correctSignature != $signature) {
                return null;
            }

            parse_str(base64_decode($initialBase64Payload), $initialPayload);
            $nonce = $initialPayload["nonce"];

            $finalBase64Payload = base64_encode(http_build_query(array(
                "nonce" => $nonce,
                "name" => $user['username'],
                "email" => $user['email'],
                "external_id" => $user['userID']
            )));
            $finalSignature = hash_hmac("sha256", $finalBase64Payload, $this->config['sso']['secret']);

            $finalQueryString = http_build_query(array(
                "sso" => $finalBase64Payload,
                "sig" => $finalSignature
            ));
            $finalURL = $this->config['sso']['url']."?".$finalQueryString;

            header("Location: ".$this->config['sso']['url']."?".$finalQueryString);
            die();
        }
    }
    
    /* Worker Endpoint
     *
     * Bots are compiled and run in games by a decentralized network of 'worker' servers.
     * A few stats about each worker are recorded by our 'manager' server.
     * These stats are exposed on our status page.
     */
    protected function worker() {
        $workers = $this->selectMultiple("SELECT * FROM Worker ORDER BY workerID");
        return $workers;
    }
    
    /* Stats endpoint
     *
     * Provides a number of statistics about the competition,
     * which would be expensive or impossible to determine using our generic endpoints.
     */ 
    protected function stats() {
        if(isset($_GET['throughput'])) {
            return mysqli_query($this->mysqli, "SELECT * FROM Game WHERE TIMESTAMPDIFF(DAY, timestamp, NOW()) < 1")->num_rows;
        }

        // Get the number of active users
        else if(isset($_GET['numSubmissions'])) {
            return $this->select("SELECT SUM(numSubmissions) FROM User")["SUM(numSubmissions)"];
        }

        // Get the number of active users
        else if(isset($_GET['numActive'])) {
            return mysqli_query($this->mysqli, "SELECT userID FROM User WHERE isRunning=1")->num_rows;
        } 

        // Get the median mu and sigma of active users
        else if(isset($_GET['scoreMedians'])) {
            $medians = array();
            $medians["mu"] = $this->select("SELECT AVG(tbl.mu) as medianMu FROM (
                SELECT @rownum:=@rownum+1 as row, mu FROM User, (SELECT @rownum:=0) rn
                    WHERE isRunning=1 ORDER BY mu
                ) as tbl
                WHERE tbl.row in (floor((@rownum+1)/2), floor((@rownum+2)/2))")["medianMu"];

            $medians["sigma"] = $this->select("SELECT AVG(tbl.sigma) as medianSigma FROM (
                SELECT @rownum:=@rownum+1 as row, sigma FROM User, (SELECT @rownum:=0) rn
                    WHERE isRunning=1 ORDER BY sigma
                ) as tbl
                WHERE tbl.row in (floor((@rownum+1)/2), floor((@rownum+2)/2))")["medianSigma"];
            return $medians;
        }
    }

    /* Announcement Endpoint
     *
     * Annoucements are used as 'news blasts' without requiring email.
     * An alert with the annoucement's body and header is showed to users.
     * Once it has been closed, it is never shown to that user again
     */
    protected function announcement() {
        // Get the newest annoucement available to a user
        if(isset($_GET['userID'])) {
            return $this->select("SELECT a.* FROM Announcement a WHERE NOT EXISTS(SELECT NULL FROM DoneWithAnnouncement d WHERE d.userID = ".intval($_GET['userID'])." and d.announcementID = a.announcementID) ORDER BY announcementID LIMIT 1;");
        } 
        
        // Mark an annoucement as closed    
        else if(isset($_POST['announcementID'])) {
            $announcementID = intval($_POST['announcementID']);
            $user = $this->getLoggedInUser();

            if(count($this->select("SELECT * FROM User WHERE user={$user['userID']} LIMIT 1")) > 0) {
                $this->insert("INSERT INTO DoneWithAnnouncement (userID, announcementID) VALUES ({$user['userID']}, $announcementID)");
                return "Success";
            }
            return "Fail";
        }
    }
    
    /* Error Log Endpoint
     *
     * When a users times-out or errors during a game of Halite on our server,
     * we store their stdout and stderr output and make it available to them.
     * Users may only see **their** error logs.
     */
    protected function errorLog() {
        // Return the requested error log only if it belongs to the signed in user.
        if(isset($_GET['errorLogName']) && isset($_SESSION['userID']) && count($this->select("SELECT * FROM GameUser WHERE errorLogName='".$this->mysqli->real_escape_string($_GET['errorLogName'])."' and userID={$_SESSION['userID']}"))) {
            $result = $this->loadAwsSdk()->createS3()->getObject([
                'Bucket' => ERROR_LOG_BUCKET,
                'Key'    => $_GET['errorLogName']
            ]);

            header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
            header("Cache-Control: public"); // needed for internet explorer
            header("Content-Type: application/txt");
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length:".strlen($result['Body']));
            header("Content-Disposition: attachment; filename=error.log");
            echo $result['Body'];
            die();
        }
        return "You aren't logged into your Halite account or are trying to access the error log of another contestant.";
    }
    
    /* Session Endpoint
     *
     * Encapsulates the logged in user's info
     */
    protected function session() {

        // Get the logged in user's info
        if($this->method == 'GET') {
            if(count($_SESSION) > 0) return $_SESSION;
            else return NULL;
        } 
        
        // Log out a user
        else if($this->method == 'DELETE') {
            if(isset($_SESSION['userID'])) {
                $this->logOutForums($this->getForumsID($_SESSION['userID']));
            }
            session_destroy();
            return "Success";
        }
    }
 }

 ?>
