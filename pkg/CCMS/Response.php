<?php

namespace Package\CCMS;

class Response
{
    
    protected $content = "";
    protected $final = true;
    
    public function __construct($content='', $final=true)
    {
        $this->content = $content;
        $this->final = $final;
    }
    
    public function send($buffer=true)
    {
        if (!$buffer) {
            echo $this->content;
            return;
        }
        
        ob_end_clean();
        ignore_user_abort(true);
        ob_start();
        
        echo $this->content;
        
        $size = ob_get_length();
        header("Content-Length: {$size}");
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