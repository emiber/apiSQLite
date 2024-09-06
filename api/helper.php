<?php
class Helper
{
    function __construct()
    {
        $this->setHeader();
    }

    private function setHeader()
    {
        $ts = gmdate("D, d M Y H:i:s") . " GMT";
        header("Expires: $ts");
        header("Last-Modified: $ts");
        header("Pragma: no-cache");
        header("Cache-Control: no-cache, must-revalidate");
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: OPTIONS, GET, PUT, POST, PATCH, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Authorization, X-Requested-With, bearer, email");
        header("Access-Control-Max-Age: 3600");
    }

    function getParams()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri =  $_SERVER['REQUEST_URI'];
        $scriptName = $_SERVER["SCRIPT_NAME"];
        $scriptName = str_replace("index.php", "", $scriptName);
        $uri = str_replace($scriptName, "", $uri);
        $uriParams = explode('/', $uri);
        $body = json_decode(file_get_contents("php://input"));

        switch (count($uriParams)) {
            case 1:
                $table = empty($uriParams[0]) ? null : $uriParams[0];
                $id = null;
                break;
            case 2:
                $table = empty($uriParams[0]) ? null : $uriParams[0];
                $id = empty($uriParams[1]) ? null : $uriParams[1];
                break;
            default:
                $table = null;
                $id = null;
        }
        if ($table == null) {
            http_response_code(400);
            die;
        }

        $params = [];
        $params['method'] = $method;
        $params['table'] = $table;
        $params['id'] = $id;
        $params['body'] = $body;

        return $params;
    }

    function getToken()
    {
        $token = $this->getHeader('x-authorization');
        if ($token !== null) {
            return json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $token)[1]))));
        }
        return '';
    }

    function getSubFromToken()
    {
        $token = $this->getToken();
        if ($token !== '') {
            return $token->sub;
        }
        return '';
    }

    function getHeader($headerKey)
    {
        $headerKey = strtolower($headerKey);
        foreach (getallheaders() as $key => $value) {
            $key = strtolower($key);
            if (strpos($key, $headerKey) !== false) {
                return $value;
            }
        }
        return null;
    }

    function logRequest()
    {
        $toLog['timeStamp']     = date('Y-m-d H:i:s');
        $toLog['HTTP_HOST']     = $_SERVER['HTTP_HOST'];
        $toLog['method']        = $_SERVER['REQUEST_METHOD'];
        $toLog['uri']           = $_SERVER['REQUEST_URI'];
        $toLog['scriptName']    = $_SERVER["SCRIPT_NAME"];
        $toLog['headers']       = getallheaders();
        $toLog['body']          = json_decode(file_get_contents("php://input"));
        $this->logInFile('log/log.json', json_encode($toLog));

        $toLog = "###" . PHP_EOL;
        $toLog .= $_SERVER['REQUEST_METHOD'] . " http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . PHP_EOL;
        $toLog .= "x-user-id:" . $this->getHeader('x-user-id') . PHP_EOL;
        $toLog .= PHP_EOL;
        $toLog .= file_get_contents("php://input");
        $toLog .= PHP_EOL . PHP_EOL;
        $this->logInFile('log/log.http', $toLog);
    }

    function logInFile($file, $text)
    {
        $fp = fopen($file, 'a');
        fwrite($fp, $text . "," . PHP_EOL);
        fclose($fp);
    }
}
