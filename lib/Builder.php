<?php

/**
 * This file is part of the cyberalien/simple-tokenizer package.
 *
 * (c) Vjacheslav Trushkin <cyberalien@gmail.com>
 *
 * This is not open source library.
 * This library can be used only with products available on artodia.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package cyberalien/simple-tokenizer
 */

namespace CyberAlien\SimpleTokenizer;

/**
 * Class that converts tokens list or tree back to css
 */
class Builder
{
    /*
     * Options
     */
    public $minify;
    public $tab;
    public $newline;
    public $ruleSeparator;
    public $selectorsSeparator;
    public $ruleModifiers;
    public $newLineAfterSelector;

    /**
     * Constructor
     *
     * @param array $options
     */
    function __construct($options = []) {
        $this->minify = !isset($options['minify']) ? false : $options['minify'];
        $this->tab = $this->minify ? '' : (!isset($options['tab']) ? "\t" : $options['tab']);
        $this->newline = $this->minify ? '' : (!isset($options['newline']) ? "\n" : $options['newline']);
        $this->newLineAfterSelector = !isset($options['newLineAfterSelector']) ? true : $options['newLineAfterSelector'];
        $this->ruleSeparator = $this->minify ? ':' : ': ';
        $this->selectorsSeparator = $this->minify ? ',' : ', ';
        $this->ruleModifiers = isset($options['ruleModifiers']) ? $options['ruleModifiers'] : ['default', 'important'];
    }

    /**
     * Convert list of tokens to string
     *
     * @param array $tokens
     * @return string
     */
    public function build($tokens) {
        return trim($this->_build($tokens, ''));
    }

    /**
     * Convert list of tokens to string
     *
     * @param array $tokens
     * @param string $space
     * @return string
     */
    protected function _build($tokens, $space) {
        $output = '';
        $lastToken = false;
        $level = 0;

        foreach ($tokens as $token) {
            switch ($token['token']) {
                case 'code':
                    $output .= $space . $token['code'] . $this->newline;
                    break;

                case '}':
                    $level --;
                    $space = substr($space, strlen($this->tab));
                    $output .= $space . '}' . $this->newline;
                    break;

                case '{':
                    if ($lastToken === '}') {
                        // Double new line between 2 items in same scope
                        $output .= $this->newline;
                    }
                    if (isset($token['selectors'])) {
                        $output .= $space . implode($this->selectorsSeparator, $token['selectors']);
                    } elseif (isset($token['atRule'])) {
                        $output .= $space . '@' . $token['atRule'];
                        if (!empty($token['atValues'])) {
                            $values = implode($this->selectorsSeparator, $token['atValues']);
                            $output .= $values === '' ? '' : ' ' . $values;
                        }
                    } else {
                        // Error - use code as backup
                        $output .= $space . $token['code'];
                    }
                    $output .= ($this->newLineAfterSelector ?
                            $this->newline . $space :
                            ($this->minify ? '' : ' ')
                        ) . '{' . $this->newline;
                    if (isset($token['children'])) {
                        $output .= $this->_build($token['children'], $space . $this->tab);
                        $output .= $space . '}' . $this->newline;
                    } else {
                        $level ++;
                        $space .= $this->tab;
                    }
                    break;

                case 'rule':
                    $output .= $space . $token['key'] . $this->ruleSeparator . $token['value'];
                    foreach ($this->ruleModifiers as $mod) {
                        if (!empty($token[$mod])) {
                            $output .= ' !' . $mod;
                        }
                    }
                    $output .= ';' . $this->newline;
                    break;
            }
            $lastToken = $token['token'];
        }

        return $output;
    }
}
