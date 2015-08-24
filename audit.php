<?php

require 'vendor/autoload.php';

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Connection;

/**
 * Initializes the database with a fresh, empty schema.
 */
function audit()
{
    $conn = getDb();


    //ensureFirstAppearanceTable();
    //makeFirstAppearanceIndex();

}

function ensureFirstAppearanceTable()
{
    $conn = getDb();

    /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager $sm */
    $sm = $conn->getSchemaManager();

    if ($sm->tablesExist('first_appearance')) {
        $sm->dropTable('first_appearance');
    }

    $schema = new Schema();
    $table = $schema->createTable('first_appearance');
    $table->addColumn("speaker", "string", ["length" => 128]);
    $table->addColumn("event", "string", ["length" => 64]);
    $table->addColumn("event_date", "date");
    $table->addColumn("event_name", "string", ["length" => 128]);
    $table->setPrimaryKey(["speaker"]);
    $table->addForeignKeyConstraint('event', ['event'], ['url_friendly_name']);
    $sm->dropAndCreateTable($table);
}

function makeFirstAppearanceIndex()
{
    $conn = getDb();

    $conn->transactional(function(Connection $conn) {
        $result = $conn->executeQuery("SELECT DISTINCT speaker FROM talk");

        $sql = "INSERT INTO first_appearance
                SELECT talk.speaker as speaker, event.url_friendly_name as event, event.start_date as event_date, event.name as event_name
                FROM event
                  INNER JOIN talk ON event.url_friendly_name = talk.event
                WHERE talk.speaker = :name
                ORDER BY event.start_date
                LIMIT 1";
        $stmt = $conn->prepare($sql);

        foreach ($result as $record) {
            $stmt->execute([
                'name' => $record['speaker']
            ]);
        }
    });

}

audit();
