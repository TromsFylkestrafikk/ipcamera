<?php

namespace TromsFylkestrafikk\Camera\Services;

use Illuminate\Database\Eloquent\Model;

class Tokenizer
{
    protected $tokens = [];

    public function __construct(array $allowed)
    {
        $this->tokens = $allowed;
    }

    public function expand($pattern, Model $model)
    {
        return preg_replace_callback(
            '|\[\[(?<property>[a-zA-Z_]+[a-zA-Z0-9_]*)\]\]|U',
            function ($matches) use ($model) {
                $prop = $matches['property'];
                return in_array($prop, $this->tokens) ? $model->{$prop} : '';
            },
            $pattern
        );
    }
}
