<?php

namespace Crell\JoindIn;

use GuzzleHttp\Client;

require 'vendor/autoload.php';

function run() {
    $client = getClient();

    $eventPages = new \SplQueue();
    $eventPages->enqueue('http://api.joind.in/v2.1/events?filter=past');
    $pageProcessor = partial('Crell\JoindIn\processEventPage', $client, $eventPages);

    apply($eventPages, $pageProcessor);
}



function processEventPage(Client $client, \SplQueue $pages, $page) {
    $response = $client->get($page);
    if ($response->getStatusCode() !== 200) {
        return;
    }
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
