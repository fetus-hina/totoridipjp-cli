<?php
namespace jp3cki\totoridipjp\cli;

use Curl\Curl;
use jp3cki\totoridipjp\Totori;

class Application
{
    public function run()
    {
        $options = getopt('hv', [
            'help',
            'version',
            'out-url',
            'out-xterm256',
            'width:',
            'zenkaku',
        ]);
        if (isset($options['h']) || isset($options['help'])) {
            $this->displayHelp();
            exit(0);
        }
        if (isset($options['v']) || isset($options['version'])) {
            $this->displayVersion();
            exit(0);
        }
        
        // url, xterm256 の出力オプションがなければ xterm256 を指定されたものとみなす
        if (!isset($options['out-url']) && !isset($options['out-xterm256'])) {
            $options['out-xterm256'] = false;
        }

        $url = $this->getIwashi();
        if (isset($options['out-url'])) {
            $this->outputUrl($url);
        }
        if (isset($options['out-xterm256'])) {
            $width = null;
            if (isset($options['width'])) {
                $width = $options['width'];
                if (!preg_match('/^\d+$/', $width)) {
                    fwrite(STDERR, "The option 'width' must be integer.\n");
                    exit(2);
                }
                if ($width < 1) {
                    fwrite(STDERR, "The option 'width' must be greater than 0.\n");
                    exit(2);
                }
                if ($width > 1000) {
                    fwrite(STDERR, "The option 'width' must be less than 1000.\n");
                    exit(2);
                }
            }

            $image = $this->downloadIwashi($url);
            list($width, $height) = $this->calcImageSize(
                $image->getWidth(),
                $image->getHeight(),
                $width,
                isset($options['zenkaku'])
            );
            $this->outputXterm256($image, $width, $height, str_repeat(' ', isset($options['zenkaku']) ? 2 : 1));
        }
        exit(0);
    }

    protected function getIwashi()
    {
        return 'http://totoridipjp-cdn.c.sakurastorage.jp/imgs/totori_vita.jpg';

        try {
            $totori = new Totori();
            return $totori->getIwashi();
        } catch (\Exception $e) {
            fwrite(STDERR, "Could not get iwashi.\n");
            exit(1);
        }
    }

    protected function downloadIwashi($url)
    {
        try {
            $curl = new Curl();
            $curl->get($url);
            if ($curl->error) {
                throw new \Exception($curl->errorMessage, $curl->errorCode);
            }
            $image = new image\Image($curl->rawResponse);
            if ($image->getWidth() < 1 || $image->getHeight() < 1) {
                throw new \Exception('Rotten iwashi');
            }
            return $image;
        } catch (\Exception $e) {
            fwrite(STDERR, "Could not download iwashi.\n");
            if ($e->getMessage() != '') {
                fwrite(STDERR, "(" . $e->getMessage() . ")\n");
            }
            exit(1);
        }
    }
    
    protected function calcImageSize($imgWidth, $imgHeight, $reqHalfWidth, $reqIsFullWidth)
    {
        if ($reqHalfWidth) {
            if ($reqIsFullWidth) {
                $width = (int)floor($reqHalfWidth / 2);
                $height = (int)round($imgHeight * $width / $imgWidth);
            } else {
                $width = (int)$reqHalfWidth;
                $height = (int)round($imgHeight * $width * 0.5 / $imgWidth);
            }
            return [
                max(1, $width),
                max(1, $height),
            ];
        }

        try {
            $settings = new console\Settings();
            $size = $settings->getScreenSize();
        } catch (\Exception $e) {
            $size = null;
        }
        if (!$size) {
            $reqHalfWidth = 80;
            $reqHeight = \PHP_INT_MAX;
        } else {
            $reqHalfWidth = max(1, $size[0] - 1);
            $reqHeight = max(1, $size[1] - 1);
        }
        //fprintf(STDERR, "ターゲット: %d x %d\n", $reqHalfWidth, $reqHeight);

        // とりあえず幅基準で高さを計算する
        if ($reqIsFullWidth) {
            $width = (int)floor($reqHalfWidth / 2);
            $height = (int)round($imgHeight * $width / $imgWidth);
        } else {
            $width = (int)$reqHalfWidth;
            $height = (int)round($imgHeight * $width * 0.5 / $imgWidth);
        }
        //fprintf(STDERR, "幅基準: %d x %d\n", $width, $height);

        // 高さが端末からはみ出すなら高さ基準で幅を計算する
        if ($height > $reqHeight) {
            $height = $reqHeight;
            if ($reqIsFullWidth) {
                $width = (int)round($imgWidth * $height / $imgHeight);
            } else {
                $width = (int)round($imgWidth * 2 * $height / $imgHeight);
            }
            //fprintf(STDERR, "高さ基準: %d x %d\n", $width, $height);
        }

        return [
            max(1, $width),
            max(1, $height),
        ];
    }

    protected function displayHelp()
    {
    }

    protected function displayVersion()
    {
        $list = [
            " totori.dip.jp",
            "===============",
            "",
            "  - jp3cki/totoridipjp     version " . \jp3cki\totoridipjp\Version::VERSION,
            "  - jp3cki/totoridipjp-cli version " . \jp3cki\totoridipjp\cli\Version::VERSION,
            "",
        ];
        fwrite(STDERR, implode("\n", $list) . "\n");
    }

    public function outputUrl($url)
    {
        echo "{$url}\n";
    }

    public function outputXterm256($image, $width, $height, $pixel) {
        $xt256 = $image->resize($width, $height)->xterm256();
        foreach ($xt256 as $line) {
            for ($x = 0; $x < $width; ++$x) {
                $ord = ord($line[$x]);
                echo "\033[48;5;${ord}m${pixel}";
            }
            echo "\033[0m\n";
        }
    }
}
