<?php
if (!defined('DEDEINC')) {http_response_code(403); exit();}
class InvalidJsonException extends \Exception
{
    public function __construct($message = "Invalid JSON format", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
?>