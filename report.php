<?php

require 'vendor/autoload.php';

use Crell\HtmlModel\Head\StyleElement;
use Crell\HtmlModel\HtmlPage;
use Crell\JoindIn\HtmlTable;

/**
 * Generates an HTML report of the data.
 */
function report()
{
    $page = (new HtmlPage())
      ->withTitle('Conference audit')
      ->withInlineStyle(new StyleElement(file_get_contents('styles.css')));

    // Generate the results page.
    $page = $page->withContent($page->getContent() . reportNewSpeakersPerCon());

    $page = $page->withContent($page->getContent() . reportTopSpeakers());

    file_put_contents('results.html', $page);
}

function reportTopSpeakers()
{
    $conn = getDb();

    $stmt = $conn->executeQuery("SELECT speaker, COUNT(speaker) AS appearances
        FROM talk
          WHERE speaker <> ''
        GROUP BY speaker
        HAVING appearances >= 20
        ORDER BY appearances DESC, speaker DESC");

    $header = ['Speaker', 'Appearances (since 2011)'];

    $table = new HtmlTable('Most frequent speakers');
    $table
      ->setRows($stmt->fetchAll())
      ->addHeader($header);

    return $table;
}


function reportNewSpeakersPerCon()
{
    $conn = getDb();

    $sql = "SELECT event.start_date, event.name, tz_continent, talks_count, num_speakers, new_speakers, FORMAT((new_speakers/event.num_speakers)*100, 1) AS percent_new
        FROM event
        WHERE start_date >= '2010-01-01'
        ORDER BY start_date";

    $stmt = $conn->executeQuery($sql);

    $rows = $stmt->fetchAll();

    $header = [
      'Date',
      'Event',
      'Region',
      'Total sessions',
      'Speakers',
      'New speakers',
      'Percent new'
    ];

    $table = new HtmlTable('First time speakers');
    $table
      ->setRows($rows)
      ->addHeader($header);

    $continent_stmt = $conn->executeQuery("SELECT DISTINCT tz_continent FROM event WHERE tz_continent <> '' ORDER BY tz_continent");

    foreach ($continent_stmt as $continent) {
        $averages = $conn->executeQuery(
          "SELECT 'N/A', 'Average', '{$continent['tz_continent']}',
             FORMAT(AVG(talks_count), 1), FORMAT(AVG(num_speakers), 1), FORMAT(AVG(new_speakers), 1), FORMAT(AVG(percent_new), 1)
             FROM (SELECT event.start_date, event.name, tz_continent, talks_count, num_speakers, new_speakers, FORMAT((new_speakers/event.num_speakers)*100, 1) AS percent_new
              FROM event
              WHERE start_date >= '2010-01-01'
                AND tz_continent = '{$continent['tz_continent']}'
              ORDER BY start_date) AS stuff")->fetch();
        $table->addFooter($averages);
    }


    return $table;
}

report();
