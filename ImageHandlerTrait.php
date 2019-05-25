<?php

namespace Dios\SimpleImageHandler;

use Belca\GeName\GeName;
use Belca\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;

/**
 * Простой обработчик изображений.
 *
 * Основан на Intervention Image.
 *
 * github: https://github.com/Intervention/image
 * website: http://image.intervention.io/
 */
trait ImageHandlerTrait
{
    /**
     * Изменяет размер изображения по указанным критериям.
     *
     * @param  string $filename
     * @param  array  $options
     * @return mixed
     */
    public function resize($filename, $options = [])
    {
        $originalFile = $this->initFilename($filename, $this->getSourceDirectory());
        $img = $this->initImage($originalFile);
        $this->resizeImage($img, $options['width'] ?? null, $options['height'] ?? null, $options['reduce'] ?? null);
        $this->cropImage($img, $options['crop_width'] ?? null, $options['crop_width'] ?? null, $options['margin_top'] ?? null, $options['margin_left'] ?? null, $options['resize'] ?? false);
        $quality = $this->initQuality($options['quality'] ?? null);

        // Если задан либой из шаблонов директории, то генерируем его/их,
        // иначе в качестве директории для сохранения используется директория
        // с исходным файлом.
        if (isset($options['directory_prefix_pattern']) || isset($options['directory_pattern'])) {
            $directoryPrefix = $this->makeDirectory($options['directory_prefix_pattern'] ?? '{sourceDirectory}', array_merge(['sourceDirectory' => $this->getSourceDirectory()], $options));
            $finalDirectory = Str::normalizeFilePath($directoryPrefix.'/'.$this->makeDirectory($options['directory_pattern'] ?? null, $options));
        } else {
            $finalDirectory = $this->getFinalDirectory();
        }

        $newFilename = $this->makeFilename($originalFile, $options['filename_pattern'] ?? null, $options, $finalDirectory);
        $this->saveImage($img, $newFilename, $quality);

        $pathinfo = pathinfo($newFilename);

        return [
            'extension' => $pathinfo['extension'],
            'size' => $img->filesize(),
            'mime' => $img->mime(),
            'fullname' => Str::normalizeFilePath($newFilename),
            'relativename' => Str::differenceSubstring($this->getSourceDirectory(), $newFilename),
            'filename' => $pathinfo['basename'],
            'shortname' => $pathinfo['filename'],
            'final_directory' => $this->getFinalDirectory(),
            'source_directory' => $this->getSourceDirectory(),
        ];
    }

    public function thumbnail($filename, $options = [])
    {
        $originalFile = $this->initFilename($filename, $this->getSourceDirectory());
        $img = $this->initImage($originalFile);
        $this->cropImage($img, $options['width'] ?? 100, $options['height'] ?? 100, $options['margin_top'] ?? null, $options['margin_left'] ?? null, $options['resize'] ?? true);
        $quality = $this->initQuality($options['quality'] ?? null);

        if (isset($options['directory_prefix_pattern']) || isset($options['directory_pattern'])) {
            $directoryPrefix = $this->makeDirectory($options['directory_prefix_pattern'] ?? '{sourceDirectory}', array_merge(['sourceDirectory' => $this->getSourceDirectory()], $options));
            $finalDirectory = Str::normalizeFilePath($directoryPrefix.'/'.$this->makeDirectory($options['directory_pattern'] ?? null, $options));
        } else {
            $finalDirectory = $this->getFinalDirectory();
        }

        $newFilename = $this->makeFilename($originalFile, $options['filename_pattern'] ?? null, $options, $finalDirectory);
        $this->saveImage($img, $newFilename, $quality);

        $pathinfo = pathinfo($newFilename);

        return [
            'extension' => $pathinfo['extension'],
            'size' => $img->filesize(),
            'mime' => $img->mime(),
            'fullname' => Str::normalizeFilePath($newFilename),
            'relativename' => Str::differenceSubstring($this->getSourceDirectory(), $newFilename),
            'filename' => $pathinfo['basename'],
            'shortname' => $pathinfo['filename'],
            'final_directory' => $this->getFinalDirectory(),
            'source_directory' => $this->getSourceDirectory(),
        ];
    }

