<?php

namespace suver\behavior\upload;

use suver\behavior\upload\File;
use suver\behavior\upload\ImageFile;
use suver\behavior\upload\IncorrectFile;
use suver\behavior\upload\models\Uploads;
use Imagine\Gmagick\Font;
use Yii;
use yii\base\Behavior;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use yii\web\UploadedFile;
use yii\imagine\Image;
use common\modules\uploads\models\UploadsInterface;

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
 * Class UploadBehavior
 * @package common\modules\uploads\behaviors
 */
class UploadBehavior extends Behavior
{

    const EVENT_AFTER_UPLOAD = 'afterUpload';

    public $attribute;
    public $thumbnail;
    public $_values;

    /**
     * @var array the scenarios in which the behavior will be triggered
     */
    public $scenarios = ['default', 'insert', 'update', 'delete'];

    /**
     * @var bool Getting file instance by name
     */
    public $instanceByName = false;

    /**
     * @var bool загрузка множества файлов
     */
    public $multiUpload = false;

    /**
     * @var UploadedFile the uploaded file instance.
     */
    protected $_files;

    /**
     * @var UploadedFile the uploaded file instance.
     */
    protected $_httpFiles;

    /**
     * @var UploadedFile the uploaded file instance.
     */
    protected $_pathFiles;

    /**
     * Определить явно тип файла
     * @var null
     */
    public $type = null;

    /**
     * Определить явно модель файла
     * @var null
     */
    public $fileModel = null;

    /**
     * Сообщение если файл не найден
     * @var null
     */
    public $messageFileNotFound = "File not found";

    protected $defaultParams = [
        'option' => [ // Опции сохранения
            //'jpeg_quality' => 100,
            'animated'     => true,
            //'flatten' => false,
            'animated.delay' => 30, // delay in ms
            'animated.loops' => 0,   // number of loops, 0 means infinite
        ],
        'prebuild' => true, // Содавать образ на этапе сохранения оригинала
        'animate' => true, // использовать анимацию? Если нет, будет взят первый фрейм
        'prefix' => false, // префикс для имени файла. Можно помечать модицикации
        'modifedObject' => false, // Объект для создания собственной превьюшки. Должен наследоватся из интерфейса ImageModifedInterface
    ];

    const TYPE_IMAGE = 1;
    const TYPE_UNKNOWN = 0;

