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
        foreach ($result as &$item) {
            unset ($item['index']);
        }
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => '.box-shadow()',
                'selectors' => ['.box-shadow()'],
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'red',
            ],
            [
                'token' => '}',
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
        foreach ($result as &$item) {
            unset ($item['index']);
        }
        $this->assertEquals([
            [
                'token' => 'rule',
                'key'  => '$test',
                'value' => 'black',
            ],
            [
                'token' => 'rule',
                'key'  => '$foo',
                'value' => 'lighten(#400, 20%)',
            ],
            [
                'token' => '{',
                'code'  => 'a',
                'selectors' => ['a'],
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => '$test',
            ],
            [
                'token' => '}',
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
        foreach ($result as &$item) {
            unset ($item['index']);
        }
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => '.mixin(@color: black; @margin: 10px; @padding: 20px)',
                'selectors' => ['.mixin(@color: black; @margin: 10px; @padding: 20px)'],
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => '@color',
            ],
            [
                'token' => 'rule',
                'key'  => 'margin',
                'value' => '@margin',
            ],
            [
                'token' => 'rule',
                'key'  => 'padding',
                'value' => '@padding',
            ],
            [
                'token' => '}',
            ],
            [
                'token' => '{',
                'code'  => '.class1',
                'selectors' => ['.class1'],
            ],
            [
                'token' => 'code',
                'code'  => '.mixin(@margin: 20px; @color: #33acfe);',
            ],
            [
                'token' => '}',
            ],
            [
                'token' => '{',
                'code'  => '.class2',
                'selectors' => ['.class2'],
            ],
            [
                'token' => 'code',
                'code'  => '.mixin(lighten(#efca44, 10%); @padding: 40px);',
            ],
            [
                'token' => '}',
            ]
        ], $result);
    }

    public function testMixinWithVariables()
    {
        $parser = new Tokenizer([
            'ignoreErrors'  => false,
            'lessSyntax'    => true
        ]);
        $result = $parser->tokenize('.box-shadow(@style, @c) when (iscolor(@c)) {
  -webkit-box-shadow: @style @c;
  box-shadow:         @style @c;
}
.box-shadow(@style, @alpha: 50%) when (isnumber(@alpha)) {
  .box-shadow(@style, rgba(0, 0, 0, @alpha));
}
.box {
  color: saturate(@base, 5%);
  border-color: lighten(@base, 30%);
  div { .box-shadow(0 0 5px, 30%) }
}
');
        $this->assertEquals(0, count($parser->errors));
        foreach ($result as &$item) {
            unset ($item['index']);
        }
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => '.box-shadow(@style, @c) when (iscolor(@c))',
                'selectors' => ['.box-shadow(@style, @c) when (iscolor(@c))'],
            ],
            [
                'token' => 'rule',
                'key'  => '-webkit-box-shadow',
                'value' => '@style @c',
            ],
            [
                'token' => 'rule',
                'key'  => 'box-shadow',
                'value' => '@style @c',
            ],
            [
                'token' => '}',
            ],
            [
                'token' => '{',
                'code'  => '.box-shadow(@style, @alpha: 50%) when (isnumber(@alpha))',
                'selectors' => ['.box-shadow(@style, @alpha: 50%) when (isnumber(@alpha))'],
            ],
            [
                'token' => 'code',
                'code'  => '.box-shadow(@style, rgba(0, 0, 0, @alpha));',
            ],
            [
                'token' => '}',
            ],
            [
                'token' => '{',
                'code'  => '.box',
                'selectors' => ['.box'],
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'saturate(@base, 5%)',
            ],
            [
                'token' => 'rule',
                'key'  => 'border-color',
                'value' => 'lighten(@base, 30%)',
            ],
            [
                'token' => '{',
                'code'  => 'div',
                'selectors' => ['div'],
            ],
            [
                'token' => 'code',
                'code'  => '.box-shadow(0 0 5px, 30%)',
            ],
            [
                'token' => '}',
            ],
            [
                'token' => '}',
            ],
        ], $result);
    }

    public function testVariablesAndComments()
    {
        $parser = new Tokenizer([
            'ignoreErrors'  => false,
            'lessSyntax'    => true
        ]);
        $result = $parser->tokenize('
// numbers are converted into the same units
@conversion-1: 5cm + 10mm; // result is 6cm
@conversion-2: 2 - 3cm - 5mm; // result is -1.5cm

// conversion is impossible
@incompatible-units: 2 + 5px - 3cm; // result is 4px

// example with variables
@base: 5%;
@filler: @base * 2; // result is 10%
@other: @base + @filler; // result is 15%
');
        $this->assertEquals(0, count($parser->errors));
        foreach ($result as &$item) {
            unset ($item['index']);
        }
        $this->assertEquals([
            [
                'token' => 'rule',
                'key'  => '@conversion-1',
                'value' => '5cm + 10mm',
            ],
            [
                'token' => 'rule',
                'key'  => '@conversion-2',
                'value' => '2 - 3cm - 5mm',
            ],
            [
                'token' => 'rule',
                'key'  => '@incompatible-units',
                'value' => '2 + 5px - 3cm',
            ],
            [
                'token' => 'rule',
                'key'  => '@base',
                'value' => '5%',
            ],
            [
                'token' => 'rule',
                'key'  => '@filler',
                'value' => '@base * 2',
            ],
            [
                'token' => 'rule',
                'key'  => '@other',
                'value' => '@base + @filler',
            ],
        ], $result);
    }
}
