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

    /**
     * @var string
     */
    protected $scriptAddress = '127.0.0.1:5000';

    /**
     * @param array $adapters
     * @param array $options
     */
    public function __construct(array $adapters = [], $options = [])
    {
        foreach ($adapters as $adapterName => $adapter) {
            $adapterName = __NAMESPACE__ . '\\Adapter\\' . ucfirst($adapter) . 'Adapter';
            $this->addAdapter($adapterName, new $adapterName($options));
        }
    }

    /**
     * @param string $adapterName
     * @param AdapterInterface $adapter
     */
    public function addAdapter($adapterName, AdapterInterface $adapter)
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

        $this->adapters[$adapterName] = $adapter;
    }

    /**
     * @return AdapterInterface[]
     */
    public function getAdapters()
    {
        return $this->adapters;
    }

    /**
     * @return string[]
     */
    public function getAdapterLabels()
    {
        return array_map(
            function(AdapterInterface $adapter){
                return $adapter->getLabel();
            },
            $this->adapters
        );
    }

    /**
     * @param string $adapterName
     * @return string
     */
    public function getAdapterLabel($adapterName)
    {
        if(!array_key_exists($adapterName, $this->adapters)) {
            return null;
        }
        return $this->adapters[$adapterName]->getLabel();
    }

    /**
     * @param string $adapterName
     * @return string
     */
    public function getAdapterUrl($adapterName)
    {
        if(!array_key_exists($adapterName, $this->adapters)) {
            return null;
        }
        return $this->adapters[$adapterName]->getUrl();
    }

    /**
     * @param string $query
     * @return SearchResult[]
     */
    public function search($query)
    {
        $results = [];

        foreach ($this->adapters as $adapter) {
          echo($adapter->getUrl());
          $result = $adapter->search($query);
          $results = array_merge($result, $results);
        }

        return $results;
    }

    /**
     * Set script default address
     * @var string $address
     * @return self
     */
    public function setScriptAddress($address)
    {
        $this->scriptAddress = $address;
        return $this;
    }

    /**
     * Get script default address
     * @return string
     */
    public function getScriptAddress()
    {
        return $this->scriptAddress;
    }

    private function sendToScript($data, $scriptAddress = null) 
    {
        if(null === $scriptAddress) {
            $scriptAddress = $this->getScriptAddress();
        }
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'timeout' => 0.5
            ]
        ]);
        $response = $client->post(
            $scriptAddress,
            [
                'body' => json_encode($data),
                'timeout'         => 0.5,
                'connect_timeout' => 0.5
            ]

        );
        return $response->getBody()->getContents();
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

    public function logs($from_date = null, $to_date = null, $scriptAddress = null)
    {
        return $this->sendToScript(
            [
                'data' => ['interval' => [$from_date, $to_date]]
            ],
            $scriptAddress
        );
    }


    public function ping($scriptAddress = null)
    {
        return $this->sendToScript(
            [
                'data' => 'ping'
            ],
            $scriptAddress
        );
    }


    public function stop($scriptAddress = null)
    {
        return $this->sendToScript(
            [
                'data' => 'stop'
            ],
            $scriptAddress
        );
    }
}
