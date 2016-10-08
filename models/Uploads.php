<?php

namespace suver\behavior\upload\models;

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
 * This is the model class for table "{{%uploads}}".
 *
 * @property integer $id
 * @property string $model
 * @property integer $parent_id
 * @property string $mime_type
 * @property integer $size
 * @property string $original_name
 * @property string $name
 * @property string $extension
 * @property integer $type
 * @property string $created_at
 * @property string $updated_at
 */
class Uploads extends \yii\db\ActiveRecord implements UploadsInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%uploads}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['model', 'parent_id', 'attribute', 'hash'], 'required'],
            [['parent_id', 'size', 'type'], 'integer'],
            [['created_at', 'updated_at', 'params'], 'safe'],
            [['model', 'mime_type', 'original_name', 'name'], 'string', 'max' => 255],
            [['extension'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('common', 'ID'),
            'attribute' => Yii::t('common', 'Атрибут модели'),
            'hash' => Yii::t('common', 'Хэш'),
            'model' => Yii::t('common', 'Модель'),
            'parent_id' => Yii::t('common', 'ID в модели'),
            'mime_type' => Yii::t('common', 'Mime Type'),
            'size' => Yii::t('common', 'Размер'),
            'params' => Yii::t('common', 'Параметры'),
            'original_name' => Yii::t('common', 'Оригинальное имя'),
            'name' => Yii::t('common', 'Файл'),
            'extension' => Yii::t('common', 'Расширение'),
            'type' => Yii::t('common', 'Тип'),
            'created_at' => Yii::t('common', 'Создано'),
            'updated_at' => Yii::t('common', 'Обновлено'),
        ];
    }

    /**
     * @inheritdoc
     * @return UploadsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UploadsQuery(get_called_class());
    }

    public function getName() {
        return $this->name;
    }

    public function getExtension() {
        return $this->extension;
    }

    public function beforeDelete()
    {
        return parent::beforeDelete();

        unlink();
    }

}
