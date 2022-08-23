<?php
use PHPUnit\Framework\TestCase;
use bot\Request;

class RequestTest extends TestCase
{
    protected $request;

    public function setUp()
    {
        $this->request = new Request();
        $this->request->setFrame(frame());
    }

    public function testRequestType()
    {
        //数据异常
        $this->assertEquals('default', $this->request->testRequestType());

        //GROUP/9
        $data = [
            'channel_type' => 'GROUP',
            'type'         => 9,
            'extra'        => [
                'type' => 9,
            ]
        ];
        $this->request->setFrame(frame($data));
        $this->assertEquals('GROUP/9', $this->request->testRequestType());

        //GROUP/a
        $data = [
            'channel_type' => 'GROUP',
            'type'         => 255,
            'extra'        => [
                'type' => 'a',
            ]
        ];
        $this->request->setFrame(frame($data));
        $this->assertEquals('GROUP/a', $this->request->testRequestType());
    }


}
