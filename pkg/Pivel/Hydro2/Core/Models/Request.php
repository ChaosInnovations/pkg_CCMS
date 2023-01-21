<?php

namespace Package\Pivel\Hydro2\Core\Models;

use Package\Pivel\Hydro2\Core\Models\HTTP\Method;

class Request
{
    
    protected string $endpoint = '';
    protected bool $isWeb = true;
    protected array $cookies = [];
    protected bool $isHttps = false;
    protected string $hostname = 'localhost';
    protected string $clientAddress = '';
    public Method $method;
    public string $baseUrl = '';
    /**
     * Union of all args from POST, query string, path (defined by matched route), and a json object contained in
     * the request body (if content-type=="application/json" and there is a valid json object in the request body)
     */
    public array $Args = [];
    public string $requestBody = '';
    
    public function __construct(array $server, array $cookies=[], array $post=[], array $get=[], string $sapi_name="apache2handler")
    {
        if (substr($sapi_name, 0, 3) == 'cli' || empty($server['REMOTE_ADDR'])) {
            $this->isWeb = false;
            
            global $argv;
            if (isset($argv)) {
                foreach ($argv as $arg) {
                    $e=explode("=", $arg, 2);
                    if(count($e)==2)
                        $this->Args[$e[0]] = $e[1];

                    else    
                        $this->Args[$e[0]] = true;
                }
            }
        } else {
            $this->isHttps = isset($server['HTTPS']) && $server['HTTPS'] != 'off';
            $this->hostname = $server["SERVER_NAME"];
            $this->clientAddress = $server['REMOTE_ADDR'];
        }

        $this->baseUrl = "http" . ($this->isHttps ? "s" : "") . "://" . $this->hostname;
        
        $url = trim($server["REQUEST_URI"], "/");
        if (strstr($url, '?')) {
            $url = substr($url, 0, strpos($url, '?'));
        }
        $this->endpoint = $url;

        $method = $server['REQUEST_METHOD'];
        $this->method = Method::tryFrom($method) ?? Method::GET;

        $this->cookies = $cookies;

        $this->requestBody = file_get_contents('php://input');
        
        $this->Args = array_merge($this->Args, $get);
        $this->Args = array_merge($this->Args, $post);

        if (isset($server['CONTENT_TYPE'])) {
            $contentType = $server['CONTENT_TYPE'];
            if ($contentType == "application/json") {
                $bodyObject = json_decode($this->requestBody, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($bodyObject)) {
                    $this->Args = array_merge($this->Args, $bodyObject);
                }
            }
        }
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

    public function getClientAddress() : string {
        return $this->clientAddress;
    }
}