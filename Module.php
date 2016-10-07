<?php

namespace suver\behavior\upload;

/**
 * user module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'suver\behavior\upload\controller';
    public $storageDomain = '';

    public $menu = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        /*$this->menu = [
            [
                'label' => '<i class="fa fa-home"></i> Загруженые файлы',
                'url' => ['/books'],
                'alias' => ['uploads'],
                'items' => [
                    [
                        'label' => 'Каталог книг',
                        'url' => ['/uploads/list'],
                        'alias' => ['uploads/list'],
                    ],
                ],
            ],
        ];*/

        if (\Yii::$app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'suver\behavior\upload\commands';
        }

        // инициализация модуля с помощью конфигурации, загруженной из config.php
        \Yii::configure($this, require(__DIR__ . '/config.php'));

        // custom initialization code goes here
    }
}