    /**
     * Наносит на указанное изображение метку (подпись, водяной знак).
     *
     * @param  string $filename
     * @param  array  $options
     * @return string
     */
    public function signature($filename, $options = [])
    {
        $originalFile = $this->initFilename($filename, $this->getSourceDirectory());
        $img = $this->initImage($originalFile);
        $quality = $this->initQuality($options['quality'] ?? null);

        $orderAction = (isset($options['order']) && is_string($options['order'])) ? $options['order'] : 'resize-insert';

        // Порядок выполнения операции
        // Сначала добавляет метку, затем масштабирует
        if ($orderAction == 'insert-resize') {
            $success = $this->insertImage($img, $options['mark_filename'] ?? null, $options['mark_position'] ?? 'top-left', $options['mark_size'] ?? null, $options['mark_offset'] ?? null);

            if ($success) {
                $this->resizeImage($img, $options['width'] ?? null, $options['height'] ?? null, $options['reduce'] ?? null);
            }
        }

        // Сначала масштабирует изображение, затем добавляет метку
        else {
            $this->resizeImage($img, $options['width'] ?? null, $options['height'] ?? null, $options['reduce'] ?? null);
            $success = $this->insertImage($img, $options['mark_filename'] ?? null, $options['mark_position'] ?? 'top-left', $options['mark_size'] ?? null, $options['mark_offset'] ?? null);
        }

        // Если водяной знак не был нанесен, то выходим из обработчика
        if (! $success) {
            return [];
        }

        if (isset($options['directory_prefix_pattern']) || isset($options['directory_pattern'])) {
            $directoryPrefix = $this->makeDirectory($options['directory_prefix_pattern'] ?? '{sourceDirectory}', array_merge(['sourceDirectory' => $this->getSourceDirectory()], $options));
            $finalDirectory = Str::normalizeFilePath($directoryPrefix.'/'.$this->makeDirectory($options['directory_pattern'] ?? null, $options));
        } else {
            $finalDirectory = $this->getFinalDirectory();
        }

        $newFilename = $this->makeFilename($originalFile, $options['filename_pattern'] ?? null, $options, $finalDirectory);
        $this->saveImage($img, $newFilename, $quality);

        $pathinfo = pathinfo($newFilename);

        return [
            'extension' => $pathinfo['extension'],
            'size' => $img->filesize(),
            'mime' => $img->mime(),
            'fullname' => Str::normalizeFilePath($newFilename),
            'relativename' => Str::differenceSubstring($this->getSourceDirectory(), $newFilename),
            'filename' => $pathinfo['basename'],
            'shortname' => $pathinfo['filename'],
            'final_directory' => $this->getFinalDirectory(),
            'source_directory' => $this->getSourceDirectory(),
        ];
    }

    /**
     * Задает значения по умолчанию, если они отсутствуют.
     *
     * @param  mixed  $values
     * @param  array  $defaults
     * @return mixed
     */
    /*public function setDefaultValues($values, $defaults)
    {
        // TODO задать все обязательные аргументы функций null, если они отсутствуют
    }*/

    /**
     * Инициализирует имя файла (склеивает директорию и имя файла).
     *
     * @param  string $filename
     * @param  string $sourceDirectory
     * @return string
     */
    public function initFilename($filename, $sourceDirectory = '')
    {
        return Str::normalizeFilePath($sourceDirectory.'/'.$filename);
    }

    /**
     * Инициализирует изображение и возвращает экземпляр.
     *
     * @param  string $filename
     * @return \Intervention\Image\Image
     */
    public function initImage($filename)
    {
        $config = isset($this->imageConfigure) && is_array($this->imageConfigure) ? $this->imageConfigure : ['driver' => 'imagick'];

        Image::configure($config);

        return Image::make($filename);
    }

