<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use GuzzleHttp\Exception\ClientException;
use SergeySMoiseev\TorrentScraper\AdapterInterface;
use SergeySMoiseev\TorrentScraper\HttpClientAware;
use SergeySMoiseev\TorrentScraper\Entity\SearchResult;
use SergeySMoiseev\TorrentScraper\TorrentScraperService;
use Symfony\Component\DomCrawler\Crawler;
use DateTime;
use DateInterval;

class ThePirateBayAdapter implements AdapterInterface
{
    use HttpClientAware;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {

    }

    /**
     * @param string $query
     * @return SearchResult[]
     */
    public function search($query)
    {
        try {
            $response = $this->httpClient->get('https://thepiratebay.se/search/' . urlencode($query) . '/0/7/0');
        } catch (ClientException $e) {
            return [];
        }
        
        $crawler = new Crawler((string) $response->getBody());
        $items = $crawler->filter('#searchResult tr');
        $results = [];
        $first = true;

        foreach ($items as $item) {
            // Ignore the first row, the header
            if ($first) {
                $first = false;
                continue;
            }

            $result = new SearchResult();
            $itemCrawler = new Crawler($item);
            $desc = trim($itemCrawler->filter('.detDesc')->text());
            $now = new DateTime();
            preg_match("/(\d{2})-(\d{2})|Today|Y-day/", $desc, $date_str);
            $year = (preg_match("/(\d{4})[^\.]/", $desc, $year)) ? $year[1] : $now->format('Y');
            preg_match("/MiB|GiB|TiB|KiB/", $desc, $k_size);
            preg_match("/Size\s(\d{1,}(\.\d{1,})?)/", $desc, $size);
            /**@var $date [0] DateTime**/
            if ($date_str[0] == 'Today') {
                $date = new DateTime('now');
            }
            elseif ($date_str[0] == 'Y-day') {
                $date = new DateTime('now');
                $date->sub(new DateInterval('P1D'));
            } else {
                $date = new DateTime();
                $date->setDate($year, $date_str[1], $date_str[2]);
            }

            /**Size**/
            $size=(float) $size[1];
            switch ($k_size[0]){
                case 'KiB':
                    $size = $size * 1/1024;
                    break;

                case 'MiB':
                    break;

                case 'GiB':
                    $size = $size * 1024;
                    break;

                case 'TiB':
                    $size = $size * 1024*1024;
                    break;
            }
            $result->setName(trim($itemCrawler->filter('.detName')->text()));
            $result->setCategory(trim($itemCrawler->filter('.vertTh')->text()));
            $result->setSeeders((int) $itemCrawler->filter('td')->eq(2)->text());
            $result->setLeechers((int) $itemCrawler->filter('td')->eq(3)->text());
            $result->setSource(TorrentScraperService::THEPIRATEBAY);
            $result->setMagnetUrl($itemCrawler->filterXpath('//tr/td/a')->attr('href'));
            $result->setTimestamp($date->getTimestamp());
            $result->setSize($size);
            $results[] = $result;
        }

        return $results;
    }
}
