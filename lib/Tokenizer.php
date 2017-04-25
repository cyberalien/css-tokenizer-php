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
 * Class that converts css code into list or tree of tokens
 */
class Tokenizer
{
    /**
     * Convert list or tree of tokens to string
     *
     * @param array $tokens
     * @param array $options (optional)
     * @return string
     */
    public static function build($tokens, $options = []) {
        return (new Builder($options))->build($tokens);
    }

    /*
     * Options
     */
    public $splitRules;
    public $ignoreErrors;
    public $lessSyntax;
    public $ruleModifiers;
    public $errors;

    /*
     * Internal stuff
     */
    protected $_tokens;
    protected $_css;
    protected $_cssLC;

    /**
     * Constructor
     *
     * @param array $options (optional)
     */
    public function __construct($options = []) {
        $this->splitRules = !isset($options['splitRules']) || $options['splitRules'] !== false;
        $this->ignoreErrors = !isset($options['ignoreErrors']) || $options['ignoreErrors'] !== false;
        $this->lessSyntax = !empty($options['lessSyntax']);
        $this->ruleModifiers = isset($options['ruleModifiers']) ? $options['ruleModifiers'] : ['default', 'important'];
    }

    /**
     * Get tokens as tree
     *
     * @param string $css
     * @return array
     */
    public function tree($css) {
        // Do stuff
        $this->_tokens = $this->tokenize($css);
        $results = $this->_parseTokens(array_shift($this->_tokens));

        // Add remaining items to root element
        while (count($this->_tokens)) {
            if (!$this->ignoreErrors) {
                $this->errors[] = new ParseError('Unmatched }', $this->_css, $this->_tokens[0]['index']);
            }
            $results = array_merge($results, $this->_parseTokens(array_shift($this->_tokens)));
        }

        return $results;
    }

