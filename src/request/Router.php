<?php
namespace bot\request;

use Illuminate\Support\Collection;
use yii\base\BootstrapInterface;
use bot\common\Config;

class Router
{
    /**
     * @var Collection
     */
    protected $routes = null;

    public $routeConf = 'route';

    public function __construct()
    {
        //加载路由
        $this->load(
            Config::get($this->routeConf)
        );
    }

    /**
     * 根据信令类型转成路由
     * @param Request $request
     * @return array
     */
    public function trans(Request $request)
    {
        $find = $this->routes->first(function ($route) use ($request) {
            /**
             * @var Route $route
             */
            return $route->match($request);
        });

        $path = $find->path ?? null;
        return [$path, []];
    }

    /**
     * 从配置文件加载路由
     */
    protected function load($routes, $group = '')
    {
        $this->routes = collect();
        foreach ($routes as $route => $path) {
            if (is_array($path) && ! isset($path[0])) {
                $this->load($path, $route);
                continue;
            }

            $route = trim($group . '/' . $route, '/');
            foreach ((array)$path as $p) {
                $this->routes->add(new Route($route, $p));
            }
        }

        //有过滤项的排在前边
        $this->routes = $this->routes->sortByDesc(function (Route $route) {
            return intval($route->hasCondition());
        });
    }
}