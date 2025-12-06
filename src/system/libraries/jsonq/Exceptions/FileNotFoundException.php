<?php
if (!defined('DEDEINC')) {http_response_code(403); exit();}
class FileNotFoundException extends \Exception
{
    public function __construct($message = "File not found exception", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
?>