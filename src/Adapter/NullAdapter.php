<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use SergeySMoiseev\TorrentScraper\AdapterInterface;

class NullAdapter implements AdapterInterface
{
    public function __construct(array $options = [])
    {
    }

    public function setHttpClient(\GuzzleHttp\Client $httpClient)
    {
    }

    public function getHttpClient()
    {
    }

    public function search($query)
    {
       return [];
    }
}
