<?php

namespace bot\message;

use Illuminate\Support\Arr;

class KmarkDown extends Message
{
    protected $type = Message::KMARK_DOWN;

    /**
     * 加粗
     * @param string $content 文案
     * @return $this
     */
    public function bold($content)
    {
        return $this->push($content, '**');
    }

    /**
     * 斜体
     * @param string $content 文案
     * @return $this
     */
    public function italic($content)
    {
        return $this->push($content, '*');
    }

    /**
     * 加粗斜体
     * @param string $content 文案
     * @return $this
     */
    public function boldItalic($content)
    {
        return $this->push($content, '***');
    }

    /**
     * 删除线
     * @param string $content 文案
     * @return $this
     */
    public function delLine($content)
    {
        return $this->push($content, '~~');
    }

    /**
     * 链接
     * @param string $content 文案
     * @param string $url 链接
     * @return $this
     */
    public function link($content, $url)
    {
        return $this->push($content, '[')->push($url, '(');
    }

    /**
     * 分割线
     * @return $this
     */
    public function cut()
    {
        return $this->push(PHP_EOL . '---');
    }

    /**
     * 引用
     * @param string $content 内容
     * @return $this
     */
    public function quote($content)
    {
        if (! $this->isEmpty()) {
            $this->push(PHP_EOL);
        }

        return $this->push($content,  '> ');
    }

    /**
     * 下划线
     * @param string $content 内容
     * @return $this
     */
    public function ins($content)
    {
        return $this->push($content, '(ins)');
    }

    /**
     * 剧透
     * @param string $content 内容
     * @return $this
     */
    public function spl($content)
    {
        return $this->push($content, '(spl)');
    }

    /**
     * emoji
     * @param string $content 内容
     * @return $this
     */
    public function emoji($content)
    {
        return $this->push($content, ':');
    }

    /**
     * 提及频道
     * @param string $content 内容
     * @return $this
     */
    public function chn($content)
    {
        return $this->push($content, '(chn)');
    }

    /**
     * at用户
     * @param string $content 内容
     * @return $this
     */
    public function met($content)
    {
        return $this->push($content, '(met)');
    }

    /**
     * at角色
     * @param string $content 内容
     * @return $this
     */
    public function rol($content)
    {
        return $this->push($content, '(rol)');
    }

    /**
     * 代码
     * @param string $content 内容
     * @return $this
     */
    public function code($content)
    {
        return $this->push($content, '`');
    }

    /**
     * 代码块
     * @param sting $lang     语言
     * @param string $content 内容
     * @return $this
     */
    public function codeBlock($lang, $content)
    {
        if (! $this->isEmpty()) {
            $this->push(PHP_EOL);
        }

        $content = $lang . PHP_EOL. $content;
        return $this->push($content, '```');
    }

    /**
     * 转义
     * @param string $content 内容
     * @return $this
     */
    public function trans($content)
    {
        return $this->push($content, '\\');
    }

    /**
     * 加元素
     * @param string $content 内容
     * @param string $with 类型
     * @return $this
     */
    public function push($content, $with = '')
    {
        return parent::push($this->with($content, $with));
    }

    /**
     * 拼接
     * @param string $content 内容
     * @param string $begin 类型
     * @return string
     */
    private function with($content, $begin)
    {
        $map = [
            '['   => ']',
            '('   => ')',
            '---' => '',
            '> '  => PHP_EOL . PHP_EOL,
            '\\'  => '',
        ];

        $end = Arr::get($map, $begin, $begin);
        return $begin . $content . $end;
    }
}