    /**
     * Convert css into list of tokens
     *
     * @param string $css
     * @return array
     */
    public function tokenize($css) {
        $this->_css = $css;
        $this->_cssLC = strtolower($this->_css);
        $this->errors = [];

        $start = 0;
        $words = [];
        $items = [];
        $depth = 0;
        $functionDepth = 0;
        $expressionDepth = 0;
        $blockStart = 0;
        $selectorStart = 0;
        $error = false;
        $cssLength = strlen($this->_css);

        $validTokens = ['"', "'", '/*', '{', '}', ';', 'url(', '\\'];
        if ($this->lessSyntax) {
            $validTokens = array_merge($validTokens, ['(', ')', '//', '@{', '#{']);
        }

        $tokens = $this->_findTokens($validTokens);
        foreach($tokens as $token) {
            if ($token['index'] < $start) {
                continue;
            }

            switch ($token['token']) {
                case '//':
                    // Skip to end of line
                    $words[] = [
                        'type'  => 'text',
                        'text'  => substr($this->_css, $start, $token['index'] - $start),
                        'index' => $start,
                    ];
                    $start = $token['index'];

                    $end1 = strpos($this->_css, "\n", $start + 2);
                    $end2 = strpos($this->_css, "\r", $start + 2);
                    $end = $end1 === false ? $end2 : ($end2 === false ? $end1 : min($end1, $end2));
                    if ($end === false) {
                        // Last string
                        $start = $cssLength;
                        break;
                    }

                    $start = $end + 1;
                    break;

                case '/*':
                    // Skip to end of comment
                    $words[] = [
                        'type'  => 'text',
                        'text'  => substr($this->_css, $start, $token['index'] - $start),
                        'index' => $start,
                    ];
                    $start = $token['index'];

                    $end = strpos($this->_css, '*/', $start + 2);
                    if ($end === false) {
                        // Skip to end of file
                        if (!$this->ignoreErrors) {
                            $this->errors[] = new ParseError('Missing comment closing statement', $this->_css, $start);
                            $error = true;
                        }
                        $start = $cssLength;
                        break;
                    }

                    $start = $end + 2;
                    break;

                case '\\':
                    // Escaped character, skip next character
                    $words[] = [
                        'type'  => 'text',
                        'text'  => substr($this->_css, $start, $token['index'] - $start + 2),
                        'index' => $start,
                    ];
                    $start = $token['index'] + 2;
                    break;

                case 'url(':
                    $words[] = [
                        'type'  => 'text',
                        'text'  => substr($this->_css, $start, $token['index'] - $start),
                        'index' => $start,
                    ];
                    $start = $token['index'];

                    // Skip to end of URL
                    $end = $this->_findEndOfURL($start);
                    if (!is_numeric($end)) {
                        // Invalid URL - skip "url"
                        $words[] = [
                            'type'  => 'text',
                            'text'  => substr($this->_css, $start, 3),
                            'index' => $start,
                        ];
                        if (!$this->ignoreErrors) {
                            $this->errors[] = new ParseError('Incomplete URL', $this->_css, $start);
                            $error = true;
                        }
                        $start += 3;
                        break;
                    }
                    $words[] = [
                        'type'  => 'url',
                        'text'  => substr($this->_css, $start, $end - $start),
                        'index' => $start,
                    ];
                    $start = $end;
                    break;

                case '"':
                case '\'':
                    $words[] = [
                        'type'  => 'text',
                        'text'  => substr($this->_css, $start, $token['index'] - $start),
                        'index' => $start,
                    ];
                    $start = $token['index'];

                    // Skip to end of quoted string
                    $end = $this->_findEndOfQuotedString($token['token'], $start);
                    if ($end === false) {
                        // Missing closing quote
                        if ($this->ignoreErrors) {
                            $words[] = [
                                'type'  => 'text',
                                'text'  => substr($this->_css, $start, 1),
                                'index' => $start,
                            ];
                            $start ++;
                        }
                        else {
                            $this->errors[] = new ParseError('Missing closing ' . $token['token'], $this->_css, $start);
                            $error = true;
                            $words[] = [
                                'type'  => 'code',
                                'text'  => substr($this->_css, $start),
                                'index' => $start,
                            ];
                            $start = $cssLength;
                        }
                        break;
                    }
                    $words[] = [
                        'type'  => 'string',
                        'text'  => substr($this->_css, $start, $end - $start),
                        'index' => $start,
                    ];
                    $start = $end;
                    break;

                case ';':
                    if ($functionDepth > 0) {
                        break;
                    }
                    if ($this->splitRules) {
                        $words[] = [
                            'type'  => 'text',
                            'text'  => substr($this->_css, $start, $token['index'] - $start),
                            'index' => $start,
                        ];
                        $items[] = $this->_checkRule($words, $token['token'], $error);
                        if ($error) {
                            $items[count($items) - 1]['error'] = true;
                            $error = false;
                        }
                    }
                    $selectorStart = $start = $token['index'] + 1;
                    $words = [];
                    break;

                case '{':
                    // Get selector
                    if (!$this->splitRules) {
                        if ($selectorStart > $blockStart) {
                            $code = trim(substr($this->_css, $blockStart, $selectorStart - $blockStart));
                            if (strlen($code)) {
                                $items[] = [
                                    'token' => 'code',
                                    'code'  => $code,
                                    'index' => $blockStart,
                                ];
                                if ($error) {
                                    $items[count($items) - 1]['error'] = true;
                                    $error = false;
                                }
                            }
                        }
                    }
                    $words[] = [
                        'type'  => 'text',
                        'text'  => substr($this->_css, $start, $token['index'] - $start),
                        'index' => $start,
                    ];
                    $items[] = $this->_checkSelectors($words);

                    $blockStart = $selectorStart = $start = $token['index'] + 1;
                    $words = [];
                    $depth ++;
                    break;

                case '}':
                    if ($expressionDepth > 0) {
                        // LESS/SASS expression
                        if ($expressionDepth === 1 && $this->splitRules) {
                            // Find start of expression
                            $found = false;
                            $text = substr($this->_css, $start, $token['index'] - $start + 1);

                            for ($i = count($words) - 1; $i >= 0; $i --) {
                                if (!empty($words[$i]['beforeExpression'])) {
                                    $found = true;
                                    unset($words[$i]['beforeExpression']);
                                    if ($i === count($words) - 1) {
                                        // Previous token starts expression - do not change word tokens
                                        $words[] = [
                                            'type'  => 'expression',
                                            'text'  => $text,
                                            'index' => $start
                                        ];
                                    } else {
                                        // Merge with previous tokens
                                        $start = $words[$i + 1]['index'];
                                        $words = array_slice($words, 0, $i + 1);
                                        $words[] = [
                                            'type'  => 'expression',
                                            'text'  => $text,
                                            'index' => $start
                                        ];
                                    }
                                    break;
                                }
                                $text = $words[$i]['text'] . $text;
                            }
                            if (!$found) {
                                $words[] = [
                                    'type'  => 'expression',
                                    'text'  => $text,
                                    'index' => $start,
                                    'error' => true
                                ];
                            }
                            $start = $token['index'] + 1;
                        }
                        $expressionDepth --;
                        break;
                    }
                    // End of block
                    if ($this->splitRules) {
                        $words[] = [
                            'type'  => 'text',
                            'text'  => substr($this->_css, $start, $token['index'] - $start),
                            'index' => $start,
                        ];
                        $items[] = $this->_checkRule($words, '', $error);
                        if ($error) {
                            $items[count($items) - 1]['error'] = true;
                        }
                    } else {
                        $code = trim(substr($this->_css, $blockStart, $token['index'] - $blockStart));
                        if (strlen($code)) {
                            $items[] = [
                                'token' => 'code',
                                'code'  => $code,
                                'index' => $blockStart,
                            ];
                            if ($error) {
                                $items[count($items) - 1]['error'] = true;
                            }
                        }
                    }
                    $error = false;
                    $items[] = [
                        'token' => '}',
                        'index' => $token['index'],
                    ];

                    if (!$depth && !$this->ignoreErrors) {
                        $this->errors[] = new ParseError('Unexpected }', $this->_css, $token['index']);
                    }
                    $depth --;

                    $blockStart = $selectorStart = $start = $token['index'] + 1;
                    $words = [];
                    $functionDepth = 0;
                    break;

                case '(':
                    // Function with LESS syntax enabled
                    if ($this->splitRules) {
                        $row = [
                            'type'  => 'text',
                            'text'  => substr($this->_css, $start, $token['index'] - $start),
                            'index' => $start,
                        ];
                        if (!$functionDepth) {
                            $row['beforeFunction'] = true;
                        }
                        $words[] = $row;
                        $start = $token['index'];
                    }
                    $functionDepth ++;
                    break;

                case ')':
                    // End of function with LESS syntax enabled
                    if ($functionDepth === 1 && $this->splitRules) {
                        // Find start of function
                        $found = false;
                        $text = substr($this->_css, $start, $token['index'] - $start + 1);

                        for ($i = count($words) - 1; $i >= 0; $i --) {
                            if (!empty($words[$i]['beforeFunction'])) {
                                $found = true;
                                unset($words[$i]['beforeFunction']);
                                if ($i === count($words) - 1) {
                                    // Previous token starts function - do not change word tokens
                                    $words[] = [
                                        'type'  => 'function',
                                        'text'  => $text,
                                        'index' => $start
                                    ];
                                } else {
                                    // Merge with previous tokens
                                    $start = $words[$i + 1]['index'];
                                    $words = array_slice($words, 0, $i + 1);
                                    $words[] = [
                                        'type'  => 'function',
                                        'text'  => $text,
                                        'index' => $start
                                    ];
                                }
                                break;
                            }
                            $text = $words[$i]['text'] . $text;
                        }
                        if (!$found) {
                            $words[] = [
                                'type'  => 'function',
                                'text'  => $text,
                                'index' => $start,
                                'error' => true
                            ];
                        }
                        $start = $token['index'] + 1;
                    }
                    $functionDepth --;
                    if ($functionDepth < 0) {
                        $functionDepth = 0;
                    }
                    break;

                case '@{':
                case '#{':
                    // Expression with LESS/SASS syntax enabled
                    if ($this->splitRules) {
                        $row = [
                            'type'  => 'text',
                            'text'  => substr($this->_css, $start, $token['index'] - $start + 2),
                            'index' => $start,
                        ];
                        if (!$expressionDepth) {
                            $row['beforeExpression'] = true;
                        }
                        $words[] = $row;
                        $start = $token['index'] + 2;
                    }
                    $expressionDepth ++;
                    break;
            }
        }

        if ($depth > 0 && !$this->ignoreErrors) {
            $this->errors[] = new ParseError('Missing }', $this->_css, $cssLength);
        }

        // Add remaining code
        if ($this->splitRules) {
            $words[] = [
                'type'  => 'text',
                'text'  => substr($this->_css, $start),
                'index' => $start,
            ];
            $items[] = $this->_checkRule($words, '', $error);
            if ($error) {
                $items[count($items) - 1]['error'] = true;
            }
        } else {
            $code = trim(substr($this->_css, $blockStart));
            if (strlen($code)) {
                $items[] = [
                    'token' => 'code',
                    'code'  => $code,
                    'index' => $blockStart,
                ];
                if ($error) {
                    $items[count($items) - 1]['error'] = true;
                }
            }
        }

        return array_values(array_filter($items, function($item) {
            return $item !== false;
        }));
    }

