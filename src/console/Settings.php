<?php
namespace jp3cki\totoridipjp\cli\console;

class Settings
{
    public function getScreenSize($stty = null)
    {
        if ($stty === null) {
            // @codeCoverageIgnoreStart
            $stty = $this->execStty();
            // @codeCoverageIgnoreEnd
        }

        if (!is_string($stty)) {
            return null;
        }
        $rows = null;
        $cols = null;
        $lines = preg_split('/\x0d\x0a|\x0d|\x0a/', $stty);
        foreach ($lines as $line) {
            if (preg_match('/\brows\s+(\d+)/', $line, $match)) {
                $rows = (int)$match[1];
            }
            if (preg_match('/\bcol(?:umns)?\s+(\d+)/', $line, $match)) {
                $cols = (int)$match[1];
            }
            if ($rows !== null && $cols !== null) {
                return [$cols, $rows];
            }
        }
        return null;
    }

    // @codeCoverageIgnoreStart
    private function execStty()
    {
        @exec('stty -a 2>/dev/null', $lines, $status);
        if ($status !== 0) {
            return null;
        }
        return implode("\n", $lines);
    }
    // @codeCoverageIgnoreEnd
}
