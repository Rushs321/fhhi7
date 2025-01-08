<?php

namespace staifa\php_bandwidth_hero_proxy\proxy;

use function staifa\php_bandwidth_hero_proxy\util\doto;
use function staifa\php_bandwidth_hero_proxy\redirect\redirect;

// Sends a GET request to given target URL
// This function is used in main flow control
function send_request()
{
    return function ($ctx) {
        extract($ctx["config"], EXTR_REFS);
        extract($ctx["http"], EXTR_REFS);

        $error_msg = null;
        $response_headers = [];
        $request_headers = [
          "x-forwarded-for" => $request["HTTP_X_FORWARDED_FOR"] || $request["REMOTE_ADDR"] || $request["SERVER_ADDR"],
          "cookie" => $request["HTTP_COOKIE"],
          "dnt" => $request["HTTP_DNT"],
          "referer" => $request["HTTP_REFERER"],
          "user-agent" => "Bandwidt Compressor",
          "via" => "1.1 bandwid-hero",
          "content-encoding" => "gzip"
        ];

        $ch = $c_init();
        $ctx["instances"] += ["http" => $ch];
        doto(
            fn ($c, $o, $v) => $c_set($c, $o, $v),
            $ch,
            $request_opts($request_headers, $response_headers, $target_url)
        );
        $data = $c_exec($ch);
        $status = $c_info($ch, CURLINFO_HTTP_CODE);

        $ctx["config"] += [
          "response" => [
            "data" => $data,
            "status" => $status,
            "headers" => array_merge($response_headers, ["content-encoding" => "identity"])
          ],
          "request_headers" => array_merge(
              $request_headers,
              ["origin_type" => $response_headers["content-type"] ?? '',
         "origin_size" => strlen($data)]
          )
        ];

        if ($c_err_no($ch)) {
            $error_msg = $c_err($ch);
        }
        $c_close($ch);
        if ($error_msg || $status >= 400) {
            redirect($ctx);
            return false;
        }

        return $ctx;
    };
};
