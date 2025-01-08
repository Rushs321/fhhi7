<?php

namespace staifa\php_bandwidth_hero_proxy\compression;

// Image compression with height resizing
function process_image()
{
    return function ($ctx) {
        extract($ctx["config"], EXTR_REFS);
        extract($request_headers, EXTR_REFS);
        extract($response, EXTR_REFS);
        extract($ctx["http"], EXTR_REFS);
        extract($ctx["image"], EXTR_REFS);

        $format = $webp ? "webp" : "jpeg";
        $inst = $i_create($data);

        $ctx["instances"] += ["image" => $inst];

        // Resize if height exceeds 16383 pixels
        $image_width = $i_get_width($inst);
        $image_height = $i_get_height($inst);
        if ($image_height > 16383) {
            $resize_ratio = 16383 / $image_height;
            $new_width = intval($image_width * $resize_ratio);
            $new_height = 16383;
            $i_resize($inst, $new_width, $new_height);
        }

        if ($origin_type == "image/png" || "image/gif") {
            $i_palette($inst);
        };
        if ($greyscale) {
            $i_filter($inst, IMG_FILTER_GRAYSCALE);
        };

        ob_start();

        ($format == "webp") ? $i_webp($inst, null, $quality) : $i_jpeg($inst, null, $quality);
        $converted_image = ob_get_contents();
        ob_end_clean();
        $i_destroy($inst);

        array_walk($headers, fn ($v, $k) => $set_header($k . ": " . $v));

        $size = strlen($converted_image);
        $set_header("content-length: " . $size);
        $set_header("content-type: image/" . $format);
        $set_header("x-original-size: " . $origin_size);
        $set_header("x-bytes-saved: " . $origin_size - $size);

        echo $converted_image;
        return $ctx;
    };
};
