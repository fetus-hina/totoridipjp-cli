<?php
namespace tests\unit\console;

use jp3cki\totoridipjp\cli\console\Settings;

class SettingsTest extends \Codeception\Test\Unit
{
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
    public function testGetScreenSize()
    {
        // 環境によって戻りが全然違うはずなので（最悪失敗する）
        // インタフェースがちゃんと呼び出せることを確認するだけ
        $o = new Settings();
        $o->getScreenSize();
    }

    public function testGetScreenSize2()
    {
        // 指定したテキストを stty の出力としてパースする
        $stty = implode("\n", [
            'speed 38400 baud; rows 64; columns 237; line = 0;',
            'intr = ^C; quit = ^\; erase = ^?; kill = ^U; eof = ^D; eol = <undef>; eol2 = <undef>; swtch = <undef>; start = ^Q; stop = ^S; susp = ^Z; rprnt = ^R; werase = ^W; lnext = ^V; flush = ^O; min = 1; time = 0;',
            '-parenb -parodd -cmspar cs8 -hupcl -cstopb cread -clocal -crtscts',
            '-ignbrk -brkint -ignpar -parmrk -inpck -istrip -inlcr -igncr icrnl ixon -ixoff -iuclc -ixany -imaxbel -iutf8',
            'opost -olcuc -ocrnl onlcr -onocr -onlret -ofill -ofdel nl0 cr0 tab0 bs0 vt0 ff0',
            'isig icanon iexten echo echoe echok -echonl -noflsh -xcase -tostop -echoprt echoctl echoke',
        ]);
        $o = new Settings();
        $ret = $o->getScreenSize($stty);
        $this->assertTrue(is_array($ret));
        $this->assertEquals(237, $ret[0]);
        $this->assertEquals(64, $ret[1]);
    }

    public function testGetScreenSize3()
    {
        // 指定したテキストを stty の出力としてパースする
        $stty = implode("\n", [
            'speed 38400 baud; rows 42; columns 80; line = 0;',
            'intr = ^C; quit = ^\; erase = ^?; kill = ^U; eof = ^D; eol = <undef>; eol2 = <undef>; swtch = <undef>; start = ^Q; stop = ^S; susp = ^Z; rprnt = ^R; werase = ^W; lnext = ^V; flush = ^O; min = 1; time = 0;',
            '-parenb -parodd -cmspar cs8 -hupcl -cstopb cread -clocal -crtscts',
            '-ignbrk -brkint -ignpar -parmrk -inpck -istrip -inlcr -igncr icrnl ixon -ixoff -iuclc -ixany -imaxbel -iutf8',
            'opost -olcuc -ocrnl onlcr -onocr -onlret -ofill -ofdel nl0 cr0 tab0 bs0 vt0 ff0',
            'isig icanon iexten echo echoe echok -echonl -noflsh -xcase -tostop -echoprt echoctl echoke',
        ]);
        $o = new Settings();
        $ret = $o->getScreenSize($stty);
        $this->assertTrue(is_array($ret));
        $this->assertEquals(80, $ret[0]);
        $this->assertEquals(42, $ret[1]);
    }

    public function testGetScreenSize4()
    {
        // 変な引数渡してみる
        $o = new Settings();
        $this->assertNull($o->getScreenSize(42));
    }

    public function testGetScreenSize5()
    {
        // 文字列は渡すがサイズはわからない
        $o = new Settings();
        $this->assertNull($o->getScreenSize('hoge'));
    }
}
