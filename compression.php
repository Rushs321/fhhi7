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

        // Define maximum dimensions
        $maxWidth = 800;
        $maxHeight = 16383;

        // Calculate the new dimensions while maintaining the aspect ratio
        if ($width > $height) {
            if ($width > $maxWidth) {
                $height = (int)($height * $maxWidth / $width);
                $width = $maxWidth;
            }
        } else {
            if ($height > $maxHeight) {
                $width = (int)($width * $maxHeight / $height);
                $height = $maxHeight;
            }
        }

        // Resize the image
        $inst = imagescale($inst, $width, $height);

        $ctx["instances"] += ["image" => $inst];

        if ($origin_type == "image/png" || $origin_type == "image/gif") {
            $i_palette($inst);
        }
        if ($greyscale) {
            $i_filter($inst, IMG_FILTER_GRAYSCALE);
        }

        ob_start();

        ($format == "webp") ? $i_webp($inst, null, $quality) : $i_jpeg($inst, null, $quality);
        $converted_image = ob_get_contents();
        ob_end_clean();
        $i_destroy($inst);

        array_walk($headers, fn($v, $k) => $set_header($k . ": " . $v));

        $size = strlen($converted_image);
        $set_header("content-length: " . $size);
        $set_header("content-type: image/" . $format);
        $set_header("x-original-size: " . $origin_size);
        $set_header("x-bytes-saved: " . ($origin_size - $size));

        echo $converted_image;
        return $ctx;
    };
};
