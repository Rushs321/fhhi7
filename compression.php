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

        // Get image dimensions
        $image_width = imagesx($inst);
        $image_height = imagesy($inst);

        // Check if height is greater than 16383
        if ($image_height > 16383) {
            // Calculate new dimensions while maintaining aspect ratio
            $new_height = 16383;
            $new_width = intval(($new_height / $image_height) * $image_width);

            // Resize the image using GD library
            $src = $inst; // Assuming $inst is an image resource
            $dst = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $image_width, $image_height);

            // Replace the old image with the resized image
            $inst = $dst;
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