    /**
     * Изменяет размер изображения на основе указанных значений.
     *
     * @param  Intervention\Image\Image $img
     * @param  integer                  $width
     * @param  integer                  $height
     * @param  boolean                  $reduce
     * @return void
     */
    public function resizeImage(\Intervention\Image\Image &$img, $width, $height, $reduce = false)
    {
        // Масштабировать по двум сторонам
        if (isset($width) && isset($height)) {
            $img->resize($width, $height);
        }
        // Масштабировать по ширине
        elseif (isset($width) && ! isset($height)) {
            $this->resizeWidth($img, $width, $reduce);
        }
        // Масштабировать по высоте
        elseif (! isset($height) && isset($height)) {
            $this->resizeHeight($img, $height, $reduce);
        }
        // Масштабировать по большей или меньшей стороне
        elseif (isset($size) && is_integer($size)) { // TODO определить сторону из двух переданных значений или по параметру стороны
            // TODO уменьшить бо большей или меньшей стороне
        }
    }

    public function resizeWidthAndHeight(\Intervention\Image\Image &$img, $width, $height, $reduce = false)
    {
        /*if (empty($reduce) || (is_bool($reduce) && $reduce && $img->width() >= $width && $img->height() >= $height)) {
            $img->resize($options['width'], $options['height']);
        }*/
    }

    /**
     * Изменяет размер изображения по указанной ширине.
     *
     * @param  Intervention\Image\Image $img
     * @param  integer                  $width
     * @param  boolean                  $reduce
     * @return void
     */
    public function resizeWidth(\Intervention\Image\Image &$img, $width, $reduce = false)
    {
        if (empty($reduce) || (is_bool($reduce) && $reduce && $img->width() >= $width)) {
            $img->widen($width);
        }
    }

    /**
     * Изменяет размер изображения по указанной высоте.
     *
     * @param  Intervention\Image\Image $img
     * @param  integer                  $height
     * @param  boolean                  $reduce
     * @return void
     */
    public function resizeHeight(\Intervention\Image\Image &$img, $height, $reduce = false)
    {
        if (empty($reduce) || (is_bool($reduce) && $reduce && $img->height() >= $height)) {
            $img->heighten($height);
        }
    }

