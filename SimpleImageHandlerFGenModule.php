<?php

namespace Dios\SimpleImageHandler;

use Belca\FGen\FHandler;
use Belca\FGen\FHandlerTrait;
use Dios\SimpleImageHandler\ImageHandlerTrait;

/**
 * Класс для использования вместе с пакетом FGen.
 */
class SimpleImageHandlerFGenModule extends FHandler
{
    use FHandlerTrait, ImageHandlerTrait;

    protected $imageConfigure = ['driver' => 'imagick'];
}
