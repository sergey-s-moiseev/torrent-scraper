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
            $result = $adapter->search($query);
            $results = array_merge($result, $results);
        }

        return $results;
    }


    private function sendToScript($data) {
        try {
            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'timeout' => 5
                ]
            ]);
            $response = $client->post('127.0.0.1:5000',
                ['body' => json_encode($data),
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

    /**
     * @param string $query
     * @param $callback
     * @param $key
     * @return string
     */
    public function scrap($query, $callback, $key)
    {
        return $this->sendToScript(
            [
                'data' => $query,
                'callback' => $callback,
                'private_key' => $key
            ]
        );
    }

    public function logs($from_date = null, $to_date = null)
    {
        return $this->sendToScript(
            [
                'data' => ['interval' => [$from_date, $to_date]],
                'callback' => null,
                'private_key' => null
            ]
        );
    }


    public function ping()
    {
        return $this->sendToScript(
            [
                'data' => 'ping',
                'callback' => null,
                'private_key' => null
            ]
        );
    }


    public function stop()
    {
        return $this->sendToScript(
            [
                'data' => 'stop',
                'callback' => null,
                'private_key' => null
            ]
        );
    }
}
