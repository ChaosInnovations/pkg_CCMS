<?php

namespace Package\CCMS;

class Request
{
    
    protected $endpoint = '';
    protected $isWeb = true;
    protected $cookies = [];
    protected $isHttps = false;
    protected $hostname = "localhost";
    public $baseUrl = "";
    
    public function __construct(array $server, array $cookies=[], $sapi_name="apache2handler")
    {
        if (substr($sapi_name, 0, 3) == 'cli' || empty($server['REMOTE_ADDR'])) {
            $this->isWeb = false;
            
            global $argv;
            if (isset($argv)) {
                foreach ($argv as $arg) {
                    $e=explode("=",$arg);
                    if(count($e)==2)
                        $_GET[$e[0]]=$e[1];
                    else    
                        $_GET[$e[0]]=0;
                }
                
                if (isset($argv[1])) {
                    $server["REQUEST_URI"] = $argv[1];
                }
            }
        } else {
            $this->isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
            $this->hostname = $_SERVER["SERVER_NAME"];
        }

        $this->baseUrl = "http" . ($this->isHttps ? "s" : "") . "://" . $this->hostname;
        
        $url = trim($server["REQUEST_URI"], "/");
        if (strstr($url, '?')) {
            $url = substr($url, 0, strpos($url, '?'));
        }
        $this->endpoint = $url;
        
        $this->cookies = $cookies;
    }
    
    public function getEndpoint()
    {
        return $this->endpoint;
    }
    
    public function getTypedEndpoint()
    {
        return ($this->isWeb ? "web:" : "cli:") . $this->endpoint;
    }
    
    public function isWeb()
    {
        return $this->isWeb;
    }
    
    public function getCookie(string $key, string $default="")
    {
        if (!isset($this->cookies[$key])) {
            return $default;
        }
        
        return $this->cookies[$key];
    }
}