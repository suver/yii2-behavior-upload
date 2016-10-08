<?php
/**
 * Created by IntelliJ IDEA.
 * User: suver
 * Date: 07.10.16
 * Time: 0:14
 */

namespace suver\behavior\upload;

interface FileInterface {


    /**
     * Препроцессор. Выполняет предварительные команды с файлом
     * @return mixed
     */
    public function preprocessor();

    public function byDefault($file=false);

    /**
     * Вернет директорию файла
     * @return mixed
     */
    public function getDirectory();

    /**
     * Вернет тип файла
     * @return mixed
     */
    public function getType();

    /**
     * Вернет true если файл загружен
     * @return mixed
     */
    public function hasFile();

    /**
     * Вернет имя файла
     * @return mixed
     */
    public function getName();

    /**
     * Вернет mime файла
     * @return mixed
     */
    public function getMimeType();

    /**
     * Вернет size файла
     * @return mixed
     */
    public function getSize();

    /**
     * Вернет оригинальное название файла
     * @return mixed
     */
    public function getOriginalName();

    /**
     * Вернет параметры файла
     * @return mixed
     */
    public function getParams();

    /**
     * Вернет параметры файла
     * @return mixed
     */
    public function getHash();

    /**
     * Вернем разширение файла
     * @return mixed
     */
    public function getExtension();

    /**
     * Удаление файла и модели
     * @return mixed
     */
    public function delete();

    /**
     * Вернет относительный путь к диреткории с файлами текущего представления
     *
     * @param UploadsInterface $model
     * @return string
     */
    public function getPath();

    /**
     * Вернет полный путь к файлу
     * @param bool $size
     * @return bool|null|string
     */
    public function getFullPath();

    /**
     * Вернет путь из хранилища
     * @param bool $size
     * @return string
     */
    public function getDomainPath();

}
