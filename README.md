Frapse File Upload Behavior
===========================
File Upload Behavior

Installation
------------


The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require suver/yii2-behavior-upload
```

or add

```
"suver/yii2-behavior-upload": "*"
```

to the require section of your `composer.json` file.



Install migrations

```bash
yii migrate --migrationPath=@vendor/suver/yii2-behavior-upload/migrations
```

How USE
-------

0. Create @storage alias for you structur

1. COnfigure you nginx server like this
```
server {
	charset utf-8;
	client_max_body_size 128M;

	listen 80; ## listen for ipv4

	server_name storage.example.com;
	root        /var/www/com/example/storage/;
	index       index.php;

	location / {
	
	}	
}

```

or add simlink as example

```bash
ln -s /var/www/com/example/storage /var/www/com/example/frontend/web/storages
ln -s /var/www/com/example/storage /var/www/com/example/backend/web/storages

```

or configure your another web server


2. Configure your config file as example

```
    'modules' => [
        'uploads' => [
            'class' => 'suver\behavior\upload\Module',
            'storageDomain' => '//storage.example.com'
            // or 
            //'storageDomain' => '//example.com/storages'
        ],
    ],
```

3. You must add behavior like this
 

```php

public function behaviors()
{
    return [
        [
            'class' => TimestampBehavior::className(),
            'value' => new Expression('NOW()'),
        ],
        [ 
            'class' => \suver\behavior\upload\UploadBehavior::className(),
            'attribute' => 'photo',
            'thumbnail' => [
                'admin_preview' => ['size' => '200x200', 'prefix' => 'v1'],
                'admin_preview_without_animate' => ['size' => '100x100', 'prefix' => 'v2', 'option' => ['jpeg_quality' => 10], 'animate' => false],
                'medium2' => ['size' => 'x100'],
                'medium3' => ['size' => '100'],
            ],
        ],
    ];
}

```

4. You must add rule for file like this
 

```php

/**
 * @inheritdoc
 */
public function rules()
{
    return [
        ['photo', 'file', 'extensions' => ['jpg','png','gif'], 'maxSize' => 10*1024*1024, 'maxFiles' => 1]
    ];
}

```

5. Upgrade your upload form like this

php
```
<!-- Get Uploaded image -->
<img src="<?php echo $model->linkedFile('photo')->thumbnail('admin_preview')->byDefault('/images/gogol.jpg') ?>">

<!-- Render upload input -->
<?= $form->field($model, 'photo')->fileInput() ?>

```

Its all of you need create for uploaded files for you model )

You don`t create data base or uploaded logic. All you need it add behafior and smoke sigars )) 


REMEMBER: Your attribute for file from UploadBehavior options mast not exists in your model class

OPTIONS
-------

* attribute - your file attribute 
* thumbnail - configuration for you thumbnail
* * size - size image like this 200x150 where 200 is width and 150 is height
* * prefix - add prefix for file name. When you modifien your thumbnail options you sey thenks for me fo this options ))
* * option - imagine option for save file
* * preGenerationOff - if this option is FALSE, this thumbnail don`t generated toafter uploaded file
* animate - if `animate == false` you gif animation is off
* instanceByName - if true your UploadedFile instance changed on `UploadedFile::getInstancesByName`
* type - type of file. Exmaple: image. All type see `UploadBehavior::$types`
* fileModel - You model for file modified. You class must be instanceof FileInterface like ImageFile class
* messageFileNotFound - if file not found you see this message
* multiUpload - if TRUE you can a lot uploaded file, else you can only one file

WARNING: If you wont to lot upload you must add  `multiUpload=true` to your behavior options


USE after upload
----------------

Like Example

```php

<img src="<?php echo $model->linkedFile('photo')->thumbnail('admin_preview')->byDefault('/images/gogol.jpg') ?>">
```

Other method

```php

$model->addFile(/var/www/com/example/storage/er/rt/errtsjdhfjsdhsdfsdfsd/errtsjdhfjsdhsdfsdfsd.jpg);
// Add file from path

$model->addHttpFile(http://storage.example.com/er/rt/errtsjdhfjsdhsdfsdfsd/errtsjdhfjsdhsdfsdfsd.jpg);
// Add file from http

$model->httpFileExists(http://storage.example.com/er/rt/errtsjdhfjsdhsdfsdfsd/errtsjdhfjsdhsdfsdfsd.jpg);
// return TRUE if remote file is exists else FALSE

var_dump($model->linkedFiles('photo'));
//=> [
//    ImageFile object,
//    File object,
//    ImageFile object,
// ]
// return list of uploaded file object

echo $model->linkedFile('photo')
// if exists you see http://storage.example.com/er/rt/errtsjdhfjsdhsdfsdfsd/errtsjdhfjsdhsdfsdfsd.jpg
// if NOT exists you see ''

$model->linkedFile('photo')->thumbnail('admin_preview')
// if exists you see http://storage.example.com/er/rt/errtsjdhfjsdhsdfsdfsd/v1_errtsjdhfjsdhsdfsdfsd_200x200.jpg
// if NOT exists you see ''

$model->linkedFile('photo')->thumbnail('admin_preview')->byDefault('/images/gogol.jpg')
// if exists you see http://storage.example.com/er/rt/errtsjdhfjsdhsdfsdfsd/v1_errtsjdhfjsdhsdfsdfsd_200x200.jpg
// if NOT exists you see /images/gogol.jpg

$model->linkedFile('photo')->getName();
// => errtsjdhfjsdhsdfsdfsd

$model->linkedFile('photo')->getExtension();
// => jpg

$model->linkedFile('photo')->delete();
// => delete you file

$model->linkedFile('photo')->getPath();
// => /er/rt/errtsjdhfjsdhsdfsdfsd/errtsjdhfjsdhsdfsdfsd.jpg

$model->linkedFile('photo')->getFullPath();
// => /var/www/com/example/storage/er/rt/errtsjdhfjsdhsdfsdfsd/errtsjdhfjsdhsdfsdfsd.jpg

$model->linkedFile('photo')->getDomainPath();
// => http://storage.example.com/er/rt/errtsjdhfjsdhsdfsdfsd/errtsjdhfjsdhsdfsdfsd.jpg

$model->linkedFile('photo')->hasFile();
// => TRUE if file exists else FALSE

$model->linkedFile('photo')->getType();
// => UploadBehavior::TYPE_IMAGE == 1

$model->linkedFile('photo')->getMimeType();
// => image/jpeg

$model->linkedFile('photo')->getSize();
// => 1545454 - image size

$model->linkedFile('photo')->getOriginalName();
// => avatar.jpg

$model->linkedFile('photo')->getDirectory()
// => /er/rt/errtsjdhfjsdhsdfsdfsd

$model->linkedFile('photo')->getParams();
// => [width => 1000, height => 1500]

$model->linkedFile('photo')->getHash();
// => sdfsdfsfsdfsdfhsdjfh**sdfsdf - MD5 hash for file content


```
