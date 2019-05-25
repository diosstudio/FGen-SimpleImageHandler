<?php

namespace Dios\SimpleImageHandler;

use Dios\SimpleImageHandler\ImageHandlerTrait;

/**
 * Класс для обработки изображений без использования пакета FGen и FileHandler.
 */
class SimpleImageHandler
{
    use ImageHandlerTrait;

    protected $imageConfigure = ['driver' => 'imagick'];
}
