<?php

use \CyberAlien\SimpleTokenizer\Tokenizer;

class TreeTest extends \PHPUnit\Framework\TestCase
{
    public function testSimpleBlock()
    {
        // Without splitting rules
        $parser = new Tokenizer([
            'splitRules'    => false
        ]);
        $result = $parser->tree('color: red');
        $this->assertEquals([
            [
                'token' => 'code',
                'code'  => 'color: red',
                'index' => 0,
            ]
        ], $result);

        // With splitting rules
        $parser = new Tokenizer();
        $result = $parser->tree('color: red; opacity: 1; padding: 0 !important');
        $this->assertEquals([
            [
                'token' => 'rule',
                'key'  => 'color',
                'value'  => 'red',
                'index' => 0,
            ],
            [
                'token' => 'rule',
                'key'  => 'opacity',
                'value'  => '1',
                'index' => 11,
            ],
            [
                'token' => 'rule',
                'key'  => 'padding',
                'value'  => '0',
                'important' => true,
                'index' => 23,
            ]
        ], $result);
    }

    public function testSimpleSelector()
    {
        // Simple selector
        $parser = new Tokenizer([
            'splitRules'    => false
        ]);
        $result = $parser->tree('a { color: red; text-decoration: none }');
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'a',
                'selectors' => ['a'],
                'index' => 0,
                'children'  => [
                    [
                        'token' => 'code',
                        'code'  => 'color: red; text-decoration: none',
                        'index' => 3
                    ]
                ]
            ]
        ], $result);

        $parser = new Tokenizer();
        $result = $parser->tree('a { color: red; text-decoration: none }');
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'a',
                'selectors' => ['a'],
                'index' => 0,
                'children'  => [
                    [
                        'token' => 'rule',
                        'key'  => 'color',
                        'value' => 'red',
                        'index' => 3
                    ],
                    [
                        'token' => 'rule',
                        'key'  => 'text-decoration',
                        'value' => 'none',
                        'index' => 15
                    ]
                ]
            ]
        ], $result);
    }

    public function testMultipleSelectors()
    {
        // Simple selector
        $parser = new Tokenizer([
            'splitRules'    => false
        ]);
        $result = $parser->tree("a { color: red; text-decoration: none }\nb { font-weight: 500; }");
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'a',
                'selectors' => ['a'],
                'index' => 0,
                'children'  => [
                    [
                        'token' => 'code',
                        'code'  => 'color: red; text-decoration: none',
                        'index' => 3
                    ]
                ]
            ],
            [
                'token' => '{',
                'code'  => 'b',
                'selectors' => ['b'],
                'index' => 39,
                'children'  => [
                    [
                        'token' => 'code',
                        'code'  => 'font-weight: 500;',
                        'index' => 43
                    ]
                ]
            ],
        ], $result);

        $parser = new Tokenizer();
        $result = $parser->tree('a { color: red; text-decoration: none }b{ font-weight: 500; }');
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'a',
                'selectors' => ['a'],
                'index' => 0,
                'children'  => [
                    [
                        'token' => 'rule',
                        'key'  => 'color',
                        'value' => 'red',
                        'index' => 3
                    ],
                    [
                        'token' => 'rule',
                        'key'  => 'text-decoration',
                        'value' => 'none',
                        'index' => 15
                    ]
                ]
            ],
            [
                'token' => '{',
                'code'  => 'b',
                'selectors' => ['b'],
                'index' => 39,
                'children'  => [
                    [
                        'token' => 'rule',
                        'key'  => 'font-weight',
                        'value' => '500',
                        'index' => 41
                    ]
                ]
            ],
        ], $result);
    }

    public function testNestedSelectors()
    {
        $parser = new Tokenizer();
        $result = $parser->tree('a { color: red; text-decoration: none } @media (min-width: 700px) and (orientation: landscape), not all and (monochrome) { a { text-decoration: underline; } }');
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => 'a',
                'selectors' => ['a'],
                'index' => 0,
                'children'  => [
                    [
                        'token' => 'rule',
                        'key'  => 'color',
                        'value' => 'red',
                        'index' => 3
                    ],
                    [
                        'token' => 'rule',
                        'key'  => 'text-decoration',
                        'value' => 'none',
                        'index' => 15
                    ]
                ]
            ],
            [
                'token' => '{',
                'code'  => '@media (min-width: 700px) and (orientation: landscape), not all and (monochrome)',
                'atRule'    => 'media',
                'atValues' => [
                    '(min-width: 700px) and (orientation: landscape)',
                    'not all and (monochrome)'
                ],
                'index' => 39,
                'children'  => [
                    [
                        'token' => '{',
                        'code'  => 'a',
                        'selectors' => ['a'],
                        'index' => 122,
                        'children'  => [
                            [
                                'token' => 'rule',
                                'key'  => 'text-decoration',
                                'value' => 'underline',
                                'index' => 126
                            ]
                        ]
                    ]
                ]
            ],
        ], $result);
    }

    public function testNestSelectors()
    {
        $parser = new Tokenizer();
        $result = $parser->tree('.foo { color: blue; & > .bar { color: red; } opacity: 1;}');
        $this->assertEquals([
            [
                'token' => '{',
                'code'  => '.foo',
                'selectors' => ['.foo'],
                'index' => 0,
                'children'  => [
                    [
                        'token' => 'rule',
                        'key'  => 'color',
                        'value' => 'blue',
                        'index' => 6
                    ],
                    [
                        'token' => '{',
                        'code'  => '& > .bar',
                        'selectors' => ['& > .bar'],
                        'index' => 19,
                        'children'  => [
                            [
                                'token' => 'rule',
                                'key'  => 'color',
                                'value' => 'red',
                                'index' => 30
                            ]
                        ]
                    ],
                    [
                        'token' => 'rule',
                        'key'  => 'opacity',
                        'value' => '1',
                        'index' => 44
                    ]
                ]
            ]
        ], $result);
    }

    public function testInvalidCSS()
    {
        // Missing } at the end
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tree('.foo { color: red; border: 1px solid red; &:hover { color: blue; }');
        $this->assertEquals(1, count($parser->errors));
        $this->assertEquals('Missing } on line 1', $parser->errors[0]->getMessage());

        // Extra } at the end
        $parser = new Tokenizer([
            'ignoreErrors'  => false
        ]);
        $result = $parser->tree('.foo 
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
        $result = $parser->tree('.foo {
                color: red; 
                border: 1px solid red;
                } 
            } 
            .bar { 
                .baz { 
                    color: blue; 
            }');
        $this->assertEquals(2, count($parser->errors));
        $this->assertEquals('Unexpected } on line 5', $parser->errors[0]->getMessage());
        $this->assertEquals('Unmatched } on line 6', $parser->errors[1]->getMessage());
    }
}
