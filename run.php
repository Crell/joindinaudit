<?php

namespace Crell\JoindIn;

use GuzzleHttp\Client;

require 'vendor/autoload.php';

function run() {
    $client = getClient();

    $eventPages = new \SplQueue();
    $eventPages->enqueue('http://api.joind.in/v2.1/events?filter=past');

    foreach ($eventPages as $page) {
        $response = $client->get($page);
        if ($response->getStatusCode() !== 200) {
            continue;
        }
        $events = new EventsResponse($response);
        foreach (new ConferenceFilter($events->getIterator()) as $event) {
            print_r($event);
        }
        if ($next = $events->nextPage()) {
            $eventPages->enqueue($next);
        }
    }
}


function getClient() {
    static $client;

    if (empty($client)) {
        $client = new Client(['headers' => ['X-Foo' => 'Bar']]);
    }

    return $client;
}

run();
