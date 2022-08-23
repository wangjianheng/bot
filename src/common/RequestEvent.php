<?php

namespace bot\common;
use yii\base\Event;

class RequestEvent extends Event
{
    protected $aborted = false;

    protected $abortedBy = '';

    protected $msg = '';

    public $frame = [];

    public function abort($msg = '', $by = '')
    {
        $this->aborted = true;

        $this->msg = $msg;

        $this->abortedBy = $by;
    }

    public function isAborted()
    {
        return [
            $this->aborted,
            $this->abortedBy,
            $this->msg
        ];
    }

}