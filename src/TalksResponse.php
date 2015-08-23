<?php


namespace Crell\JoindIn;

/**
 * Wrapper for Talks response pages.
 */
class TalksResponse extends JoindInResponse
{
    public function getType() {
        return 'talks';
    }
}
