<?php


namespace Crell\JoindIn;

use Psr\Http\Message\ResponseInterface;


abstract class JoindInResponse implements \IteratorAggregate
{

    /**
     *
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;

    /**
     *
     *
     * @var array
     */
    private $data;

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

    public function nextPage() {
        return empty($this->meta['next_page']) ? '' : $this->meta['next_page'];
    }

    public function thisPage() {
        return empty($this->meta['this_page']) ? '' : $this->meta['this_page'];
    }

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
