<?php

use \CyberAlien\SimpleTokenizer\Tokenizer;

class LessTest extends \PHPUnit\Framework\TestCase
{
    public function testSimpleMixin()
    {
        $parser = new Tokenizer([
            'ignoreErrors'  => false,
            'lessSyntax'    => true
        ]);
        $result = $parser->tokenize('.box-shadow() { color: red; }');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => '.box-shadow()',
                'selectors' => ['.box-shadow()'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'red',
                'index' => 15,
            ],
            [
                'token' => '}',
                'index' => 28,
            ]
        ], $result);
    }

    public function testVariables()
    {
        // Code with variables
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('$test: black; $foo: lighten(#400, 20%); a { color: $test; }');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => 'rule',
                'key'  => '$test',
                'value' => 'black',
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key'  => '$foo',
                'value' => 'lighten(#400, 20%)',
                'index' => 13,
            ],
            [
                'token' => '{',
                'code'  => 'a',
                'selectors' => ['a'],
                'index' => 39,
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => '$test',
                'index' => 43,
            ],
            [
                'token' => '}',
                'index' => 58,
            ]
        ], $result);

        // Mixin with variables
        $parser = new Tokenizer([
            'ignoreErrors'  => false,
            'lessSyntax'    => true
        ]);
        $result = $parser->tokenize('.mixin(@color: black; @margin: 10px; @padding: 20px) {
  color: @color;
  margin: @margin;
  padding: @padding;
}
.class1 {
  .mixin(@margin: 20px; @color: #33acfe);
}
.class2 {
  .mixin(lighten(#efca44, 10%); @padding: 40px);
}');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => '.mixin(@color: black; @margin: 10px; @padding: 20px)',
                'selectors' => ['.mixin(@color: black; @margin: 10px; @padding: 20px)'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => '@color',
                'index' => 54,
            ],
            [
                'token' => 'rule',
                'key'  => 'margin',
                'value' => '@margin',
                'index' => 71,
            ],
            [
                'token' => 'rule',
                'key'  => 'padding',
                'value' => '@padding',
                'index' => 90,
            ],
            [
                'token' => '}',
                'index' => 112,
            ],
            [
                'token' => '{',
                'code'  => '.class1',
                'selectors' => ['.class1'],
                'index' => 113,
            ],
            [
                'token' => 'code',
                'code'  => '.mixin(@margin: 20px; @color: #33acfe);',
                'index' => 123,
            ],
            [
                'token' => '}',
                'index' => 166,
            ],
            [
                'token' => '{',
                'code'  => '.class2',
                'selectors' => ['.class2'],
                'index' => 167,
            ],
            [
                'token' => 'code',
                'code'  => '.mixin(lighten(#efca44, 10%); @padding: 40px);',
                'index' => 177,
            ],
            [
                'token' => '}',
                'index' => 227,
            ]
        ], $result);
    }
}
