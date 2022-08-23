<?php

namespace bot\middleware;

use bot\common\RequestEvent;

interface MiddleWareInterface
{
    public function handle(RequestEvent $event):RequestEvent;
}
