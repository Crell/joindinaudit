<?php

namespace Crell\JoindIn;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

require 'vendor/autoload.php';

function run() {
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

function processEventPage(\SplQueue $pages, ResponseInterface $response) {
    $events = new EventsResponse($response);

    if ($next = $events->nextPage()) {
        $pages->enqueue($next);
    }

    apply(new ConferenceFilter($events->getIterator()), function($event) {
        print_r($event);



        addEventToDatabase($event);

        //fetchTalksForEvent($event);
    });

}


function addEventToDatabase(array $event) {

}


function getClient() {
    static $client;

    if (empty($client)) {
        $client = new Client(['headers' => ['X-Foo' => 'Bar']]);
    }

    return $client;
}

run();
