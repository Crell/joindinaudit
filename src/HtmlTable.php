<?php


namespace Crell\JoindIn;

/**
 * Builder class for an HTML table.
 */
class HtmlTable {

    private $header = [];

    private $caption = [];

    private $rows;

    private $footer = [];

    public function __construct($caption, $header = [], $rows = [])
    {
        $this->caption = $caption;
        if ($header) {
            $this->header[] = $header;
        }
        $this->rows = $rows;
    }

    public function addFooter(array $footer) {
        $this->footer[] = $footer;
        return $this;
    }

    public function addHeader(array $header)
    {
        $this->header[] = $header;
        return $this;
    }

    public function setCaption($caption)
    {
        $this->caption = $caption;
        return $this;
    }

    public function setRows($rows)
    {
        $this->rows = $rows;
        return $this;
    }

    public function __toString()
    {
        return "<table>\n<caption>{$this->caption}</caption>\n"
        . $this->makeTableSection('thead', $this->header, 'th')
        . $this->makeTableSection('tbody', $this->rows)
        . $this->makeTableSection('tfoot', $this->footer)
        . "</table>\n";
    }

    protected function makeTableSection($section, array $rows, $cell = 'td')
    {
        assert('in_array($section, ["thead", "tbody", "tfoot"])');
        assert('in_array($cell, ["td", "th"])');

        if (empty($rows)) {
            return '';
        }

        $tableCell = function($value) use ($cell) {
            return "<{$cell}>$value</{$cell}>";
        };

        $tableRow = function(array $row) use ($section, $tableCell) {
            return "<tr>" . implode('', array_map($tableCell, $row)) . "</tr>" . PHP_EOL;
        };

        $output = "<{$section}>" . implode('', array_map($tableRow, $rows)) . "</{$section}>";

        return $output;
    }
}
