<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GOGPHPDL
 * @see https://github.com/lhw/gogdownloader/wiki/API
 * @author K
 */
class GOGPHPDL
{

    const gog_consumer_key       = "1f444d14ea8ec776585524a33f6ecc1c413ed4a5";
    const gog_consumer_sercret   = "20d175147f9db9a10fc0584aa128090217b9cf88";
    const gogdl_api_manifest     = 'https://api.gog.com/en/downloader2/status/stable/';
    const url_login_init         = 'oauth_get_temp_token';
    const url_login              = 'oauth_authorize_temp_token';
    const url_login_verify       = 'oauth_get_token';
    const url_get_extra_link     = 'get_extra_link';
    const url_get_game_details   = 'get_game_details';
    const url_get_installer_link = 'get_installer_link';
    const url_get_user_details   = 'get_user_details';
    const url_get_user_games     = 'get_user_games';
    const url_set_app_status     = 'set_app_status';
    const auth_token             = 'oauth_token';
    const auth_token_secret      = 'oauth_token_secret';
    const auth_verifier          = 'oauth_verifier';

    protected $username
            , $password
            , $authFile
            , $downloadDir
            , $wgetBin
            //, $ApiTimestamp
            , $apiBaseURL
            , $apiStatus
            , $apiConfig
            , $apiVersion
            , $initDone       = false
    ;
    private $curl
            // , $apiCache         = array()
            , $lastApiRead      = array()
            , $authData
            , $OAuthToken
            , $OAuthConsumer
            , $authDataDefaults = array(
                self::auth_token        => NULL,
                self::auth_token_secret => NULL,
                self::auth_verifier     => NULL,
                    )
            , $log              = array();

    /**
     * 
     * @param string $username gog.com login
     * @param string $password gog.com password
     */
    public function __construct($username, $password)
    {
        $this->curl = curl_init();

        $this->setAuthFile(__DIR__ . '/.gogauth')
                ->setDownloadDir(__DIR__ . '/dl')
                ->setPassword($password)
                ->setUsername($username)
                ->setWgetBin('wget');

        if (!is_dir($this->getDownloadDir()))
        {
            mkdir($this->getDownloadDir(), 0777, true);
        }
    }

    /**
     * destructor operations
     */
    public function __destruct()
    {
        $this->saveAuthdata();
        curl_close($this->curl);
    }

    /**
     * sets 
     * @return \GOGPHPDL
     */
    public function init()
    {
        if ($this->initDone)
        {
            return $this;
        }

        $this->fetchApiManifest();
        $this->initDone = true;
        return $this;
    }

    /**
     * 
     * @param string|array $games
     * @return \GOGPHPDL
     */
    public function run($games)
    {
        set_time_limit(0);
        $cmd  = array();
        $wget = $this->getWgetBin();
        foreach ((array) $games as $game)
        {
            list($tag, $version) = explode('/', $game);
            $res   = $this->apiGetGameDetails($game);
            $parts = $res['game'][$version];

            if (!array_key_exists(0, $parts)) // for updates and the like
            {
                $parts = array($parts);
            }

            foreach ($parts as $part)
            {
                $file    = $this->apiGetFileDetails($tag . '/' . $part['id']);
                $outfile = GOGPHPDL_DOWNLOAD_DIR . '/' . $tag . '/' . basename($part['link']);

                if (!is_dir(dirname($outfile)))
                {
                    mkdir(dirname($outfile), 0777, true);
                }

                $continue = is_file($outfile) ? ' --continue ' : ' ';

                // $cmd[] = $wget . ' -O ' . $outfile . ' -P ' . dirname($outfile) . $continue . $file['file']['link'];
                $cmd[] = $wget . ' -O ' . escapeshellarg($outfile) . $continue . escapeshellarg($file['file']['link']);
            }
        }

        if (DIRECTORY_SEPARATOR === '/')
        { // linux
            $cmd = $this->_makeAtCmd($cmd);
            exec($cmd, $out);
            $this->log($out);
        }
        else
        { // windows untested
            $cmd = 'start ' . implode(' && start ', $cmd);
            pclose(popen($cmd, "r"));
        }


        return $this;
    }

