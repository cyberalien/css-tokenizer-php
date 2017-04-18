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
 * Parse error class
 */
class ParseError extends \Exception
{
    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $css CSS
     * @param int $index Index where error occurred, -1 if n/a
     * @param null $previous
     */
    public function __construct($message, $css, $index = -1, $previous = null)
    {
        parent::__construct($message, 0, $previous);

        // Calculate line
        if (is_numeric($index) && $index !== -1) {
            $start = $index;

            // Check for space on left side of remaining code to calculate line start correctly
            $remaining = substr($css, $index);
            $trimmed = ltrim($remaining);
            $end = $start + strlen($remaining) - strlen($trimmed);

            $line = substr_count(substr($css, 0, $end), "\n") + 1;
            $this->message .= ' on line ' . $line;
        }
    }
}
