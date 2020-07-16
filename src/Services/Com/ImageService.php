<?php

namespace WeSoonNet\LaravelPlus\Services\Com;

use Intervention\Image\Facades\Image;

class ImageService
{
    public static function resize(string $file, int $width = null, int $height = null)
    {
        if (!is_file($file))
        {
            return false;
        }

        $image_resize = Image::make($file);
        $image_resize->resize($width, $height, function ($constraint) use ($width, $height)
        {
            if (!$width || !$height)
            {
                $constraint->aspectRatio();
            }
        });
        $image_resize->save($file);

        return true;
    }
}
