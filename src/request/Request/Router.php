<?php
namespace bot;

use Illuminate\Support\Arr;
use yii\base\BootstrapInterface;
use bot\common\Config;

class Router implements BootstrapInterface
{

    protected $routes = [];

    public $routeConf = 'route';

    /**
     * boot
     * @param \yii\base\Application $app
     */
    public function bootstrap($app)
    {
        //加载路由
        $this->load(
            Config::get($this->routeConf)
        );
    }

    /**
     * 根据信令类型转成路由
     * @param string $type 请求类型
     * @return string
     */
    public function trans($type)
    {
        return Arr::get($this->routes, $type);
    }

    /**
     * 从配置文件加载路由
     */
    protected function load($routes, $group = '')
    {
        foreach ($routes as $route => $path) {
            if (is_array($path)) {
                $this->load($path, $route);
                continue;
            }

            $route = trim($group . '/' . $route, '/');
            $this->routes[$route] = $path;
        }
    }


}