<?php

class Fault extends Exception
{
    protected $previous = NULL;

    function __construct($message = '', SoapFault $previous = NULL, $request_headers = '', $last_request = '', $response_headers = '', $last_response = ''){
        $this->request_headers = $request_headers;
        $this->last_request = $last_request;
        $this->response_headers = $response_headers;
        $this->last_response = $last_response;
        $this->previous = $previous;
    }

    function __toString(){
        $message = $this->getMessage() . ' in ' . $this->getFile() . ':' . $this->getLine() . "\n";

        if($this->previous) {
            $message .= $this->previous->faultstring . "\n";
        }

        return $message;
    }

    function getPreviousException() {
        return $this->previous;
    }
}