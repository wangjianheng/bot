<?php
namespace bot\common;

use Illuminate\Support\Arr;
use \Yii;
use yii\helpers\ArrayHelper;

class Config
{
    /**
     * 获取配置并合并
     * @param $files 配置文件名
     * @return array
     */
    public static function get($files, $ext = 'php')
    {
        $conf = [];
        foreach (Arr::wrap($files) as $file) {
            //读文件
            $path = Yii::getAlias('@conf') . '/' . $file . ".{$ext}";
            $content = file_exists($path) ? require $path : [];

            //合并
            $conf = ArrayHelper::merge($conf, $content);
        }

        return $conf;
    }

    /**
     * 读配置
     * @param $file 配置文件名
     * @return mixed
     */
    public static function val($key, $ext = 'php')
    {
        list($file, $key) = explode('.', $key, 2);

        return Arr::get(static::get($file), $key);
    }

}