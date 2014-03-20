<?php

use Modules\Markdown\Markdown;

class MarkdownTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Markdown
     */
    private $markdown;

    public function setUp()
    {
        $this->markdown = new Markdown();
    }

    public function fixtureProvider()
    {
        return array(
            array('0.txt', '0_expectation.txt')
        );
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testStandardFormatters($source, $expectation)
    {
        $result = $this->markdown->format(file_get_contents('Fixtures/' . $source));

        $expected = file_get_contents('Fixtures/' . $expectation);

        $this->assertEquals($expected, $result . "\n");
    }
}
