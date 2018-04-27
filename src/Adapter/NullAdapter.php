<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use SergeySMoiseev\TorrentScraper\AdapterInterface;

class NullAdapter implements AdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function __construct(array $options = [])
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return 'null';
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function setHttpClient(\GuzzleHttp\Client $httpClient)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getHttpClient()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function search($query)
    {
       return [];
    }
}
