<?php
namespace bot\common;

use bot\Request;
use yii\base\BootstrapInterface;

class Command implements BootstrapInterface
{
    public $help = 'help';

    const CATCH_PATH = 9;

    public function bootstrap($app)
    {
        /**
         * @var $request Request
         */
        $request = $app->request;
        

    }

}