<?php

use \CyberAlien\SimpleTokenizer\Tokenizer;

class BuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testSimpleCodeTokens()
    {
        // 1 code block
        $result = Tokenizer::build([
            [
                'token' => 'code',
                'code'  => 'color: red'
            ]
        ]);
        $this->assertEquals('color: red', $result);

        // 2 code blocks
        $result = Tokenizer::build([
            [
                'token' => 'code',
                'code'  => 'color: red;'
            ],
            [
                'token' => 'code',
                'code'  => 'alpha: 1;'
            ]
        ]);
        $this->assertEquals("color: red;\nalpha: 1;", $result);
    }

    public function testSimpleRules()
    {
        // 1 rule
        $result = Tokenizer::build([
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'red',
            ]
        ]);
        $this->assertEquals('color: red;', $result);

        // 2 code blocks
        $result = Tokenizer::build([
            [
                'token' => 'rule',
                'key'   => 'color',
                'value'  => 'red'
            ],
            [
                'token' => 'rule',
                'key'  => 'text-decoration',
                'value' => 'none',
                'important' => true
            ]
        ]);
        $this->assertEquals("color: red;\ntext-decoration: none !important;", $result);
    }

    public function testListOfSelectors()
    {
        // One simple selector
        $result = Tokenizer::build([
            [
                'token' => '{',
                'code'  => 'a[href]', // no selectors property
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'red',
            ],
            [
                'token' => 'rule',
                'key'  => 'opacity',
                'value' => '.5',
            ],
            [
                'token' => '}',
            ]
        ]);
        $this->assertEquals("a[href]\n{\n\tcolor: red;\n\topacity: .5;\n}", $result);

        // 2 levels
        $result = Tokenizer::build([
            [
                'token' => '{',
                'selectors'  => ['.foo'] // no code property
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'red',
            ],
            [
                'token' => 'rule',
                'key'  => 'border',
                'value' => '1px solid red',
            ],
            [
                'token' => '{',
                // different values for code and selectors - selectors list is used
                'code'  => '&:focus',
                'selectors'  => ['&:hover']
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'blue',
            ],
            [
                'token' => '}',
            ],
            [
                'token' => '}',
            ]
        ]);
        $this->assertEquals(".foo\n{\n\tcolor: red;\n\tborder: 1px solid red;\n\t&:hover\n\t{\n\t\tcolor: blue;\n\t}\n}", $result);

        // at rule without value
        $result = Tokenizer::build([
            [
                'token' => '{',
                'atRule'    => 'foo',
                'code'  => '.ignored'
            ],
            [
                'token' => 'rule',
                'key'  => 'color',
                'value' => 'red',
            ],
            [
                'token' => '}',
            ]
        ]);
        $this->assertEquals("@foo\n{\n\tcolor: red;\n}", $result);
    }

    public function testTree()
    {
        // One simple selector
        $result = Tokenizer::build([
            [
                'token' => '{',
                'code'  => '.foo',
                'children'  => [
                    [
                        'token' => 'rule',
                        'key'   => 'color',
                        'value' => 'blue'
                    ],
                    [
                        'token' => '{',
                        'selectors'  => ['& > .bar'],
                        'children'  => [
                            [
                                'token' => 'rule',
                                'key'   => 'color',
                                'value' => 'red'
                            ]
                        ]
                    ],
                    [
                        'token' => 'rule',
                        'key'  => 'opacity',
                        'value' => '1',
                    ]
                ]
            ]
        ]);
        $this->assertEquals(".foo\n{\n\tcolor: blue;\n\t& > .bar\n\t{\n\t\tcolor: red;\n\t}\n\topacity: 1;\n}", $result);
    }

    public function testCompactLayout()
    {
        $result = Tokenizer::build((new Tokenizer())->tokenize('
                @media (min-width: 100px) and (min-height: 50px), (min-height: 200px) {
                  .foo {
                    color: blue;
                  }
                }
                @media (min-width: 100px) and (min-height: 50px) and (max-width: 500px), (min-height: 200px) and (max-width: 500px), (min-width: 100px) and (min-height: 50px) and (max-width: 900px), (min-height: 200px) and (max-width: 900px) {
                  .foo {
                    color: purple;
                  }
                }
        '), [
            'newLineAfterSelector'  => false
        ]);
        $this->assertEquals("@media (min-width: 100px) and (min-height: 50px), (min-height: 200px) {\n\t.foo {\n\t\tcolor: blue;\n\t}\n}\n\n@media (min-width: 100px) and (min-height: 50px) and (max-width: 500px), (min-height: 200px) and (max-width: 500px), (min-width: 100px) and (min-height: 50px) and (max-width: 900px), (min-height: 200px) and (max-width: 900px) {\n\t.foo {\n\t\tcolor: purple;\n\t}\n}", $result);
    }
}
