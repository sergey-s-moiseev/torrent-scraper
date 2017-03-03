<?php

namespace SergeySMoiseev\TorrentScraper;

use GuzzleHttp\Exception\RequestException;
use SergeySMoiseev\TorrentScraper\Entity\SearchResult;

class TorrentScraperService
{
    /**
     * @var AdapterInterface[]
     */
    protected $adapters;

    const EZTV = 'ezTv';
    const KICKASS = 'kickassTorrents';
    const THEPIRATEBAY = 'thePirateBay';

    /**
     * @param array $adapters
     * @param array $options
     */
    public function __construct(array $adapters = [], $options = [])
    {
        foreach ($adapters as $adapter) {
            $adapterName = __NAMESPACE__ . '\\Adapter\\' . ucfirst($adapter) . 'Adapter';
            $this->addAdapter(new $adapterName($options));
        }
    }

    /**
     * @param AdapterInterface $adapter
     */
    public function addAdapter(AdapterInterface $adapter)
    {
        if (!$adapter->getHttpClient())
        {
            $adapter->setHttpClient(new \GuzzleHttp\Client());
        }

        $this->adapters[] = $adapter;
    }

    /**
     * @return AdapterInterface[]
     */
    public function getAdapters()
    {
        return $this->adapters;
    }

    /**
     * @param string $query
     * @return SearchResult[]
     */
    public function search($query)
    {
        $results = [];

        foreach ($this->adapters as $adapter) {
            $results = array_merge($adapter->search($query), $results);
        }

        return $results;
    }

    /**
     * @param string $query
     * @param $callback
     * @param $key
     */
    public function scrap($query, $callback, $key)
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [ 'Content-Type' => 'application/json' ]
        ]);

        $response = $client->post('127.0.0.1:5000',
            ['body' => json_encode(
                [
                    'data' => $query,
                    'callback' => $callback,
                    'private_key' => $key
                ]
            )]
        );
        return $response->getBody()->getContents();
    }


    public function ping()
    {

        try {
            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'timeout' => 5
                ]
            ]);
            $response = $client->post('127.0.0.1:5000',
                ['body' => json_encode(
                        [
                            'data' => 'ping',
                            'callback' => null,
                            'private_key' => null
                        ]
                    ),
                    'timeout'         => 5,
                    'connect_timeout' => 5
                ]

            );
            return $response->getBody()->getContents();
        } catch(RequestException $e) {
            $message = $e->getMessage();
            return $message;
        }
    }
}