    /**
     * 
     * @param string $cmd
     * @return string
     */
    protected function _makeAtCmd($cmd)
    {
        $shfile = sys_get_temp_dir() . '/' . uniqid('gogdl') . '.sh';
        $cmd    = implode("\n", $cmd) . "\nrm -f {$shfile}";
        file_put_contents($shfile, $cmd);

        $atcmd = 'at now + 2 minute -f ' . $shfile;
        return $atcmd;
    }

    /**
     * 
     * @param string $url
     * @param array $params
     * @param string $method default GET
     * @return type
     * @throws Exception
     */
    public function apiRead($url, $params = array(), $method = 'GET')
    {
        //curl_reset($this->curl); // php 5.5 aparently

        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 10);

        switch ($method)
        {
            case 'POST':
                curl_setopt($this->curl, CURLOPT_URL, $url);
                curl_setopt($this->curl, CURLOPT_POST, true);
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);

                break;
            default:
                $req = $url . '?' . http_build_query($params);
                curl_setopt($this->curl, CURLOPT_URL, $req);
        }
        $res               = curl_exec($this->curl);
        $this->lastApiRead = curl_getinfo($this->curl);
        $last_error        = curl_error($this->curl);
        $result            = json_decode($res, true);

        if (is_null($result))// sometimes strings are returned
        {
            $result = $res;
        }

        if (!empty($last_error) || $this->lastApiRead['http_code'] !== 200)
        {

            $this->log($url, $params, $last_error, $result, $this->lastApiRead);
            throw new Exception('Error' . $last_error . '\n'
            . '<pre><h3>Result</h3>\n' . print_r($result, 1) . '</pre>'
            . '<pre><h3>LastApiRead</h3>\n' . print_r($this->lastApiRead, 1) . '</pre>'
            . '<pre><h3>Params</h3>\n' . print_r($params, 1) . '</pre>'
            );
        }

        return $result;
    }

    /**
     * 
     * @param type $url
     * @param type $params
     * @return type
     */
    public function apiReadAuthed($url, $params = array())
    {

        $httpMethod = 'POST';

        $params = $this->getApiAuthedParams($url, $params, $httpMethod);

        return $this->apiRead($url, $params, $httpMethod);
    }

    public function getApiAuthedParams($url, $params = array(), $httpMethod = 'POST')
    {
        $request = OAuthRequest::from_consumer_and_token($this->getOAuthConsumer(), $this->getOAuthToken(), $httpMethod, $url, $params);

        $signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();

        $request->sign_request($signatureMethod, $this->getOAuthConsumer(), $this->getOAuthToken());

        return $request->get_parameters();
    }

    /**
     * 
     * @return type
     */
    public function apiGetUserDetails()
    {
        $cfg = $this->getApiConfig();
        return $this->apiReadAuthed($cfg[self::url_get_user_details]);
    }

    /**
     * 
     * @throws Exception
     */
    public function apiGetGamesList()
    {
        throw new Exception('NOT IMPLEMENTED');
//        $cfg = $this->getApiConfig();
//        return $this->apiReadAuthed($cfg[self::url_get_user_games]);
    }

    /**
     * 
     * @param type $gameTag
     * @return type
     */
    public function apiGetGameDetails($gameTag)
    {
        $cfg = $this->getApiConfig();
        return $this->apiReadAuthed($cfg[self::url_get_game_details] . '/' . $this->_sanitiseGameTag($gameTag));
    }

    /**
     * 
     * @param type $gameTag
     * @return type
     */
    public function apiGetGameInstaller($gameTag)
    {
        $cfg = $this->getApiConfig();
        return $this->apiReadAuthed($cfg[self::url_get_installer_link] . '/' . $this->_sanitiseGameTag($gameTag));
    }

    /**
     * 
     * @param type $gameTag
     * @return type
     */
    public function apiGetGameExtras($gameTag)
    {
        $cfg = $this->getApiConfig();
        return $this->apiReadAuthed($cfg[self::url_get_extra_link] . '/' . $this->_sanitiseGameTag($gameTag) . '/');
    }

    /**
     * 
     * @param type $fileTag
     * @return type
     */
    public function apiGetFileDetails($fileTag)
    {
        return $this->apiReadAuthed($this->apiBaseURL . '/file/' . ltrim($fileTag, '/'));
    }

    /**
     * fetch details about API
     * @return \GOGPHPDL
     */
    protected function fetchApiManifest()
    {
        $test = $this->apiRead(self::gogdl_api_manifest);

        $this->setApiConfig($test['config'])
                ->setApiStatus($test['status'])
                ->setApiVersion($test['current_version']);
        $this->apiBaseURL = dirname($test['config'][self::url_get_user_details]);
        $this->getAuthData();
        return $this;
    }

    /**
     * performs login operatio
     * @return \GOGPHPDL
     * @throws Exception
     */
    public function login()
    {
        $cfg = $this->getApiConfig();
// step one get the token and token secret
        $tok = $this->apiReadAuthed($cfg[self::url_login_init]);

        $tokens = $this->_parseopt($tok, $this->authDataDefaults);

        // step two, merge token into auth data and get verification.
        $this->setAuthData($tokens);

        $verif = $this->apiReadAuthed($cfg[self::url_login], array(
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
        ));

        $tokens = $this->_parseopt(trim($verif, '?'), $tokens); // returns string starting with `?`
        //step three. verify tmp auth into a less temp auth
        $final  = $this->apiReadAuthed($cfg[self::url_login_verify], array(
            self::auth_verifier => $tokens[self::auth_verifier],
        ));

        $tokens = $this->_parseopt(trim($final, '?'), $tokens); // returns string starting with `?`
        // update tokens
        $this->setAuthData($tokens);

        // test
        $isValid = $this->isAuthDataValid();

        if ($isValid)
        {
            $this->saveAuthdata();
        }
        else
        {
            throw new Exception('Login Failed');
        }
        return $this;
    }

    /**
     * revalidate auth if expired.
     * @return \GOGPHPDL
     */
    public function revalidateAuth()
    {
        //oauth_get_temp_token, oauth_authorize_temp_token, oauth_get_token
        $valid = $this->isAuthDataValid();

        if (!$valid)
        {
            $this->login();
        }
        else
        {
            // do something
        }
        return $this;
    }

    /**
     * @return boolean
     */
    public function isAuthDataValid()
    {
        $authData = $this->getAuthData();
        if (!empty($authData[self::auth_token]) && !empty($authData[self::auth_token_secret]) && !empty($authData[self::auth_verifier]))
        {
            $cfg = $this->getApiConfig();
            try
            {
                $test = $this->apiReadAuthed($cfg[self::url_get_user_details]);
                if ($test['result'] === 'ok')
                {
                    return true;
                }
            }
            catch (Exception $exc)
            {
                //echo $exc->getMessage();
                return false;
            }
        }

        return false;
    }

    /**
     * 
     * @return OAuthConsumer
     */
    protected function getOAuthConsumer()
    {
        if (empty($this->OAuthConsumer))
        {
            $this->OAuthConsumer = new OAuthConsumer(self::gog_consumer_key, self::gog_consumer_sercret);
        }

        return $this->OAuthConsumer;
    }

    /**
     * 
     * @param type $key
     * @param type $secret
     * @return \GOGPHPDL
     */
    protected function setOAuthToken($key, $secret)
    {
        $this->OAuthToken = new OAuthToken($key, $secret);
        return $this;
    }

    /**
     * 
     * @return OAuthToken
     */
    protected function getOAuthToken()
    {
        if (is_null($this->OAuthToken))
        {
            $authData = $this->getAuthData();
            if (!empty($authData[self::auth_token]) && !empty($authData[self::auth_token_secret]))
            {
                $this->setOAuthToken($authData[self::auth_token], $authData[self::auth_token_secret]);
            }
        }
        return $this->OAuthToken;
    }

    /**
     * 
     * @return array
     */
    protected function getAuthData()
    {
        if (empty($this->authData))
        {
            $this->authData = unserialize(file_get_contents($this->getAuthFile()));
        }
        return $this->_parseopt($this->authData, $this->authDataDefaults);
    }

    public function setAuthData($data)
    {
        $data                = $this->_parseopt($data, $this->authDataDefaults);
        // clear
        $this->OAuthToken    = NULL;
        $this->OAuthConsumer = NULL;

        $this->authData = $data;
        return $this;
    }

    /**
     * 
     * @return \GOGPHPDL
     */
    public function saveAuthdata()
    {
        file_put_contents($this->getAuthFile(), serialize($this->getAuthData()));
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * 
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * 
     * @return string
     */
    public function getAuthFile()
    {
        return $this->authFile;
    }

    /**
     * 
     * @return string
     */
    public function getDownloadDir()
    {
        return $this->downloadDir;
    }

    /**
     * 
     * @return string
     */
    public function getWgetBin()
    {
        return $this->wgetBin;
    }

    /**
     * 
     * @param type $username
     * @return \GOGPHPDL
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * 
     * @param type $password
     * @return \GOGPHPDL
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * 
     * @param type $authFile
     * @return \GOGPHPDL
     */
    public function setAuthFile($authFile)
    {
        $this->authFile = $authFile;
        return $this;
    }

    /**
     * 
     * @param type $downloadDir
     * @return \GOGPHPDL
     */
    public function setDownloadDir($downloadDir)
    {
        $this->downloadDir = $downloadDir;
        return $this;
    }

    /**
     * 
     * @param type $wgetBin
     * @return \GOGPHPDL
     */
    public function setWgetBin($wgetBin)
    {
        $this->wgetBin = $wgetBin;
        return $this;
    }

    /**
     * 
     * @return type
     */
    public function getApiStatus()
    {
        return $this->apiStatus;
    }

    /**
     * 
     * @param type $ApiStatus
     * @return \GOGPHPDL
     */
    protected function setApiStatus($ApiStatus)
    {
        $this->apiStatus = $ApiStatus;
        return $this;
    }

    /**
     * 
     * @return array
     */
    public function getApiConfig()
    {
        return $this->apiConfig;
    }

    /**
     * 
     * @param array $ApiConfig
     * @return \GOGPHPDL
     */
    public function setApiConfig($ApiConfig)
    {
        $this->apiConfig = $ApiConfig;
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * 
     * @param string $ApiVersion
     * @return \GOGPHPDL
     */
    public function setApiVersion($ApiVersion)
    {
        $this->apiVersion = $ApiVersion;
        return $this;
    }

    /**
     * 
     * @param string $in
     * @return string
     */
    protected function _sanitiseGameTag($in)
    {
        return preg_replace(array('~[^a-z0-9/]+~', '~ +~'), array(' ', '_'), strtolower($in));
    }

    /**
     * worpdress style option parser
     * @param array $param
     * @param array $defaults
     * @param boolean $strict
     * @return array
     */
    private function _parseopt($param, $defaults, $strict = true)
    {
        if (empty($param))
        {
            return $defaults;
        }

        if (is_string($param))
        {
            $tmp   = array();
            parse_str($param, $tmp);
            $param = $tmp;
        }

        $param = array_merge($defaults, (array) $param);

        if ($strict)
        {
            return array_intersect_key((array) $param, $defaults);
        }

        return $param;
    }

    /**
     * log msg
     * @param string|null $msg
     * @return \GOGPHPDL|array
     */
    public function log()
    {
        $msg = func_get_args();
        if (!empty($msg))
        {
            $this->log[] = $msg;
            return $this;
        }
        return $this->log;
    }

}
