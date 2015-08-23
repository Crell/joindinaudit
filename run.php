<?php

namespace Crell\JoindIn;

use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

require 'vendor/autoload.php';

/**
 * Executes the downloader.
 */
function run()
{
    fetchPages('http://api.joind.in/v2.1/events?filter=past', 'Crell\JoindIn\processEventPage');

    return;
}

/**
 * Fetch and process a paged response from JoindIn.
 *
 * @param $initial
 *   The initial URL to fetch. That URL may result in others getting
 *   fetched as well.
 * @param callable $processor
 *   A callable that will be used to process each page of results. Its signature
 *   must be: (\SplQueue $pages, ResponseInterface $response, $index)
 * @param int $concurrency
 *   The number of concurrent requests to send. In practice this kinda has to
 *   be 1, or else Guzzle will conclude itself before getting to later-added
 *   entries.
 */
function fetchPages($initial, callable $processor, $concurrency = 1)
{
    $client = getClient();

    $pages = new \SplQueue();
    $pages->enqueue($initial);
    $pageProcessor = partial($processor, $pages);

    $pageRequestGenerator = function (\SplQueue $pages) {
        foreach ($pages as $page) {
            yield new Request('GET', $page);
        }
    };

    $pool = new Pool($client, $pageRequestGenerator($pages), [
        // Since we'll in practice not have more than one item in the queue
        // at once, a higher concurrency would terminate early.
      'concurrency' => $concurrency,
      'fulfilled' => $pageProcessor,
    ]);

    // Initiate the transfers and create a promise
    $promise = $pool->promise();

    // Force the pool of requests to complete.
    $promise->wait();
}

/**
 * Processes a single event page from the JoindIn API.
 *
 * @param \SplQueue $pages
 *   A queue of pages in the current processing set.
 * @param \Psr\Http\Message\ResponseInterface $response
 *   The response for a single page.
 * @param int $index
 *   The index of the page being processed, 0-based.
 */
function processEventPage(\SplQueue $pages, ResponseInterface $response, $index)
{
    $events = new EventsResponse($response);

    if ($next = $events->nextPage()) {
        $pages->enqueue($next);
    }

    apply(new ConferenceFilter($events->getIterator()), function($event) {
        print "Processing Event: {$event['name']}" . PHP_EOL;
        addEventToDatabase($event);
        fetchTalksForEvent($event);
    });

    print "Downloaded Events Page {$index}" . PHP_EOL;
}

/**
 * Processes a single talk page from the JoindIn API.
 *
 * Because this processor requires the event as the first parameter, it must
 * be partially applied before passed to fetchPages().
 *
 * @param array $event
 *   The event record these talk pages are associated with.
 * @param \SplQueue $pages
 *   A queue of pages in the current processing set.
 * @param \Psr\Http\Message\ResponseInterface $response
 *   The response for a single page.
 * @param int $index
 *   The index of the page being processed, 0-based.
 */
function processTalkPage(array $event, \SplQueue $pages, ResponseInterface $response, $index)
{
    $talks = new TalksResponse($response);

    if ($next = $talks->nextPage()) {
        $pages->enqueue($next);
    }

    apply($talks, function($talk) use ($event) {
        print "Processing Talk: {$talk['talk_title']}" . PHP_EOL;
        addTalkToDatabase($event, $talk);
    });

    print "Downloaded Talk Page {$index}" . PHP_EOL;
}

/**
 * Downloads and processes all talks for a given event.
 *
 * @param array $event
 *   The event record for which we want to download talks.
 */
function fetchTalksForEvent(array $event)
{
    $talks_uri = isset($event['talks_uri']) ? $event['talks_uri'] : '';

    if (!$talks_uri) {
        return;
    }

    $processor = partial('\Crell\JoindIn\processTalkPage', $event);

    fetchPages($talks_uri, $processor, 1);
}

/**
 * Saves a talk to the database.
 *
 * @param array $event
 *   The event record the talk is associated with.
 * @param array $talk
 *   The talk record to save.
 */
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

/**
 * Returns the speaker for the specified talk.
 *
 * Note that talks may have multiple speakers, especially Workshops, but we're
 * ignoring that and only returning the first speaker listed, for simplicity.
 *
 * @param array $talk
 *   The talk record for which we want the speaker.
 *
 * @return string
 */
function getSpeaker(array $talk)
{
    return isset($talk['speakers'][0]['speaker_name']) ? $talk['speakers'][0]['speaker_name'] : '';
}

/**
 * Saves an event to the database.
 *
 * @param array $event
 *   The event record to save.
 */
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
