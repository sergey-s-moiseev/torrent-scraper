<?php

namespace SergeySMoiseev\TorrentScraper;

use SergeySMoiseev\TorrentScraper\Entity\SearchResult;
use Transmission\Transmission;


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
            $transmission = new Transmission();
            try {
                $torrent_queue = $transmission->all();
            } catch (\Exception $e) {
                var_dump('Could not connect to Transmission');
                exit;
            }
            if (empty($torrent_queue)) {
                foreach ($torrents as $key => $_torrent) {
                    $transmission->add($_torrent['magnet']);
                }
                var_dump($_torrent['magnet']);
                var_dump($key + 1 . ' torrent added');

                $torrents = null;
                while (empty($torrents)) {
                    $torrents = $transmission->all();
                }
                foreach ($torrents as $_torrent) {
                    $transmission->start($_torrent, true);
                }
            }
            else
                $torrentStats = $this->getTorrentStats();
            exit;
            return($torrentStats);

    }
//string 'magnet:?xt=urn:btih:3d90d8cf237306a49aa98afa7642d5a8688deb59&dn=Assassins+Creed+2016+HD-TS+ENG+SUB+x264-CPG&tr=udp%3A%2F%2Ftracker.leechers-paradise.org%3A6969&tr=udp%3A%2F%2Fzer0day.ch%3A1337&tr=udp%3A%2F%2Ftracker.coppersurfer.tk%3A6969&tr=udp%3A%2F%2Fpublic.popcorn-tracker.org%3A6969' (length=287)

    private function getTorrentStats(){
        $transmission = new Transmission();
        try {
            $torrents = $transmission->all();
        } catch (\Exception $e) {
            var_dump('Could not connect to Transmission');
            exit;
        }
        $torrents_removed = 0;
        foreach ($torrents as $_torrent) {
            $transmission->start($_torrent, true);
//            $transmission->remove($_torrent);

//            $_result = false;
            $stats = $_torrent->getTrackerStats();
            foreach ($stats as $_stat) {
                if ($_stat->getLastAnnounceResult() == 'Success') {
//                    $_result = true;
                    var_dump($_torrent->getTrackerStats());
                    $transmission->remove($_torrent, true);
                    $torrents_removed++;
                    $seeds = $_stat->getSeederCount();
                    $leechers = $_stat->getLeecherCount();
                    $torrent_hash = $_torrent->getHash();
                    $torrents_stats = ['hash' => $torrent_hash, 'seeds'=>$seeds, 'leechers' =>$leechers];
                    var_dump($torrents_stats);
                    break;

                }
            }


        }
        var_dump($torrents_removed.' torrents removed');

        exit;
        return ['hash'=> 'seeds', $seeds,'peers' => ($leechers+$seeds)];
    }

}