    /**
     * Find all tokens in code
     *
     * @param array $tokens Array of tokens
     * @return array
     */
    protected function _findTokens($tokens) {
        $list = [];

        foreach ($tokens as $token) {
            $index = 0;
            while (true) {
                $index = strpos($this->_cssLC, $token, $index);
                if ($index === false) {
                    break;
                }
                $list[] = [
                    'token' => $token,
                    'index' => $index
                ];
                $index ++;
            }
        }

        usort($list, function($a, $b) {
            return $a['index'] - $b['index'];
        });
        return $list;
    }

    /**
     * Find end of quoted string
     *
     * @param string $quote Quote character
     * @param int $start Position of first quote
     * @return int|bool Position of character after end of string, false if string is broken
     */
    protected function _findEndOfQuotedString($quote, $start) {
        $nextEscape = strpos($this->_css, '\\', $start + 1);
        $end = strpos($this->_css, $quote, $start + 1);

        if ($end === false) {
            // Invalid string
            return false;
        }

        while ($nextEscape !== false && $nextEscape < $end) {
            if ($end === $nextEscape + 1) {
                $end = strpos($this->_css, $quote, $end + 1);
                if ($end === false) {
                    // Invalid string
                    return false;
                }
            }
            $nextEscape = strpos($this->_css, '\\', $nextEscape + 2);
        }

        return $end + 1;
    }

