<?php

namespace Package\CCMS\Models;

use Package\CCMS\Models\HTTP\StatusCode;

class Response
{
    
    protected string $content;
    protected bool $final;
    protected StatusCode $status;
    /**
     * @var string[]
     */
    protected array $headers;
    
    public function __construct(string $content='', bool $final=true, StatusCode $status=StatusCode::OK, array $headers=[])
    {
        $this->content = $content;
        $this->final = $final;
        $this->status = $status;
        $this->headers = $headers;
    }
    
    public function send(bool $buffer=true)
    {
        if (!$buffer) {
            http_response_code($this->status->value);
            foreach ($this->headers as $headerName => $headerContent) {
                // TODO: should probably sanitize the header before sending it.
                header($headerName . ": " . $headerContent);
            }
            echo $this->content;
            return;
        }
        
        ob_end_clean();
        ignore_user_abort(true);
        ob_start();
        
        echo $this->content;
        
        $size = ob_get_length();
        http_response_code($this->status->value);
        header("Content-Length: {$size}");
        foreach ($this->headers as $headerName => $headerContent) {
            // TODO: should probably sanitize the header before sending it.
            header($headerName . ": " . $headerContent);
        }
        ob_end_flush();
        flush();
    }
    
    public function setContent(string $content)
    {
        $this->content = $content;
    }
    
    public function getContent()
    {
        return $this->content;
    }
    
    public function setFinal(bool $isFinal)
    {
        $this->final = $isFinal;
    }
    
    public function isFinal()
    {
        return $this->final;
    }
    
    public function append(Response $otherResponse)
    {
        if ($this->final) {
            return;
        }
        $this->content .= $otherResponse->getContent();
        $this->final = $otherResponse->isFinal();
    }
}