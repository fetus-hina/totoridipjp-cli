<?php
namespace jp3cki\totoridipjp\cli\image;

use jp3cki\totoridipjp\cli\Exception;

class Image
{
    private $gd;

    public function __construct($binary)
    {
        if ($binary === null) {
            return;
        }
        $this->gd = @\imagecreatefromstring($binary);
        if (!$this->gd) {
            throw new Exception('Unsupported binary given');
        }
    }

    public function __destruct()
    {
        if ($this->gd) {
            imagedestroy($this->gd);
            $this->gd = null;
        }
    }

    public function getWidth() // : int
    {
        return \imagesx($this->gd);
    }

    public function getHeight() // : int
    {
        return \imagesy($this->gd);
    }

    public function resize($width, $height) // : self (immutable)
    {
        $newGd = \imagecreatetruecolor($width, $height);
        \imagesavealpha($newGd, true);
        \imagealphablending($newGd, false);
        \imagecopyresampled(
            $newGd,
            $this->gd,
            0, // dst X
            0, // dst Y
            0, // src X
            0, // src Y
            $width,
            $height,
            $this->getWidth(),
            $this->getHeight()
        );
        $ret = new self(null);
        $ret->gd = $newGd;
        return $ret;
    }

    public function xterm256() // : string[]
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        $ret = [];
        $gosaTemplate = \array_fill(0, $width, [0, 0, 0]);
        $nextGosa = $gosaTemplate;
        $direction = 1;
        for ($y = 0; $y < $height; ++$y) {
            $line = str_repeat(chr(0), $width);
            $gosa = $nextGosa;
            $nextGosa = $gosaTemplate;
            for ($x = ($direction == 1 ? 0 : $width - 1); (0 <= $x && $x < $width); $x += $direction) {
                $color = \imagecolorat($this->gd, $x, $y);
                $r256 = ($color & 0xff0000) >> 16;
                $g256 = ($color & 0x00ff00) >>  8;
                $b256 = ($color & 0x0000ff) >>  0;
                list($index, $gosaR, $gosaG, $gosaB) = static::getXterm256Index(
                    min(255, max(0, round($r256 + $gosa[$x][0]))),
                    min(255, max(0, round($g256 + $gosa[$x][1]))),
                    min(255, max(0, round($b256 + $gosa[$x][2])))
                );
                $line[$x] = chr($index);

                // 誤差を隣のピクセルにおしつけ
                $x2 = $x + $direction;
                if (0 <= $x2 && $x2 < $width) {
                    $gosa[$x2][0] += $gosaR * 7 / 16;
                    $gosa[$x2][1] += $gosaG * 7 / 16;
                    $gosa[$x2][2] += $gosaB * 7 / 16;

                    // 斜め下のピクセルにおしつけ
                    $nextGosa[$x2][0] += $gosaR * 1 / 16;
                    $nextGosa[$x2][1] += $gosaG * 1 / 16;
                    $nextGosa[$x2][2] += $gosaB * 1 / 16;
                }

                // 真下のピクセルにおしつけ
                $nextGosa[$x][0] += $gosaR * 5 / 16;
                $nextGosa[$x][1] += $gosaG * 5 / 16;
                $nextGosa[$x][2] += $gosaB * 5 / 16;

                // 斜め下（反対側）のピクセルにおしつけ
                $x2 = $x - $direction;
                if (0 <= $x2 && $x2 < $width) {
                    $nextGosa[$x2][0] += $gosaR * 3 / 16;
                    $nextGosa[$x2][1] += $gosaG * 3 / 16;
                    $nextGosa[$x2][2] += $gosaB * 3 / 16;
                }
            }
            $ret[] = $line;
            $direction *= -1;
        }
        return $ret;
    }

    protected static function getXterm256Index($r, $g, $b)
    {
        static $table = null;
        if ($table === null) {
            $table = static::getXterm256IndexTable();
        }
        list($rI, $rG) = $table[$r];
        list($gI, $gG) = $table[$g];
        list($bI, $bG) = $table[$b];
        return [
            16 + $rI * 36 + $gI * 6 + $bI,
            $rG,
            $gG,
            $bG
        ];
    }

    private static function getXterm256IndexTable()
    {
        $ret = [];
        for ($c = 0; $c <= 255; ++$c) {
            if ($c <= 0x5f / 2) {
                $ret[] = [0, $c - 0x00];
            } elseif ($c <= (0x87 + 0x5f) / 2) {
                $ret[] = [1, $c - 0x5f];
            } elseif ($c <= (0xaf + 0x87) / 2) {
                $ret[] = [2, $c - 0x87];
            } elseif ($c <= (0xd7 + 0xaf) / 2) {
                $ret[] = [3, $c - 0xaf];
            } elseif ($c <= (0xff + 0xd7) / 2) {
                $ret[] = [4, $c - 0xd7];
            } else {
                $ret[] = [5, $c - 0xff];
            }
        }
        return $ret;
    }
}
