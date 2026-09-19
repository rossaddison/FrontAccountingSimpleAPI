<?php
namespace FAAPI\Http;

final class Response
{
    private $status = 200;
    private $body = '';
    private $headers = array();

    public function status($code = null)
    {
        if ($code !== null) {
            $this->status = (int) $code;
        }
        return $this->status;
    }

    public function body($text = null)
    {
        if ($text !== null) {
            $this->body = (string) $text;
        }
        return $this->body;
    }

    public function header($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function send()
    {
        http_response_code($this->status);
        if (!isset($this->headers['Content-Type'])) {
            $first = substr(ltrim($this->body), 0, 1);
            $this->headers['Content-Type'] = ($first === '{' || $first === '[')
                ? 'application/json'
                : 'text/html; charset=UTF-8';
        }
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
