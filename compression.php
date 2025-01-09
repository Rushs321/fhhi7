<?php

namespace staifa\php_bandwidth_hero_proxy\compression;

// Image compression
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

        // Get the current image dimensions
        $width = imagesx($inst);
        $height = imagesy($inst);

        // Check if the height exceeds 16383, and if so, resize
        if ($height > 16383) {
            $new_height = 16383;
            $new_width = (int)($width * $new_height / $height);
            $inst = imagescale($inst, $new_width, $new_height);
        }

        $ctx["instances"] += ["image" => $inst];

        if ($origin_type == "image/png" || $origin_type == "image/gif") {
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
