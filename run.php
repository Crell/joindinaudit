<?php

namespace Crell\JoindIn;

use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

require 'vendor/autoload.php';

function run()
{
    $client = getClient();

    $eventPages = new \SplQueue();
    $eventPages->enqueue('http://api.joind.in/v2.1/events?filter=past');
    $pageProcessor = partial('Crell\JoindIn\processEventPage', $eventPages);

    $eventPageRequestGenerator = function (\SplQueue $pages) {
        foreach ($pages as $page) {
            yield new Request('GET', $page);
        }
    };

    $pool = new Pool($client, $eventPageRequestGenerator($eventPages), [
        // Since we'll in practice not have more than one item in the queue
        // at once, a higher concurrency would terminate early.
        'concurrency' => 1,
        'fulfilled' => $pageProcessor,
    ]);

    // Initiate the transfers and create a promise
    $promise = $pool->promise();

    // Force the pool of requests to complete.
    $promise->wait();

    //apply($eventPages, $pageProcessor);
}

function processEventPage(\SplQueue $pages, ResponseInterface $response, $index)
{
    $events = new EventsResponse($response);

    if ($next = $events->nextPage()) {
        $pages->enqueue($next);
    }

    apply(new ConferenceFilter($events->getIterator()), function($event) {
        print "Processing Event: {$event['name']}" . PHP_EOL;
        fetchTalksForEvent($event);
        addEventToDatabase($event);
    });

    print "Downloaded Events Page {$index}" . PHP_EOL;
}

function fetchTalksForEvent(array $event)
{
    $client = getClient();

    $talks_uri = isset($event['talks_uri']) ? $event['talks_uri'] : '';

    if (!$talks_uri) {
        return;
    }

    $addTalk = partial('Crell\JoindIn\addTalkToDatabase', $event);

    $client->getAsync($talks_uri)
        ->then(function(ResponseInterface $response) use ($addTalk) {
            $talks = new TalksResponse($response);
            apply($talks, $addTalk);
        });
}

function addTalkToDatabase(array $event, array $talk)
{
    print "Processing Talk: {$talk['talk_title']}" . PHP_EOL;
    $conn = getDb();

    $fields = ['url_friendly_talk_title', 'talk_title', 'type', 'duration', 'average_rating'];

    $insert = [];
    foreach ($fields as $field) {
        $insert[$field] = $talk[$field];
    }

    $insert['speaker'] = getSpeaker($talk);
    $insert['event'] = $event['url_friendly_name'];

    try {
        $conn->insert('talk', $insert);
    }
    catch (\Exception $e) {
        print $e->getMessage() . PHP_EOL;
        print_r($talk);
    }
}


function getSpeaker($talk)
{
    return isset($talk['speakers'][0]['speaker_name']) ? $talk['speakers'][0]['speaker_name'] : '';
}

function addEventToDatabase(array $event)
{
    $conn = getDb();

    $fields = ['url_friendly_name', 'name', 'start_date', 'end_date',
      'tz_continent', 'tz_place', 'location', 'talks_count'];

    $insert = [];
    foreach ($fields as $field) {
        $insert[$field] = $event[$field];
    }

    try {
        $conn->insert('event', $insert);
    }
    catch (\Exception $e) {
        print $e->getMessage() . PHP_EOL;
        print_r($event);
    }
}

run();
