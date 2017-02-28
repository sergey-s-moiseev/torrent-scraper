<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use GuzzleHttp\Exception\ClientException;
use SergeySMoiseev\TorrentScraper\AdapterInterface;
use SergeySMoiseev\TorrentScraper\HttpClientAware;
use SergeySMoiseev\TorrentScraper\Entity\SearchResult;
use SergeySMoiseev\TorrentScraper\TorrentScraperService;
use Symfony\Component\DomCrawler\Crawler;

class EzTvAdapter implements AdapterInterface
{
    use HttpClientAware;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param $options array
     */
    public function __construct(array $options = [])
    {
        $defaults = ['seeders' => 1, 'leechers' => 1];

        $this->options = array_merge($defaults, $options);
    }

    /**
     * @param string $query
     * @return SearchResult[]
     */
    public function search($query)
    {
        try {
            $response = $this->httpClient->get('https://eztv.ag/search/' . $this->transformSearchString($query));
        } catch (ClientException $e) {
            return [];
        }
        $crawler = new Crawler((string) $response->getBody());
        $items = $crawler->filter('tr.forum_header_border');
        $results = [];
        foreach ($items as $item) {
            $result = new SearchResult();
            $itemCrawler = new Crawler($item);
            $save = true;

            //->critical
            $name = null;
            $magnet_url = null;
            $torrent_file = null;

            try {$name = trim($itemCrawler->filter('td')->eq(1)->filter('a.epinfo')->text());
            }catch (\Exception $e) {}
            try {$magnet_url = $itemCrawler->filter('td')->eq(2)->filter('a.magnet')->attr('href');
            }catch (\Exception $e) {}
            try {$torrent_file = $itemCrawler->filter('td')->eq(2)->filter('a.download_2')->attr('href');
            }catch (\Exception $e) {}
            if (is_null($name) || (is_null($magnet_url)  && is_null($torrent_file))){
                $save = false;
                continue;
            }

                //->non critical
            /**Size**/
            $size_str = 0;
            try{
                $size_str = trim($itemCrawler->filter('td')->eq(3)->text());
                $size_arr = explode(" ", $size_str);
                $size = floatval($size_arr[0]);
                $k_size = $size_arr[1];
                switch ($k_size) {
                    case 'KB':
                        $size = $size * 1 / 1024;
                        break;
                    case 'MB':
                        break;
                    case 'GB':
                        $size = $size * 1024;
                        break;
                    case 'TB':
                        $size = $size * 1024 * 1024;
                        break;
                }
            }catch (\Exception $e) {}

                /**Seeds**/
            $seeds = null;
            try {
                $seeds = trim($itemCrawler->filter('td')->eq(5)->children()->text());
                $vowels = array(",", ".", " ");
                $seeds = str_replace($vowels, "", $seeds);
            } catch (\Exception $e) {$seeds = 0;}

            $det_url = null;
            try {$det_url = 'https://eztv.ag' . $itemCrawler->filter('td')->eq(1)->filter('a.epinfo')->attr('href');
            }catch (\Exception $e) {}

//            $rat_url = 'https:s//eztv.ag'. $itemCrawler->filter('td')->eq(0)->children()->attr('href');
//            $result->setRating($this->getRating($rat_url));
            $result->setName($name);
            $result->setDetailsUrl($det_url);
            $result->setSeeders((int) $seeds);
            $result->setLeechers(0);
//            $result->setLeechers($this->getPeers($det_url));
            $result->setSource(TorrentScraperService::EZTV);
            $result->setMagnetUrl($magnet_url);
            $result->setSize($size);
            if ($save) $results[] = $result;
        }
        return $results;
    }

    /**
     * Transform every non alphanumeric character into a dash.
     *
     * @param string $searchString
     * @return mixed
     */
    public function transformSearchString($searchString)
    {
        return preg_replace('/[^a-z0-9]/', '-', strtolower($searchString));
    }

    private function getPeers($url) {
        try {
            $response = $this->httpClient->get($url);
        } catch (ClientException $e) {
            return [];
        }
        $crawler = new Crawler((string) $response->getBody());
        $item = $crawler->filter('td.episode_middle_column');
        $peers = trim($item->filter('span.stat_red')->text());

        return (int)$peers;

    }

    private function getRating($url) {
        try {
            $response = $this->httpClient->get($url);
        } catch (ClientException $e) {
            return [];
        }
        $crawler = new Crawler((string) $response->getBody());
        $item = $crawler->filter('#header_holder');
        $item = $item->filter('td.show_info_rating_score');
        $rating = $item->filter('span')->text();
        return (float)$rating;
    }
}