    /**
     * Find end of url
     *
     * @param int $start
     * @return int|ParseError Position of character after end of url() or error message
     */
    protected function _findEndOfURL($start = 0) {
        $length = strlen($this->_css);
        $index = $start + 4;

        while ($index < $length) {
            $next = substr($this->_css, $index, 1);
            switch ($next) {
                case '"':
                case '\'':
                    // quoted url
                    $end = $this->_findEndOfQuotedString($next, $index);
                    if ($end === false) {
                        return new ParseError('Incomplete string', $this->_css, $index);
                    }
                    $end = strpos($this->_css, ')', $end);
                    return $end === false ? new ParseError('Cannot find end of URL', $this->_css, $start) : $end + 1;

                case ' ':
                case "\t":
                case "\r":
                case "\n":
                    // skip whitespace
                    $index ++;
                    break;

                default:
                    // unquoted url
                    while (true) {
                        switch ($next) {
                            case ')':
                                return $index + 1;

                            case '"':
                            case '\'':
                            case '(':
                            case ' ':
                            case "\t":
                            case "\r":
                            case "\n":
                                return new ParseError('Invalid URL', $this->_css, $start);

                            default:
                                if (ord(substr($this->_css, $index, 1)) < 32) {
                                    return new ParseError('Invalid URL', $this->_css, $start);
                                }
                        }
                        $index ++;
                        if ($index >= $length) {
                            return new ParseError('Cannot find end of URL', $this->_css, $start);
                        }
                        $next = substr($this->_css, $index, 1);
                    }
            }
        }
        return new ParseError('Cannot find end of URL', $this->_css, $start);
    }

    /**
     * Check for valid css rule, return either code or rule token
     *
     * @param array $words Array of words
     * @param string $extra Additional text to add if returning code
     * @param bool $ignoreErrors True if errors should be ignored
     * @return array|bool
     */
    protected function _checkRule($words, $extra, $ignoreErrors) {
        $pairs = $this->_findRulePairs($words);

        if (is_bool($pairs)) {
            $value = $this->_mergeWords($words) . $extra;
            if (!strlen($value)) {
                return false;
            }

            $index = $words[0]['index'];
            if ($pairs === false && !$this->ignoreErrors && !$ignoreErrors) {
                $this->errors[] = new ParseError('Invalid css rule', $this->_css, $index);
            }
            return [
                'token' => 'code',
                'code'  => $value,
                'index' => $index,
            ];
        }
        return $pairs;
    }

    /**
     * Merge words list to string
     *
     * @param array $words
     * @return string
     */
    protected function _mergeWords($words) {
        $value = '';

        foreach ($words as $word) {
            $value .= $word['text'];
        }

        return trim($value);
    }

