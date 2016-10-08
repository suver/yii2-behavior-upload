<?php

namespace suver\behavior\upload;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

use suver\behavior\upload\models\UploadsInterface;

use yii\imagine\Image;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Point;
use suver\behavior\upload\ImageModifedInterface;

use Imagine\Exception\Exception as ImagineException;

/**
 * Class IncorrectFile
 * @package common\modules\uploads\models
 */
class IncorrectFile implements FileInterface
{

    protected $model;
    protected $thumbnail;
    protected $defaultFilePath = '';

    public function __construct($model, $thumbnail) {
        $this->model = $model;
        $this->thumbnail = $thumbnail;
    }


    public function __toString() {
        return $this->defaultFilePath;
    }

    /**
     * Препроцессор
     */
    public function preprocessor() {
    }

    public function getName() {
        return null;
    }

    public function getExtension() {
        return null;
    }

    /**
     * Вернет true если файл загружен
     * @return mixed
     */
    public function hasFile() {
        return false ;
    }

    /**
     * Вернет тип файла
     * @return mixed
     */
    public function getType() {
        return null;
    }

    /**
     * Вернет mime файла
     * @return mixed
     */
    public function getMimeType() {
        return null;
    }

    /**
     * Вернет size файла
     * @return mixed
     */
    public function getSize() {
        return null;
    }

    /**
     * Вернет оригинальное название файла
     * @return mixed
     */
    public function getOriginalName() {
        return null;
    }

    /**
     * Вернет параметры файла
     * @return mixed
     */
    public function getParams() {
        return [];
    }

    /**
     * Вернет параметры файла
     * @return mixed
     */
    public function getHash() {
        return null;
    }

    public function delete() {
        return null;
    }

    /**
     * Установит файл замену
     * @param $filePath
     * @return $this
     */
    public function byDefault($file=false) {
        $this->defaultFilePath = $file;
        return $this;
    }

    /**
     * Вернет относительный путь к диреткории с файлами текущего представления
     *
     * @param UploadsInterface $model
     * @return string
     */
    public function getPath($size=false)
    {
        return null;
    }

    /**
     * Вернет полный путь к файлу
     * @param bool $size
     * @return bool|null|string
     */
    public function getFullPath($size=false) {
        return null;
    }

    /**
     * @return null Удаляет директорию
     */
    public function getDirectory() {
        return null;
    }

    /**
     * Вернет путь из хранилища
     * @param bool $size
     * @return string
     */
    public function getDomainPath($size=false) {
        return $this->defaultFilePath;
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        return $this;
    }

}
