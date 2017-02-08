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
            if ($query){
                $response = $this->httpClient->get('https://thepiratebay.se/search/' . urlencode($query) . '/0/7/0');
            } else {
                // $response = $this->httpClient->get('https://thepiratebay.se/recent');
                $response = $this->httpClient->get('https://thepiratebay.org/top/all');
            }
        } catch (\Exception $e) {
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
            try{
                $desc = trim($itemCrawler->filter('.detDesc')->text());
            } catch (\Exception $e) {$desc = null;}
            try {
                $name = ($itemCrawler->filter('.detName')->text());
            } catch (\Exception $e) {$name = null;
                continue;}
            try {$magnet = $itemCrawler->filterXpath('//tr/td/a')->attr('href');
            } catch (\Exception $e){$magnet = null;
                continue;}

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
            /**Category**/
            try {
                $category = trim($itemCrawler->filter('.vertTh')->text());
                preg_match('/[a-zA-Z]+/', $category, $parent_cat);
                preg_match('/\(((.?)+)\)/', $category, $child_cat);
                $category = implode(":", [$parent_cat[0], $child_cat[1]]);
            } catch (\Exception $e){$category = null;}
            try {
                $link = $itemCrawler->filter('.detName')->children(1)->attr('href');
            } catch (\Exception $e){$link = null;}
            try {$seeds = (int) $itemCrawler->filter('td')->eq(2)->text();
            } catch (\Exception $e){$seeds = 0;}
            try {$peers = (int) $itemCrawler->filter('td')->eq(3)->text();
            } catch (\Exception $e){$peers = 0;}

            $result->setName($name);
            $result->setDetailsUrl('https://thepiratebay.org'.$link);
            $result->setCategory($category);
            $result->setSeeders($seeds);
            $result->setLeechers($peers);
            $result->setSource(TorrentScraperService::THEPIRATEBAY);
            $result->setMagnetUrl($magnet);
            $result->setTimestamp($date->getTimestamp());
            $result->setSize($size);
            $results[] = $result;
        }
        return $results;
    }
}