    /**
     * Get token with selectors list
     *
     * @param array $words
     * @return array
     */
    protected function _checkSelectors($words) {
        $selectors = $this->_getSelectors($words);
        $result = [
            'token' => '{',
            'code'  => $this->_mergeWords($words),
            'index' => $words[0]['index'],
        ];

        if (!count($selectors)) {
            return $result;
        }

        if (substr($selectors[0], 0, 1) === '@') {
            $split = preg_split('/\s+/', $selectors[0]);
            $result['atRule'] = substr($split[0], 1);
            $selectors[0] = trim(substr($selectors[0], 1 + strlen($result['atRule'])));
            $result['atValues'] = $selectors;
        } else {
            $result['selectors'] = $selectors;
        }

        return $result;
    }


    /**
     * Get list of selectors from list of words
     *
     * @param array $words
     * @return array
     */
    protected function _getSelectors($words) {
        $selectors = [];
        $selector = '';

        foreach ($words as $word) {
            if ($word['type'] !== 'text') {
                $selector .= $word['text'];
                continue;
            }

            $list = explode(',', $word['text']);
            $selector .= array_shift($list);
            while (count($list) > 0) {
                $selectors[] = trim($selector);
                $selector = array_shift($list);
            }
        }

        $selectors[] = trim($selector);

        $results = [];
        foreach ($selectors as $row) {
            if (strlen($row) > 0) {
                $results[] = $row;
            }
        }
        return $results;
    }

    /**
     * Get key/value pairs from list of words
     *
     * @param array $words
     * @return array|bool
     */
    protected function _findRulePairs($words)
    {
        $key = '';
        $value = '';
        $isKey = true;
        $hasFunction = false;

        foreach ($words as $word) {
            if ($word['type'] !== 'text') {
                if ($isKey) {
                    if (!$this->lessSyntax) {
                        // Cannot have URL or quoted string in key
                        return false;
                    }

                    // Check for function
                    if ($word['type'] === 'function') {
                        $hasFunction = true;
                    }
                    $key .= $word['text'];
                    continue;
                }
                $value .= $word['text'];
                continue;
            }

            $pairs = explode(':', $word['text']);
            if ($this->lessSyntax && count($pairs) > 1) {
                // Check for "&:extend" LESS syntax
                $updated = false;
                for ($index = 1; $index < count($pairs); $index ++) {
                    if ($pairs[$index] === 'extend' || substr($pairs[$index], 0, 7) === 'extend(') {
                        $pairs[$index - 1] .= $pairs[$index];
                        $pairs[$index] = null;
                        $updated = true;
                    }
                }

                if ($updated) {
                    $newPairs = [];
                    foreach ($pairs as $item) {
                        if ($item !== null) {
                            $newPairs[] = $item;
                        }
                    }
                    $pairs = $newPairs;
                }
            }

            if (count($pairs) > 2) {
                return false;
            }
            if (count($pairs) === 2) {
                if (!$isKey) {
                    return false;
                }
                $key .= $pairs[0];
                $value = $pairs[1];
                $isKey = false;
                continue;
            }
            if ($isKey) {
                $key .= $word['text'];
            } else {
                $value .= $word['text'];
            }
        }

        if ($isKey) {
            // True if token should be treated as code
            return $this->lessSyntax ? $hasFunction || substr(trim($key), 0, 1) === '@' : false;
        }

        $key = trim($key);
        $value = trim($value);
        if (!strlen($key) || !strlen($value)) {
            return false;
        }

        $result = [
            'token' => 'rule',
            'key'   => $key,
            'value' => $value,
            'index' => $words[0]['index'],
        ];

        foreach($this->ruleModifiers as $word) {
            if (strtolower(substr($result['value'], -1 - strlen($word))) === '!' . $word) {
                $result[$word] = true;
                $result['value'] = trim(substr($result['value'], 0, strlen($result['value']) - strlen($word) - 1));
                if (!strlen($result['value'])) {
                    return false;
                }
            }
        }

        return $result;
    }

    /**
     * Convert to tree
     *
     * @param array $item
     * @return array
     */
    protected function _parseTokens($item) {
        $results = [];
        while ($item !== null) {
            switch ($item['token']) {
                case '}':
                    return $results;

                case '{':
                    $item['children'] = $this->_parseTokens(array_shift($this->_tokens));
                    $results[] = $item;
                    break;

                default:
                    $results[] = $item;
            }

            $item = array_shift($this->_tokens);
        }
        return $results;
    }
}
