<?php


namespace Crell\JoindIn;

/**
 * Wrapper for Events response pages.
 */
class EventsResponse extends JoindInResponse
{
    /**
     * {@inheritdoc}
     */
    public function getType() {
        return 'events';
    }
}
