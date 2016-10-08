<?php

namespace suver\behavior\upload\models;

/**
 * This is the ActiveQuery class for [[Uploads]].
 *
 * @see Uploads
 */
class UploadsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Uploads[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Uploads|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
