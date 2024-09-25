<?php
include_once './api/helper.php';
include_once './api/data.php';
include_once './api/schema.php';
include_once './api/menu.php';
include_once './api/user.php';
include_once './api/security.php';

ini_set("zlib.output_compression", 4096);

$helper = new Helper();
// $helper->logRequest();
$helperParams = $helper->getParams();
extract($helperParams);

$request_headers = getallheaders();
if ($table === 'www') {
    $dataObj = new Data('');
    http_response_code(200);
    echo json_encode($dataObj->get());
    die;
}

$token = $helper->getToken();
$sub = $helper->getSubFromToken();

$user_ = '';
if ($sub !== '') {
    $user = new User();
    $user_ = $user->get($sub);
    if (!$user_) {
        $user->save($token);
        $user_ = $user->get($sub);
    }
}

// $security = new Security($method, $table, $user_, $origin);
$security = new Security($method, $table, $user_);

if ($method === 'OPTIONS') {
    http_response_code(200);
    die;
}

if ($table === 'permissions') {
    http_response_code(200);
    $oObj = new User('');
    echo json_encode($oObj->getPermissions($sub));
    die;
}

if ((!$security->getPermission())) {
    http_response_code(401);
    die;
}

$responseCode = 200;

$sysAdmin = $user_->sysAdmin === 1;

switch ($table) {
    case "data":
        $oObj = new Data($id);
        break;
    case "users":
        $oObj = new User($id);
        break;
    case  "schema":
        $oObj = new Schema('./CSVs/schema.csv');
        break;
    case  "menu":
        $oObj = new Menu('./CSVs/menu.csv');
        break;
    default:
        $responseCode = 400;
}

if ($responseCode != 200) {
    http_response_code($responseCode);
    die;
}

switch ($method) {
    case 'GET':
        if ($table === 'menu') {
            $data = $oObj->get($sysAdmin);
        } else {
            $data = $oObj->get('');
        }
        http_response_code($responseCode);
        if ($responseCode == 200) {
            echo json_encode($data);
        }
        break;
    case 'DELETE':
        $responseCode = 404;
        if (($table == "data") && ($id > 0)) {
            $responseCode = $oObj->delete() ? 200 : 404;
        }
        http_response_code($responseCode);
        break;
    case 'POST':
        $responseCode = 400;
        if ($table == "data") {
            $data = $oObj->post($body);
            $responseCode = $data ? 200 : 400;
        }
        http_response_code($responseCode);
        if ($responseCode === 200) {
            echo json_encode($data);
        }
        break;
    case 'PATCH':
        $responseCode = 400;
        if ($table == "data" || $table == "users") {
            $data = $oObj->patch($body);
            $responseCode = $data ? 200 : 400;
        }
        if ($responseCode === 200) {
            echo json_encode($data);
        }
        http_response_code($responseCode);
        break;
    case 'PUT':
        $responseCode = 400;
        if (($table == "data") && ($id > 0)) {
            $response = $oObj->copy();
            if ($response) {
                $responseCode = 200;
                echo json_encode($response);
            }
        }
        http_response_code($responseCode);
        break;
    default:
        http_response_code(501);
}
