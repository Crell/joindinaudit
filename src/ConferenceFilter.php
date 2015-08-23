<?php


namespace Crell\JoindIn;


class ConferenceFilter extends \FilterIterator
{

    const CONFERENCE_MIN_TALKS = 5;

    /**
     * Only accept conferences, ie, events with sufficient talks.
     *
     * @return bool
     */
    public function accept()
    {
        $item = parent::current();
        return (isset($item['talks_count']) && $item['talks_count'] >= static::CONFERENCE_MIN_TALKS);
    }


}
