<?php

namespace suver\behavior\upload;

use suver\behavior\upload\File;
use suver\behavior\upload\FileInterface;
use Yii;
use yii\base\Exception;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

use suver\behavior\upload\models\UploadsInterface;

use yii\helpers\ArrayHelper;
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
 * Class ImageFile
 * @package common\modules\uploads\models
 */
class ImageFile extends File implements FileInterface
{

    protected $size = false;
    protected $defaultFilePath = '';

    protected $model;
    protected $options;
    protected $defaultParams;

    public function __construct($model, $options, $defaultParams) {
        $this->model = $model;
        $this->options = $options;
        $this->defaultParams = $defaultParams;
    }


    public function __toString() {
        try {
            return (string) $this->getDomainPath();
        } catch (Exception $exception) {
            return '';
        }
    }


    /**
     * Препроцессор
     */
    public function preprocessor() {
        foreach ($this->options as $thumbnail=>$options) {
            if(empty($options['preGenerationOff']) || $options['preGenerationOff'] !== false) {
                $this->thumbnail($thumbnail);
            }
        }
    }

    /**
     * Установит файл замену
     * @param $filePath
     * @return $this
     */
    public function thumbnail($size=false) {
        $this->size = $size;
        return $this;
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
    public function getPath() {
        return $this->thumbnailProcessor($this->size);
    }

    /**
     * Вернет полный путь к файлу
     * @param bool $size
     * @return bool|null|string
     */
    public function getFullPath() {
        $image = $this->thumbnailProcessor($this->size);
        $imageFullPath = Yii::getAlias('@storage/' . $image);
        if(file_exists($imageFullPath)) {
            return $imageFullPath;
        }
        return null;
    }

    /**
     * Вернет путь из хранилища
     * @param bool $size
     * @return string
     */
    public function getDomainPath() {
        $image = $this->thumbnailProcessor($this->size);
        $imageFullPath = Yii::getAlias('@storage/' . $image);
        if(file_exists($imageFullPath)) {
            return Yii::$app->getModule('uploads')->storageDomain . '/' . $image;
        }
        return $this->defaultFilePath;
    }

    /**
     * Вернет относительный путь к диреткории с файлами текущего представления
     *
     * @param UploadsInterface $model
     * @return string
     */
    protected function _getPath($size=false) {
        $params = $this->defaultParams;

        if ($size && isset($this->options[$size])) {
            $params = ArrayHelper::merge($params, $this->options[$size]);
        }
        $prefix = isset($params['prefix']) ? $params['prefix'] . '_' : '';

        $path = $this->getDirectory();

        if(!$size) {
            $image = $path . '/' . $this->getName() . '.' . $this->getExtension();
        }
        else {
            $image = $path . '/' . $prefix . $this->getName() . '_' . $size . '.' . $this->getExtension();
        }

        return $image;
    }

    /**
     * Генерирует thumbnail
     * @param bool $size
     * @return string
     */
    protected function thumbnailProcessor($size=false) {
        $params = $this->defaultParams;
        if ($size && isset($this->options[$size])) {
            $params = ArrayHelper::merge($params, $this->options[$size]);
            $_size = isset($params['size']) ? $params['size'] : false;
        }
        else {
            $_size = $size;
        }

        if ($_size) {
            if (is_numeric($_size)) {
                $thumbnail_width = $_size;
                $thumbnail_height = false;
            }
            else if (preg_match("#^[xX]([\d]+)$#isUu", $_size, $math)) {
                $thumbnail_width = false;
                $thumbnail_height = $math[1];
            }
            else if (preg_match("#^([\d]+)[xX]([\d]+)$#isUu", $_size, $math)) {
                $thumbnail_width = $math[1];
                $thumbnail_height = $math[2];
            }
            else {
                $thumbnail_width = false;
                $thumbnail_height = false;
                $_size = false;
                throw new Exception("Incorrect image size");
            }
        }

        if(!$size) {
            $image = $this->_getPath();;
        }
        else {
            $image = $this->_getPath($size);
            $imageFullPath = Yii::getAlias('@storage/' . $image);
            if(!file_exists($imageFullPath)) {
                $imageSource = Yii::getAlias('@storage/' . $this->_getPath());
                $this->_thumbnailProcessor($imageSource, $imageFullPath, $thumbnail_width, $thumbnail_height, $params);
            }
        }
        return $image;
    }

    protected function _thumbnailProcessor($source_path, $thumbnail_path, $thumbnail_width, $thumbnail_height, $params = []) {
        $imagine = Image::getImagine();

        $image = $imagine->open($source_path);
        $currentSize  = $image->getSize();

        $mode = ImageInterface::THUMBNAIL_OUTBOUND;
        //$mode = ImageInterface::THUMBNAIL_INSET;


        if (empty($thumbnail_width)) $thumbnail_width = $currentSize->getWidth();
        if (empty($thumbnail_height)) $thumbnail_height = $currentSize->getHeight();

        $size = new Box($thumbnail_width, $thumbnail_height);

        if ($params['modifedObject']) {
            if ($params['modifedObject'] instanceof ImageModifedInterface) {
                throw new Exception("'modifedObject' not implement from ImageModifedInterface interface");
            }

            return $params['modifedObject']->execute($source_path, $thumbnail_path, $params);
        }
        else if($params['animate'] && count($image->layers()) > 1) {
            $imageNew = $imagine->create($size);
            $imageNewLayers = $imageNew->layers();

            $layers = $image->layers();
            foreach ($layers as $frame) {
                $imageNewLayers->add($frame->resize($size));
            }
            return $imageNew->save($thumbnail_path, $params["option"]);
        }
        else if(!$params['animate'] && count($image->layers()) > 1) {
            $imageNew = $imagine->create($size);
            $imageNewLayers = $imageNew->layers();

            $layers = $image->layers();
            $imageNewLayers->add($layers[0]->resize($size));

            return $imageNew->save($thumbnail_path, $params["option"]);
        }
        else {
            $image = $image->thumbnail($size, $mode);
            return $image->save($thumbnail_path, $params["option"]);
        }

        return false;
    }

}
