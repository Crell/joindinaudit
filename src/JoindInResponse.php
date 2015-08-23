<?php


namespace Crell\JoindIn;

use Psr\Http\Message\ResponseInterface;

/**
 * Wrapper for paged responses from the JoindIn API.
 */
abstract class JoindInResponse implements \IteratorAggregate
{
    /**
     * The raw response that we returned.
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;

    /**
     * The data payload of the response.
     *
     * This will be coerced to a nested array, not array/stdClass mix.
     *
     * @var array
     */
    private $data;

    /**
     * Constructs a new JoinedInResponse.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *   The raw response that was returned.
     */
    public function __construct(ResponseInterface $response) {
        $this->response = $response;
        $body = $response->getBody()->getContents();
        $json = json_decode($body, true);

        if (!empty($json['meta'])) {
            $this->meta = $json['meta'];
        }

        $data = $json[$this->getType()];

        // Remove data we know we won't need, for memory reasons.
        // @todo Why is this not working?
        unset($data['description'], $data['tags'], $data['icon']);

        $this->data = $data;
    }

    /**
     * Returns the URI of the next page in the set.
     *
     * @return string
     *   The URI of the next page in the dataset, or empty string if there
     *   are no more pages.
     */
    public function nextPage() {
        return empty($this->meta['next_page']) ? '' : $this->meta['next_page'];
    }

    /**
     * Return sthe URI of the current page.
     *
     * @return string
     *   The URI of the current page in the dataset.
     */
    public function thisPage() {
        return empty($this->meta['this_page']) ? '' : $this->meta['this_page'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * The type key for this response object.
     *
     * @return string
     */
    abstract public function getType();

}
