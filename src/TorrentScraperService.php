<?php

namespace SergeySMoiseev\TorrentScraper;

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

    public function scrapTorrents($torrents = []){
        return [];
    }
//string 'magnet:?xt=urn:btih:3d90d8cf237306a49aa98afa7642d5a8688deb59&dn=Assassins+Creed+2016+HD-TS+ENG+SUB+x264-CPG&tr=udp%3A%2F%2Ftracker.leechers-paradise.org%3A6969&tr=udp%3A%2F%2Fzer0day.ch%3A1337&tr=udp%3A%2F%2Ftracker.coppersurfer.tk%3A6969&tr=udp%3A%2F%2Fpublic.popcorn-tracker.org%3A6969' (length=287)

}
