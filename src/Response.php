<?php

declare(strict_types=1);

namespace PHP94;

use Psr\Http\Message\ResponseInterface;

class Response
{
    public static function success(string $message, $redirect_url = null, $data = null): ResponseInterface
    {
        if (self::isAcceptJson()) {
            $res = [
                'error' => 0,
                'message' => $message,
            ];
            if (!is_null($redirect_url)) {
                $res['redirect_url'] = $redirect_url;
            }
            if (!is_null($data)) {
                $res['data'] = $data;
            }
            return self::json($res);
        } else {
            return self::html(self::getTpl(true, $message, $redirect_url));
        }
    }

    public static function error(string $message, $redirect_url = null, $data = null, int $error = 1): ResponseInterface
    {
        if (self::isAcceptJson()) {
            $res = [
                'error' => $error,
                'message' => $message,
            ];
            if (!is_null($redirect_url)) {
                $res['redirect_url'] = $redirect_url;
            }
            if (!is_null($data)) {
                $res['data'] = $data;
            }
            return self::json($res);
        } else {
            return self::html(self::getTpl(false, $message, $redirect_url));
        }
    }

    public static function redirect(string $url, int $http_status_code = 302): ResponseInterface
    {
        $response = Factory::createResponse($http_status_code);
        return $response->withHeader('Location', $url);
    }

    public static function html(string $string): ResponseInterface
    {
        $response = Factory::createResponse();
        $response->getBody()->write($string);
        return $response;
    }

    public static function json($data): ResponseInterface
    {
        $response = Factory::createResponse();
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private static function isAcceptJson(): bool
    {
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }
        return false;
    }

    private static function getTpl(bool $status, string $message, string $redirect_url = null): string
    {
        $status = $status ? ':)' : ':(';
        $redirect_url = is_null($redirect_url) ? 'javascript:history.go(-1);' : $redirect_url;
        return <<<str
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
    <title>{$message}</title>
    <style>
        html, body{
            width:100%;
            height:100%;
            padding:0;
            margin:0;
        }
    </style>
</head>

<body>
    <div style="padding:0 20px;">
        <div style="font-size:150px;letter-spacing: 25px;">{$status}</div>
        <div style="margin: 20px auto;font-size: 38px;letter-spacing: 2px;">{$message}</div>
        <a style="margin: 20px auto;font-size:18px;text-decoration: none;letter-spacing: 1px;color: #4b72ff;" href="{$redirect_url}" id="jump">跳转中</a> <span id="time">3</span><span>s</span>
    </div>
    <script>
        var time = 3;
        var timer = setInterval(function(){
            time -= 1;
            if(time > 0){
                document.getElementById("time").innerHTML=time;
            } else {
                clearInterval(timer);
                document.getElementById("jump").click();
            }
        }, 1000);
    </script>
</body>

</html>
str;
    }
}
