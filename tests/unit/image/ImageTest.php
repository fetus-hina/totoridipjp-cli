<?php
namespace tests\unit\image;

use jp3cki\totoridipjp\cli\Exception;
use jp3cki\totoridipjp\cli\image\Image;

class ImageTest extends \Codeception\Test\Unit
{
    const TEST_IMAGE_PATH = '../../_data/tea.jpg';

    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testConstructorLoad()
    {
        $o = new Image(file_get_contents(__DIR__ . '/' . static::TEST_IMAGE_PATH));
        $this->assertTrue(is_int($o->getWidth()));
        $this->assertTrue(is_int($o->getHeight()));
    }

    public function testConstructorBroken()
    {
        try {
            $o = new Image('hoge');
            $this->fail();
        } catch (Exception $e) {
        }
    }

    public function testResize()
    {
        $o = new Image(file_get_contents(__DIR__ . '/' . static::TEST_IMAGE_PATH));
        $a = $o->resize(96, 72);
        $b = $o->resize(48, 18);
        $this->assertTrue($a instanceof Image);
        $this->assertTrue($b instanceof Image);
        $this->assertTrue($o !== $a);
        $this->assertTrue($a !== $b);
        $this->assertEquals(96, $a->getWidth());
        $this->assertEquals(72, $a->getHeight());
        $this->assertEquals(48, $b->getWidth());
        $this->assertEquals(18, $b->getHeight());
    }

    public function testXterm256()
    {
        $o = new Image(file_get_contents(__DIR__ . '/' . static::TEST_IMAGE_PATH));
        $a = $o->resize(48, 36);
        $width = $a->getWidth();
        $height = $a->getHeight();
        $list = $a->xterm256();
        $this->assertTrue(is_array($list));
        $this->assertEquals($height, count($list));
        foreach ($list as $row) {
            for ($x = 0; $x < $width; ++$x) {
                $char = $row[$x];
                $this->assertTrue(is_string($char));
                $this->assertEquals(1, strlen($char));
                $ord = ord($char);
                $this->assertGreaterThanOrEqual(16, $ord);
                $this->assertLessThan(232, $ord);
            }
        }
    }
}
