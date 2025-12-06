<?php
if (!defined('DEDEINC')) {http_response_code(403); exit();}
class NullValueException extends \Exception
{
    public function __construct($message = "Null value exception", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
?>