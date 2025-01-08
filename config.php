<?php

namespace staifa\php_bandwidth_hero_proxy\config;

use staifa\php_bandwidth_hero_proxy\boundary\http;
use staifa\php_bandwidth_hero_proxy\boundary\image;

const DEFAULT_QUALITY = 40;
const MIN_COMPRESS_LENGTH = 128;

// Create a configuration and app context
// Hide ugly boundaries and global state
function create()
{
    return function () {
        $app_context = [
          "http" => http\init(),
          "image" => image\init(),
          "instances" => [],
        ];

        $defaults = [
          "quality" => $_REQUEST["l"] ?? DEFAULT_QUALITY,
          "auth_user" => $_ENV["BHERO_LOGIN"],
          "auth_password" => $_ENV["BHERO_PASSWORD"],
          "greyscale" => $_REQUEST["bw"] ?? true,
          "min_compress_length" => MIN_COMPRESS_LENGTH,
          "url" => urldecode($_REQUEST["url"]),
          "request_uri" => urldecode($_SERVER["REQUEST_URI"]),
          "request" => $_SERVER,
          "webp" => !$_REQUEST["jpeg"]
        ];

        ["url" => $url,
            "request_uri" => $req_uri,
            "min_compress_length" => $min_comp
        ] = $defaults;

        if (!$url) {
            echo "bandwidtproxy";
            return false;
        };

        if (is_array($url)) {
            $url = join("&url=", $url);
        };

        $values = function () use ($url, $req_uri, $min_comp, $greyscale) {
            $route = fn ($uri) => ($pos = strpos($uri, "?")) ? substr($uri, 0, $pos) : $uri;
            $process_url = function () use ($url) {
                $pattern = "/http:\/\/1\.1\.\d\.\d\/bmi\/(https?:\/\/)?/i";
                return preg_replace($pattern, "http://", $url);
            };
            return [
              "min_transparent_compress_length" => $min_comp * 100,
              "route" => $route($req_uri),
        "target_url" => $process_url()
            ];
        };

        return $app_context += ["config" => array_merge($defaults, $values())];
    };
};