    /**
     * Выполняет обрезку изображения.
     *
     * @param  \Intervention\Image\Image  $img
     * @param  integer                    $width
     * @param  integer                    $height
     * @param  integer                    $x
     * @param  integer                    $y
     * @return void
     */
    public function cropImage(\Intervention\Image\Image &$img, $width, $height, $x = null, $y = null, $resize = null)
    {
        $height = is_integer($height) ? $height : $width;

        // Размер кадрирования должен быть указан
        if (! (is_integer($width) && is_integer($height))) {
            return;
        }

        if (is_bool($resize) && $resize) {
            // Обрезка с масштабированием
            $img->fit($width, $height, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            // Обрезка по заданным координатам
            if (is_integer($x) && is_integer($y)) {
                $img->crop($width, $height, $x, $y);
            } else {
                $img->crop($width, $height);
            }
        }
    }

    /**
     * Вставляет указанное изображение в указанную позицию. При успешном
     * добавлении изображения возвращает true.
     *
     * @param  Intervention\Image\Image $img
     * @param  string                   $filename
     * @param  string                   $position
     * @param  mixed                    $size
     * @param  mixed                    $offset
     * @return boolean
     */
    public function insertImage(\Intervention\Image\Image &$img, $filename, $position, $size = null, $offset = null)
    {
        if (! is_file($filename)) {
            return false;
        }

        $mark = Image::make($filename);

        // Масштабирование метки
        if (! is_null($size)) {

            // Указана ширина и/или высота метки
            if (is_array($size)) {

                // Если указаны оба размера, то устанавливаем указанный размер
                if (is_int($size['width']) && is_int($size['height'])) {

                    $mark->resize($size['width'], $size['height']);

                } else {
                    if (is_int($size['width'])) {
                        $mark->widen($size['width']);
                    }

                    if (is_int($size['height'])) {
                        $mark->heighten($size['height']);
                    }
                }
            }

            // Если указана ширина изображения в 'px'
            elseif (is_int($size)) {
                $mark->widen($size);
            }

            // Если указана строковая величина требуетющая конвертации,
            // например, в '%'.
            elseif (is_string($size)) {
                $width = $this->normalizeValue($size, $this->getUnit($size), [
                    'size' => $mark->width(),
                ]);

                $mark->widen($width);
            }
        }

        // Смещение метки
        if (is_null($offset)) {
            $img->insert($mark, $position);
        } else {
            // Определяем смещение, т.к. оно может быть указано в разных
            // единицах измерения.

            // Указано смещение с двух сторон в 'px' или конкретное смещение
            // по оси X или Y.
            if (is_array($offset)) {

                if (is_int($offset['x'])) {
                    $offsetX = $offset['x'];
                } else {
                    $offsetX = 0;
                }

                if (is_int($offset['y'])) {
                    $offsetY = $offset['y'];
                } else {
                    $offsetY = 0;
                }

            }

            // Если указано одно целое значение, то считается что это одинаковый
            // отступ со всех сторон. Измеряется в 'px'.
            elseif (is_int($offset)) {
                $offsetX = $offset;
                $offsetY = $offset;
            }

            // Если указана строковая величина требуетющая конвертации,
            // например, в '%'.
            elseif (is_string($offset)) {
                $val = $this->normalizeValue($offset, $this->getUnit($offset), [
                    'size' => $img->width(),
                ]);

                $offsetX = $val;
                $offsetY = $val;
            } else {
                $offsetX = 0;
                $offsetY = 0;
            }


            $img->insert($mark, $position, $offsetX, $offsetY);
        }

        return true;
    }

    /**
     * Возвращает единицы измерения указанного строкового значения.
     *
     * @var    string
     * @return string|null
     */
    protected function getUnit($value)
    {
        if (mb_substr($value, -1, 1) === '%') {
            return '%';
        }

        return null;
    }

    /**
     * Нормализует переданное значение и возвращает его. Конвертирует значение
     * в зависимости от параметров.
     *
     * @param  mixed|string $value  Исходное значение
     * @param  string       $type   Исходный тип значения (единицы измерения)
     * @param  array        $params Параметры нормализации
     * @return integer
     */
    protected function normalizeValue($value, $type, $params)
    {
        if ($type === '%') {
            $val = floatval($value);

            if ($val > 0) {
                $val = intval($value);
            }

            if (is_int($params['size'])) {
                $size = round($params['size'] / 100) * $val;

                if ($size <= ($params['size'] * 2)) {
                    return $size;
                }
            }
        }

        return intval($value);
    }

    /**
     * Генерирует имя директории на основе шаблона.
     *
     * @param  string $pattern
     * @param  mixed  $options
     * @return string
     */
    public function makeDirectory($pattern, $options)
    {
        if (! is_string($pattern)) {
            return '';
        }

        $gename = new GeName();
        $gename->setPattern($pattern);
        $gename->setInitialData($options);

        return Str::normalizeFilePath($gename->generateName());
    }

    /**
     * Генерирует имя файла на основе текущего имени файла, шаблона имени,
     * опций и директории.
     *
     * @param  string $file
     * @param  string $pattern
     * @param  mixed  $options
     * @param  string $directory
     * @return string
     */
    public function makeFilename($filename, $pattern, $options, $directory)
    {
        $pathinfo = pathinfo($filename);

        // Считаем, что в шаблоне указан абсолютный путь к файлу
        if (! is_string($directory)) {
            $directory = '';
        }

        if (is_string($pattern)) {
            $gename = new GeName();
            $gename->setDirectory($directory);
            $gename->setPattern($pattern);
            $gename->setInitialData(array_merge([
                // Передаем возможно необходимые параметры для генерации имени
                'extension' => $pathinfo['extension'],
                'filename' => $pathinfo['filename'],
                'mime' => mime_content_type($filename),
            ], $options));
            $filename = $gename->generateName();
        } else {
            $filename = $pathinfo['basename'];
        }

        return Str::normalizeFilePath($directory.'/'.$filename);
    }

    /**
     * Инициализирует значение качества изображения на основе переданного
     * значения.
     *
     * @param  integer $quality
     * @return integer
     */
    public function initQuality($quality = 90)
    {
        return (isset($quality) && is_integer($quality)) ? $quality : 90;
    }

    /**
     * Сохраняет файл с указанным именем и качеством.
     *
     * @param  \Intervention\Image\Image $img
     * @param  string                    $filename
     * @param  integer                   $quality
     * @return \Intervention\Image\Image
     */
    public function saveImage(\Intervention\Image\Image &$img, $filename, $quality = 90)
    {
        $dir = dirname($filename);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $img->save($filename, $quality);
    }
}
