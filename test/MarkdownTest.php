<?php

use Modules\Markdown\LineFormatters\YoutubeFormatter;
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
            array('0.txt', '0_expectation.txt'),
            array('1.txt', '1_expectation.txt'),
            array('2.txt', '2_expectation.txt'),
            array('3.txt', '3_expectation.txt'),
            array('4.txt', '4_expectation.txt'),
            array('5.txt', '5_expectation.txt')
        );
    }

    /**
     * @dataProvider fixtureProvider
     */
    public function testStandardFormatters($source, $expectation)
    {
        $result = $this->markdown->format(file_get_contents(__DIR__ . '/Fixtures/' . $source));

        $expected = file_get_contents(__DIR__ . '/Fixtures/' . $expectation);
        $expected = strtr($expected, array("\r\n" => "\n"));

        $this->assertEquals($expected, $result . "\n");
    }

    public function testMailFormatters()
    {
        $result = $this->markdown->format('<mail@domain.at>');

        $expected = '<p><a href="mailto:mail@domain.at">mail@domain.at</a></p>';

        // strings do not equal due to randomization
        $this->assertNotEquals($expected, $result);
        $this->assertEquals($expected, html_entity_decode($result));
    }

    public function testYoutubeFormatter()
    {
        $this->markdown->addLineFormatter(new YoutubeFormatter($this->markdown));

        $expected = '<p><div class="youtubeWrapper">' .
            '<iframe class="youtube" src="http://www.youtube.com/embed/id" frameborder="0" allowfullscreen>' .
            '</iframe></div></p>';

        $this->assertEquals($expected, $this->markdown->format('[youtube](id)'));
    }

    /**
     * @expectedException OutOfBoundsException
     */
    public function testThatAnExceptionIsThrownWhenHTMLBlockIsNotFound()
    {
        $this->markdown->getHtml('some key');
    }
}
