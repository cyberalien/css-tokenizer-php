<?php

use \CyberAlien\SimpleTokenizer\Tokenizer;

class TokenizerTest extends \PHPUnit\Framework\TestCase
{
    public function testSimpleBlock()
    {
        // Simple block
        $parser = new Tokenizer([
            'splitRules'    => false,
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('color: red');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => 'code',
                'code'  => 'color: red',
                'index' => 0,
            ]
        ], $result);

        // Multiple rules, semicolon at the end
        $parser = new Tokenizer([
            'splitRules'    => false,
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('color: red; opacity: 1; border: 1px solid blue;');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => 'code',
                'code'  => 'color: red; opacity: 1; border: 1px solid blue;',
                'index' => 0,
            ]
        ], $result);

        // Comment, semicolon at start
        $parser = new Tokenizer([
            'splitRules'    => false,
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize(';color: red; /* opacity: 1; */ border: 1px solid blue');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => 'code',
                'code'  => ';color: red; /* opacity: 1; */ border: 1px solid blue',
                'index' => 0,
            ]
        ], $result);
    }

    public function testSimpleSelector()
    {
        // Simple selector
        $parser = new Tokenizer([
            'splitRules'    => false,
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('a { Color: Red; }');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'a',
                'selectors' => ['a'],
                'index' => 0,
            ],
            [
                'token' => 'code',
                'code'  => 'Color: Red;',
                'index' => 3,
            ],
            [
                'token' => '}',
                'index' => 16,
            ]
        ], $result);

        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('a { Color: Red; }');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'a',
                'selectors' => ['a'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key'   => 'Color',
                'value' => 'Red',
                'index' => 3,
            ],
            [
                'token' => '}',
                'index' => 16,
            ]
        ], $result);

        // Multiple rules, spacing at start and end
        $parser = new Tokenizer([
            'splitRules'    => false,
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize("\n a[href] { color: red; opacity: .5 \n }\n\n");
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'a[href]',
                'selectors' => ['a[href]'],
                'index' => 0,
            ],
            [
                'token' => 'code',
                'code' => 'color: red; opacity: .5',
                'index' => 11,
            ],
            [
                'token' => '}',
                'index' => 38,
            ]
        ], $result);

        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize("\n a[href] { color: red; opacity: .5 \n }\n\n");
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'a[href]',
                'selectors' => ['a[href]'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key' => 'color',
                'value' => 'red',
                'index' => 11,
            ],
            [
                'token' => 'rule',
                'key' => 'opacity',
                'value' => '.5',
                'index' => 23,
            ],
            [
                'token' => '}',
                'index' => 38,
            ]
        ], $result);

        // Simple @rule
        $parser = new Tokenizer([
            'splitRules'    => true,
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize("@page :left {\n margin-left: 4cm;\n margin-right: 3cm !important;\n}");
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => '@page :left',
                'atRule' => 'page',
                'atValues'  => [':left'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key' => 'margin-left',
                'value' => '4cm',
                'index' => 13,
            ],
            [
                'token' => 'rule',
                'key' => 'margin-right',
                'value' => '3cm',
                'important' => true,
                'index' => 32,
            ],
            [
                'token' => '}',
                'index' => 64,
            ]
        ], $result);
    }

    public function testCodeWithComments()
    {
        // Comments
        $parser = new Tokenizer([
            'splitRules'    => false,
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize("/* comment at start */.foo, /* comment in selector */a[href], b { color: red; /* margin: 0; */ opacity: .5; }\n/* the end */");
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => '.foo, a[href], b',
                'selectors'  => ['.foo', 'a[href]', 'b'],
                'index' => 0,
            ],
            [
                'token' => 'code',
                'code' => 'color: red; /* margin: 0; */ opacity: .5;',
                'index' => 65,
            ],
            [
                'token' => '}',
                'index' => 108,
            ],
            [
                'token' => 'code',
                'code' => '/* the end */',
                'index' => 109,
            ]
        ], $result);

        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize("/* comment at start */.foo, /* comment in selector */a[href], b { color: red; /* margin: 0; */ opacity: .5; }\n/* the end */");
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => '.foo, a[href], b',
                'selectors'  => ['.foo', 'a[href]', 'b'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key' => 'color',
                'value' => 'red',
                'index' => 65,
            ],
            [
                'token' => 'rule',
                'key' => 'opacity',
                'value' => '.5',
                'index' => 77,
            ],
            [
                'token' => '}',
                'index' => 108,
            ]
        ], $result);
    }

    public function testEscapedStrings()
    {
        // Escaped string
        $parser = new Tokenizer([
            'splitRules'    => false,
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('div:after { content: "test; line } \\"; " }');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'div:after',
                'selectors'  => ['div:after'],
                'index' => 0,
            ],
            [
                'token' => 'code',
                'code' => 'content: "test; line } \\"; "',
                'index' => 11,
            ],
            [
                'token' => '}',
                'index' => 41,
            ]
        ], $result);

        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('div:after { content: "test; line } \\"; " }');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'div:after',
                'selectors'  => ['div:after'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key' => 'content',
                'value' => '"test; line } \\"; "',
                'index' => 11,
            ],
            [
                'token' => '}',
                'index' => 41,
            ]
        ], $result);

        // Escaped string in selector
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('div[href="/*foo{bar}"], span[data-foo=\'test"\\\'str\'] { color: blue !important }');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'div[href="/*foo{bar}"], span[data-foo=\'test"\\\'str\']',
                'selectors'  => ['div[href="/*foo{bar}"]', 'span[data-foo=\'test"\\\'str\']'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key' => 'color',
                'value' => 'blue',
                'important' => true,
                'index' => 53,
            ],
            [
                'token' => '}',
                'index' => 77,
            ]
        ], $result);

        // Escaped string in value
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('font-family: Test\\;1, Arial;');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => 'rule',
                'key' => 'font-family',
                'value' => 'Test\\;1, Arial',
                'index' => 0,
            ]
        ], $result);
    }

    public function testURLs()
    {
        // Quoted URL
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('div { background: url("test;}{url"); color: blue; }');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'div',
                'selectors'  => ['div'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key' => 'background',
                'value' => 'url("test;}{url")',
                'index' => 5,
            ],
            [
                'token' => 'rule',
                'key' => 'color',
                'value' => 'blue',
                'index' => 36,
            ],
            [
                'token' => '}',
                'index' => 50,
            ]
        ], $result);

        // Unquoted URL
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('div { background: url(data:image/png;base64,whatever/*}{&); color: blue; }');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'div',
                'selectors'  => ['div'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key' => 'background',
                'value' => 'url(data:image/png;base64,whatever/*}{&)',
                'index' => 5,
            ],
            [
                'token' => 'rule',
                'key' => 'color',
                'value' => 'blue',
                'index' => 59,
            ],
            [
                'token' => '}',
                'index' => 73,
            ]
        ], $result);
    }

    public function testNestedRules()
    {
        // Simple nested rule
        $parser = new Tokenizer([
            'splitRules'    => false,
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('.foo { color: red; border: 1px solid red; &:hover { color: blue; }}');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => '.foo',
                'selectors'  => ['.foo'],
                'index' => 0,
            ],
            [
                'token' => 'code',
                'code'  => 'color: red; border: 1px solid red;',
                'index' => 6,
            ],
            [
                'token' => '{',
                'code'  => '&:hover',
                'selectors' => ['&:hover'],
                'index' => 41,
            ],
            [
                'token' => 'code',
                'code'  => 'color: blue;',
                'index' => 51,
            ],
            [
                'token' => '}',
                'index' => 65,
            ],
            [
                'token' => '}',
                'index' => 66,
            ]
        ], $result);

        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('.foo { color: red; border: 1px solid red; &:hover { color: blue; }}');
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => '.foo',
                'selectors'  => ['.foo'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'red',
                'index' => 6,
            ],
            [
                'token' => 'rule',
                'key'  => 'border',
                'value' => '1px solid red',
                'index' => 18,
            ],
            [
                'token' => '{',
                'code'  => '&:hover',
                'selectors' => ['&:hover'],
                'index' => 41,
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'blue',
                'index' => 51,
            ],
            [
                'token' => '}',
                'index' => 65,
            ],
            [
                'token' => '}',
                'index' => 66,
            ]
        ], $result);
    }

    public function testLessSass()
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

    public function testInvalidAtRules()
    {
        // Misplaced @import
        $code = '.foo { color: red; @import "bar.css"; opacity: 0 }';
        $expected = [
            [
                'token' => '{',
                'code'  => '.foo',
                'selectors' => ['.foo'],
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'red',
                'index' => 6,
            ],
            [
                'token' => 'code',
                'code' => '@import "bar.css";',
                'index' => 18,
            ],
            [
                'token' => 'rule',
                'key'  => 'opacity',
                'value' => '0',
                'index' => 37,
            ],
            [
                'token' => '}',
                'index' => 49,
            ]
        ];

        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize($code);
        $this->assertEquals(1, count($parser->errors));
        $this->assertEquals('Invalid css rule on line 1', $parser->errors[0]->getMessage());

        // Same code, ignoring errors
        $parser = new Tokenizer();
        $result = $parser->tokenize($code);
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals($expected, $result);
    }

    public function testInvalidCSS()
    {
        // Missing } at the end
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('.foo { color: red; border: 1px solid red; &:hover { color: blue; }');
        $this->assertEquals(1, count($parser->errors));
        $this->assertEquals('Missing } on line 1', $parser->errors[0]->getMessage());

        // Extra } at the end
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('.foo 
                { 
                    color: red; 
                    border: 1px solid red; 
                    &:hover { 
                        color: blue; 
                    }
                }
            }');
        $this->assertEquals(1, count($parser->errors));
        $this->assertEquals('Unexpected } on line 9', $parser->errors[0]->getMessage());

        // Extra } in the middle
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('.foo {
                color: red; 
                border: 1px solid red;
                } 
            } 
            .bar { 
                .baz { 
                    color: blue; 
            }');
        $this->assertEquals(1, count($parser->errors));
        $this->assertEquals('Unexpected } on line 5', $parser->errors[0]->getMessage());

        // Invalid rule
        $code = '.foo
            { 
                color: red: blue;
                opacity: 0;
            }';
        $expected = [
            [
                'token' => '{',
                'code'  => '.foo',
                'selectors' => ['.foo'],
                'index' => 0,
            ],
            [
                // Invalid rule should be returned as code token
                'token' => 'code',
                'code' => 'color: red: blue;',
                'index' => 18,
            ],
            [
                'token' => 'rule',
                'key'  => 'opacity',
                'value' => '0',
                'index' => 53,
            ],
            [
                'token' => '}',
                'index' => 94,
            ]
        ];
        $parser = new Tokenizer([
            'ignoreErrors'  => false,
            'splitRules'    => true
        ]);
        $result = $parser->tokenize($code);
        $this->assertEquals(1, count($parser->errors));
        $this->assertEquals('Invalid css rule on line 3', $parser->errors[0]->getMessage());
        $this->assertEquals($expected, $result);

        $parser = new Tokenizer([
            'ignoreErrors'  => true,
            'splitRules'    => true
        ]);
        $result = $parser->tokenize($code);
        $this->assertEquals(0, count($parser->errors)); // Errors are ignored
        $this->assertEquals($expected, $result);

        $parser = new Tokenizer([
            'ignoreErrors'  => false,
            'splitRules'    => false
        ]);
        $result = $parser->tokenize($code);
        $this->assertEquals(0, count($parser->errors)); // Cannot detect invalid rules if code isn't split into rules

        // Missing closing quote in string
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize('.foo["bar] {
                color: red;
            }');
        $this->assertEquals(1, count($parser->errors));
        $this->assertEquals('Missing closing " on line 1', $parser->errors[0]->getMessage());

        // Missing closing quote in attribute
        $code = '.foo[\'bar] {
                color: red;
            }';
        $expected = [[
            'token' => 'code',
            'code'  => $code,
            'index' => 0,
            'error' => true
        ]];
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize($code);
        $this->assertEquals(1, count($parser->errors));
        $this->assertEquals('Missing closing \' on line 1', $parser->errors[0]->getMessage());
        $this->assertEquals($expected, $result);

        // Same as above, but ignoring errors should return different code
        $expected = [
            [
                'token' => '{',
                'code'  => '.foo[\'bar]',
                'selectors' => ['.foo[\'bar]'],
                'index' => 0
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'red',
                'index' => 12
            ],
            [
                'token' => '}',
                'index' => 53
            ]
        ];
        $parser = new Tokenizer();
        $result = $parser->tokenize($code);
        $this->assertEquals(0, count($parser->errors));
        $this->assertEquals($expected, $result);

        // Invalid URL
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tokenize(".foo {
                background-image: url(image/png\ntest);
            }");
        $this->assertEquals(1, count($parser->errors));
        $this->assertEquals('Incomplete URL on line 2', $parser->errors[0]->getMessage());
    }
}
