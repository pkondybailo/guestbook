<?php

namespace App;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

class ImageOptimizer
{
    private const MAX_HEIGHT = 150;
    private const MAX_WIDTH = 200;

    private Imagine $imagine;

    public function __construct()
    {
        $this->imagine = new Imagine();
    }

    public function resize(string $filename): void
    {
        [$imageWidth, $imageHeight] = getimagesize($filename);
        $ratio = $imageWidth / $imageHeight;
        $newWidth = self::MAX_WIDTH;
        $newHeight = self::MAX_HEIGHT;

        if ($newWidth / $newHeight > $ratio) {
            $newWidth = $newHeight * $ratio;
        } else {
            $newHeight = $newWidth / $ratio;
        }

        $image = $this->imagine->open($filename);
        $image->resize(new Box($newWidth, $newHeight))->save($filename);
    }
}