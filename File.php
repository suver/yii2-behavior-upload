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
 * Class File
 * @package common\modules\uploads\models
 */
class File implements FileInterface
{

    protected $model;
    protected $options;
    protected $defaultParams;
    protected $defaultFilePath = '';

    public function __construct($model, $options, $defaultParams) {
        $this->model = $model;
        $this->options = $options;
        $this->defaultParams = $defaultParams;
    }


    public function __toString() {
        return $this->getDomainPath();
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
     * Препроцессор
     */
    public function preprocessor() {
    }

    public function getName() {
        return $this->model->name;
    }

    public function getExtension() {
        return $this->model->extension;
    }

    /**
     * Вернет true если файл загружен
     * @return mixed
     */
    public function hasFile() {
        return empty($this->model) ? false : true;
    }

    /**
     * Вернет тип файла
     * @return mixed
     */
    public function getType() {
        return $this->model->type;
    }

    /**
     * Вернет mime файла
     * @return mixed
     */
    public function getMimeType() {
        return $this->model->mime_type;
    }

    /**
     * Вернет size файла
     * @return mixed
     */
    public function getSize() {
        return $this->model->size;
    }

    /**
     * Вернет оригинальное название файла
     * @return mixed
     */
    public function getOriginalName() {
        return $this->model->original_name;
    }

    /**
     * Вернет параметры файла
     * @return mixed
     */
    public function getParams() {
        return $this->model->params;
    }

    /**
     * Вернет параметры файла
     * @return mixed
     */
    public function getHash() {
        return $this->model->hash;
    }

    public function delete() {
        $this->fileDelete();
        return $this->model->delete();
    }

    /**
     * Вернет относительный путь к диреткории с файлами текущего представления
     *
     * @param UploadsInterface $model
     * @return string
     */
    public function getDirectory()
    {
        $path = substr($this->getName(), 0, 2);
        $path = $path . '/' . substr($this->getName(), 2, 2);
        $path = $path . '/' . $this->getName();
        return $path;
    }

    /**
     * Вернет относительный путь к файлу текущего представления
     *
     * @param UploadsInterface $model
     * @return string
     */
    public function getPath()
    {
        $path = $this->getDirectory();
        $image = $path . '/' . $this->getName() . '.' . $this->getExtension();
        return $image;
    }

    /**
     * Вернет полный путь к файлу
     * @param bool $size
     * @return bool|null|string
     */
    public function getFullPath() {
        $file = $this->getPath();
        $fileFullPath = Yii::getAlias('@storage/' . $file);
        if(file_exists($fileFullPath)) {
            return $fileFullPath;
        }
        return null;
    }

    /**
     * Вернет путь из хранилища
     * @param bool $size
     * @return string
     */
    public function getDomainPath() {
        $file = $this->getPath();
        $fileFullPath = Yii::getAlias('@storage/' . $file);
        if(file_exists($fileFullPath)) {
            return Yii::$app->getModule('uploads')->storageDomain . '/' . $file;
        }
        return $this->defaultFilePath;
    }

    /**
     * Удаляет файл
     */
    protected function fileDelete() {
        $directory = $this->getDirectory();
        $fileFullPath = Yii::getAlias('@storage/' . $directory);
        if(file_exists($fileFullPath)) {
            $files = scandir($fileFullPath);
            foreach($files as $file) {
                if(!in_array($file, ['.','..'])) {
                    $fileFullPathFile = Yii::getAlias('@storage/' . $directory . '/' . $file);
                    @unlink($fileFullPathFile);
                }
            }

            @rmdir($fileFullPath);

            $files = scandir(dirname($fileFullPath));
            if(count($files) <= 2) {
                @rmdir(dirname($fileFullPath));
            }

            $files = scandir(dirname(dirname($fileFullPath)));
            if(count($files) <= 2) {
                @rmdir(dirname(dirname($fileFullPath)));
            }
        }
    }
}
