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

    /**
     * Perform token expansion on string.
     *
     * @param string $pattern
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param bool $quote  Quote preg special characters in replaced tokens
     * @return string
     */
    public function expand(string $pattern, Model $model, bool $quote = false)
    {
        return preg_replace_callback(
            '|\[\[(?<property>[a-zA-Z_]+[a-zA-Z0-9_]*)\]\]|U',
            function ($matches) use ($model, $quote) {
                $prop = $matches['property'];
                $replace = in_array($prop, $this->tokens) ? $model->{$prop} : $matches[0];
                return $quote ? preg_quote($replace) : $replace;
            },
            $pattern
        );
    }
}
