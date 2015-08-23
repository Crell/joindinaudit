<?php

namespace Crell\JoindIn;

use GuzzleHttp\Client;

require 'vendor/autoload.php';

function run() {
    loadEvents(getClient(), 'http://api.joind.in/v2.1/events?filter=past');

}

function loadEvents(Client $client, $url) {
    $response = $client->get($url);

    if ($response->getStatusCode() != 200) {
        return;
    }

    $events = new EventsResponse($response);

    foreach (new ConferenceFilter($events->getIterator()) as $event) {
        print_r($event);
    }
    if ($next = $events->nextPage()) {
        return loadEvents($client, $next);
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
