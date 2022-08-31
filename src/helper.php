<?php

if (! function_exists('app')) {
    /**
     * @param string|array|callable $type the object type
     * @param array $params the constructor parameters
     * @return object the created object
     * @throws \yii\base\InvalidConfigException
     */
    function app($type, array $params = [], $with = [])
    {
        $app = Yii::createObject($type, $params);

        return Yii::configure($app, $with);
    }
}

if (! function_exists('get')) {
    /**
     * 设置Component
     * @param string $abstract 类名
     * @param array $paramsClass 实例属性
     * @param array $paramsVals 值属性
     * @return callable
     */
    function get($id)
    {
        try {
            return Yii::$app->get($id) ?? $id;
        } catch (Exception $e) {
            return $id;
        }
    }
}


if (! function_exists('handleVal')) {
    /**
     * 拦截空入参 并接异常
     * @param callable $callable 回调
     * @param callable $handler 接异常
     * @return mixed
     */
    function handleVal(callable $callable, $handler)
    {
        return function($val) use ($callable, $handler) {
            if (! $val) {
                return null;
            }

            try {
                return call_user_func($callable, $val);
            } catch (\Exception $e) {
                call_user_func($handler, $e);
            }
        };
    }
}

if (! function_exists('lazyComponent')) {
    /**
     * 设置Component 优先尝试获取实例
     * @param string $abstract 类名
     * @param array $params 属性
     * @return callable
     */
    function lazyComponent($abstract, array $params)
    {
        return function () use ($abstract, $params) {
            return Yii::configure(
                Yii::createObject($abstract),
                array_map('get', $params)
            );
        };
    }
}

if (! function_exists('request')) {
    /**
     * 获取request实例
     * @return \bot\request\Request
     */
    function request()
    {
        return Yii::$app->request;
    }
}