    protected $types = [
        'image' => self::TYPE_IMAGE,
        'image/jpeg' => self::TYPE_IMAGE,
        'image/jpg' => self::TYPE_IMAGE,
        'image/png' => self::TYPE_IMAGE,
        'image/gif' => self::TYPE_IMAGE,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->attribute === NULL)
        {
            throw new InvalidConfigException('The "attribute" property must be set.');
        }
    }

    public function canSetProperty($name) {
        return ($name == $this->attribute) ? true : false;
    }

    public function canGetProperty($name, $checkVars=true) {
        return ($name == $this->attribute) ? true : false;
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($name == $this->attribute) {
            $this->_values[$name] = $value;
        }
    }

    public function __get($name)
    {
        if (isset($this->_values[$name])) {
            return $this->_values[$name];
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            BaseActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            BaseActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     *
     */
    public function beforeValidate()
    {
        /** @var BaseActiveRecord $model */
        $model = $this->owner;

        if (in_array($model->scenario, $this->scenarios)) {
            if (($file = $model->{$this->attribute}) instanceof UploadedFile) {
                $this->_files[] = $file;
            }
            else {
                if ($this->instanceByName === true) {
                    $this->_files = UploadedFile::getInstancesByName($this->attribute);
                }
                else {
                    $this->_files = UploadedFile::getInstances($model, $this->attribute);
                }

                if ($this->multiUpload == false) {
                    $model->{$this->attribute} = reset($this->_files);
                }
                else {
                    $model->{$this->attribute} = $this->_files;
                }
            }

            if ($this->_httpFiles && isset($this->_httpFiles[$this->attribute]) && is_array($this->_httpFiles[$this->attribute])) {
                foreach ($this->_httpFiles[$this->attribute] as $file) {
                    if(!$this->httpFileExists($file['file'])) {
                        $this->owner->addError($this->attribute, $this->messageFileNotFound);
                    }
                }
            }

            if ($this->_pathFiles && isset($this->_pathFiles[$this->attribute]) && is_array($this->_pathFiles[$this->attribute])) {
                foreach ($this->_pathFiles[$this->attribute] as $file) {
                    if(!file_exists($file['file'])) {
                        $this->owner->addError($this->attribute, $this->messageFileNotFound);
                    }
                }
            }
        }
    }

    /**
     *
     */
    public function beforeSave()
    {
        /** @var BaseActiveRecord $model */
        $model = $this->owner;

        if (in_array($model->scenario, $this->scenarios) && !empty($this->_files)) {
            foreach ($this->_files as $_file) {
                if ($_file instanceof UploadedFile && !$model->getIsNewRecord() && $this->fileActionOnSave === 'delete') {
                    //$this->deleteFiles($this->attribute);
                }
            }
        }
    }

    /**
     *
     */
    public function afterSave()
    {
        /** @var BaseActiveRecord $model */
        //$model = $this->owner;

        if (!empty($this->_files)) {
            foreach ($this->_files as $_file){
                if ($_file instanceof UploadedFile) {

                    /*$previousFile = NULL;

                    if (!$model->getIsNewRecord()) {
                        //$previousFile = $this->linkedFilesModel($this->attribute);
                    }*/
                    if (!$this->owner->getIsNewRecord() && $this->multiUpload == false) {
                        $previousFiles = $this->linkedFiles($this->attribute);
                        foreach ($previousFiles as $file) {
                            $file->delete();
                        }
                    }

                    $savedFile = $this->save($_file->tempName, $_file->name);
                    $this->afterUpload($savedFile);
                }
            }
        }

        if ($this->_httpFiles && isset($this->_httpFiles[$this->attribute]) && is_array($this->_httpFiles[$this->attribute])) {
            foreach ($this->_httpFiles[$this->attribute] as $file) {
                $savedFile = $this->linkHttpFile($this->attribute, $file['file'], $file['originalName']);
                $this->afterUpload($savedFile);
            }
        }

        if ($this->_pathFiles && isset($this->_pathFiles[$this->attribute]) && is_array($this->_pathFiles[$this->attribute])) {
            foreach ($this->_pathFiles[$this->attribute] as $file) {
                $savedFile = $this->linkHttpFile($this->attribute, $file['file'], $file['originalName']);
                $this->afterUpload($savedFile);
            }
        }

    }

    /**
     * Присутсвует ли файл по http
     * @param $file
     * @return bool
     */
    public function httpFileExists($file) {
        $ch = curl_init($file);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_exec($ch);

        if (curl_errno($ch)) {
            //throw new Exception(curl_error($ch));
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code == 200 ? true : false;
    }

    /**
     * Добавляет http файл в очередь на загрузку
     *
     * @param $attribute
     * @param $file
     * @param bool $originalFileName
     */
    public function addHttpFile($attribute, $file, $originalFileName=false) {
        $this->_httpFiles[$attribute][] = ['file' => $file, 'originalName' => $originalFileName];
    }

    /**
     * Загружает файл по http
     *
     * @param $attribute
     * @param $file
     * @param bool $originalFileName
     * @return array|bool|null|Uploads
     */
    protected function linkHttpFile($attribute, $file, $originalFileName=false) {
        if($this->httpFileExists($file)) {
            $ch = curl_init($file);
            $temp_file = tempnam(sys_get_temp_dir(), 'Tux');
            $fp = fopen($temp_file, 'wb');
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            $originalFileName = $originalFileName ? $originalFileName : basename($file);
            return $this->save($temp_file, $originalFileName);
        }
        return false;
    }

    /**
     * Добавить путь к файлу для его дальнейшей загрузки
     * @param $attribute
     * @param $file
     * @param bool $originalFileName
     */
    public function addFile($attribute, $file, $originalFileName=false) {
        $this->_pathFiles[$attribute][] = ['file' => $file, 'originalName' => $originalFileName];
    }

    /**
     * Сохраняем файл
     *
     * @param $file
     * @return array|Uploads|null
     */
    protected function save($file, $originalName)
    {
        $mime = FileHelper::getMimeType($file);
        $extension = end(FileHelper::getExtensionsByMimeType($mime));

        $type = false;
        if ($this->type) {
            $type = isset($this->types[$this->type]) ? $this->types[$this->type] : false;
        }

        if (!$type) {
            $type = isset($this->types[$mime]) ? $this->types[$mime] : self::TYPE_UNKNOWN;
        }

        $hash = md5(file_get_contents($file));
        $model = Uploads::find()
            ->andWhere(['hash'=>$hash])
            ->andWhere(['attribute'=>$this->attribute])
            ->andWhere(['model'=>$this->owner->className()])
            ->andWhere(['parent_id'=>$this->owner->getPrimaryKey()])
            ->one();

        if(!$model) {
            do {
                $key = md5(time() . Yii::$app->security->generateRandomString(32));
            } while (Uploads::find()->andWhere(['name' => $key])->one());

            $filename = $key;

            $save_to = Yii::getAlias("@storage");
            if (!file_exists($save_to)) mkdir($save_to);

            $save_to = $save_to . '/' . substr($filename, 0, 2);
            if (!file_exists($save_to)) mkdir($save_to);

            $save_to = $save_to . '/' . substr($filename, 2, 2);
            if (!file_exists($save_to)) mkdir($save_to);

            $save_to = $save_to . '/' . $filename;
            if (!file_exists($save_to)) mkdir($save_to);


            $save_to = $save_to . '/' . $key . '.' . $extension;


            file_put_contents($save_to, file_get_contents($file));

            if($type == self::TYPE_IMAGE) {
                list($width, $height, $_type, $_attr) = getimagesize($save_to);
                $params = Json::encode(['width'=>$width, 'height'=>$height]);
            }
            else {
                $params = Json::encode([]);
            }

            $model = new Uploads();
            $model->attributes = [
                'attribute' => $this->attribute,
                'model' => $this->owner->className(),
                'parent_id' => $this->owner->getPrimaryKey(),
                'mime_type' => $mime,
                'params' => $params,
                'size' => filesize($save_to),
                'original_name' => $originalName,
                'hash' => $hash,
                'name' => $key,
                'extension' => $extension,
                'type' => $type,
            ];

            $model->save();

            if(!$model->hasErrors()) {
                $this->_linkedFile($model)->preprocessor();
            }
            else {
                $string = "";
                foreach ($model->getErrors() as $error) {
                    $string .= implode("", $error);
                }
                throw new Exception("Attribute \"{$this->attribute}\" has error \"" . $string . "\"");
            }
        }

        return $model;
    }

    /**
     * Добавляем тригер после загрузки
     * @param $image
     */
    protected function afterUpload($image)
    {
        $this->owner->trigger(self::EVENT_AFTER_UPLOAD);
    }

    /**
     * Удаляем файлы перед удалением модели
     */
    public function beforeDelete()
    {
        if ($this->attribute) {
            if (!$this->owner->getIsNewRecord() && $this->multiUpload == false) {
                $previousFiles = $this->linkedFiles($this->attribute);
                foreach ($previousFiles as $file) {
                    $file->delete();
                }
            }
        }
    }

    /**
     * Вернет модель файла
     * @param $attribute
     * @param bool $size
     * @return array|Uploads|null
     */
    protected function _linkedFile($model) {
        if ($model) {
            if($this->fileModel && is_object($this->fileModel) && $this->fileModel instanceof FileInterface) {
                return $this->fileModel->__construct($model, $this->thumbnail, $this->defaultParams);
            }
            else if ($model->type == self::TYPE_IMAGE) {
                return new ImageFile($model, $this->thumbnail, $this->defaultParams);
            }
            else {
                return new File($model, $this->thumbnail, $this->defaultParams);
            }
        }
        else {
            return new IncorrectFile(null, $this->thumbnail, $this->defaultParams);
        }
    }

    /**
     * Вернет модель файла
     * @param $attribute
     * @param bool $size
     * @return array|Uploads|null
     */
    public function linkedFile($attribute) {
        $model = Uploads::find()
            ->andWhere(['attribute'=>$attribute])
            ->andWhere(['model'=>$this->owner->className()])
            ->andWhere(['parent_id'=>$this->owner->getPrimaryKey()])
            ->one();

        return $this->_linkedFile($model);
    }

    /**
     * Верент модели всех прикрепленных файлов
     * @param $attribute
     * @param bool $size
     * @return array|Uploads|null
     */
    public function linkedFiles($attribute) {
        $models = Uploads::find()
            ->andWhere(['attribute'=>$attribute])
            ->andWhere(['model'=>$this->owner->className()])
            ->andWhere(['parent_id'=>$this->owner->getPrimaryKey()])
            ->all();

        if ($models) {
            $files = [];
            foreach ($models as $model) {
                $files[] = $this->_linkedFile($model);
            }
            return $files;
        }
        return [];
    }
}
