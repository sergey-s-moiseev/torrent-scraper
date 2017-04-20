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
    const TORRENTZ2 = 'torrentz2';
    const EXTRATORRENT = 'extratorrent';
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
            $adapter->setHttpClient(new \GuzzleHttp\Client([
                'headers'=>[
//                    "Cache-Control" => "no-cache, no-store, must-revalidate",
//                    "Pragma" => "no-cache",
//                    "Expires" => "0"
                    'Accept' => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                    'Accept-Encoding' => "gzip, deflate, sdch",
                    'Accept-Language' => "ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4",
                    'Cache-Control' => "no-cache",
                    'Connection' => "keep-alive",
                    'DNT' => '1',
                    'Host' => 'kickasstorrents.to',
                    'Pragma' => 'no-cache',
                    'Upgrade-Insecure-Requests' => '1',
                    'User-Agent' => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36"
                ]
            ]));
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
