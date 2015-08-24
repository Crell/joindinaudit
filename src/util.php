<?php

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use GuzzleHttp\Client;

/**
 * Returns a database connection.
 *
 * @return \Doctrine\DBAL\Connection
 * @throws \Doctrine\DBAL\DBALException
 */
function getDb() {
    static $conn;

    if (empty($conn)) {
        $config = new Configuration();

        $connectionParams = array(
          'url' => 'mysql://test:test@localhost/joindin',
        );

        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = DriverManager::getConnection($connectionParams, $config);
    }

    return $conn;
}

/**
 * Returns a new Guzzle client, configured for JoindIn.
 *
 * @return \GuzzleHttp\Client
 */
function getClient() {
    static $client;

    if (empty($client)) {
        $client = new Client(['headers' => ['X-Foo' => 'Bar']]);
    }

    return $client;
}

/**
 * Produces an HTML table based on the provided values.
 *
 * @param $caption
 *   The table caption.
 * @param array $header
 *   A simple array to use for the header row of the table.
 * @param array $rows
 *   A 2D array of values to show in the table.
 * @param array $footer
 *   (Optional) A footer row to include at the bottom of the table.
 *
 * @return string
 *   A formatted HTML table.
 */
function makeHtmlTable($caption, array $header, array $rows, array $footer = [])
{
    $output = "<table>\n<caption>{$caption}</caption>\n";

    $output .= "<thead><tr>" . implode('', array_map(function($element) {
        return "<th>$element</th>";
    }, $header))
    . "</tr></thead>\n";

    $output .= "<tbody>" . implode('', array_map(function(array $row) {
          return "<tr>" . implode('', array_map(function($element) {
              return "<td>$element</td>";
          }, $row))
          . "</tr>\n";
    }, $rows))
    . "</tbody>\n";

    if ($footer) {
        $output .= "<tfoot><tr>" . implode('', array_map(function($element) {
              return "<td>$element</td>";
          }, $footer))
          . "</tr></tfoot>\n";
    }

    $output .= "</table>\n";

    return $output;
}

class HtmlTable {

    private $header;

    private $caption;

    private $rows;

    private $footer;

    public function __construct($caption, $header, $rows)
    {
        $this->caption = $caption;
        $this->header = $header;
        $this->rows = $rows;
    }

    public function setFooter($footer) {
        $this->footer = $footer;
    }

    public function __toString()
    {
        return makeHtmlTable($this->caption, $this->header, $this->rows, $this->footer);
    }
}
