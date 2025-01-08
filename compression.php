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
            $new_height = 12480;
            $new_width = intval(($new_height / $image_height) * $image_width);

            // Resize the image
            $inst = i_resize($inst, $new_width, $new_height);
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

// Resize image function
function i_resize($image, $new_width, $new_height)
{
    // Create a new true color image with the new dimensions
    $resized_image = imagecreatetruecolor($new_width, $new_height);

    // Get the original image dimensions
    $original_width = imagesx($image);
    $original_height = imagesy($image);

    // Resize the original image into the new true color image
    imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

    // Destroy the original image
    imagedestroy($image);

    return $resized_image;
}
