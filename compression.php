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

        $ctx["instances"] += ["image" => $inst];

        if ($origin_type == "image/png" || $origin_type == "image/gif") {
            $i_palette($inst);
        };
        if ($greyscale) {
            $i_filter($inst, IMG_FILTER_GRAYSCALE);
        };

        // Resize the image height to 16383 pixels
        $original_width = imagesx($inst);
        $original_height = imagesy($inst);
        $new_height = 16383;
        $new_width = ($original_width / $original_height) * $new_height;

        $resized_inst = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($resized_inst, $inst, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

        ob_start();

        ($format == "webp") ? $i_webp($resized_inst, null, $quality) : $i_jpeg($resized_inst, null, $quality);
        $converted_image = ob_get_contents();
        ob_end_clean();
        $i_destroy($inst);
        $i_destroy($resized_inst);

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
