<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use SergeySMoiseev\TorrentScraper\AdapterInterface;
use SergeySMoiseev\TorrentScraper\HttpClientAware;
use SergeySMoiseev\TorrentScraper\Entity\SearchResult;
use SergeySMoiseev\TorrentScraper\TorrentScraperService;
use Symfony\Component\DomCrawler\Crawler;
use DateTime;


class Torrentz2Adapter implements AdapterInterface
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
    public function search($query='')
    {
        try {
            if (!empty($query)) {
                $response[0] = [$this->httpClient->get('https://torrentz2.eu/verifiedA?f=' . urlencode($query) . '/')];
                $response[1] = [$this->httpClient->get('https://torrentz2.eu/verifiedP?f=' . urlencode($query) . '/')];
            } else {
                $response[0]['video'] = $this->httpClient->get('https://torrentz2.eu/verifiedA?f=movies%20added%3A3d');
                $response[0]['video tv'] = $this->httpClient->get('https://torrentz2.eu/verifiedA?f=tv%20added%3A9d');
                $response[0]['music'] = $this->httpClient->get('https://torrentz2.eu/verifiedA?f=music%20added%3A30d');
                $response[0]['game'] = $this->httpClient->get('https://torrentz2.eu/verifiedA?f=games%20added%3A40d');

                $response[1]['video tv'] = $this->httpClient->get('https://torrentz2.eu/verifiedP?f=tv%20added%3A9d');
                $response[1]['video'] = $this->httpClient->get('https://torrentz2.eu/verifiedP?f=movies%20added%3A3d');
                $response[1]['music'] = $this->httpClient->get('https://torrentz2.eu/verifiedP?f=music%20added%3A30d');
                $response[1]['game'] = $this->httpClient->get('https://torrentz2.eu/verifiedP?f=games%20added%3A40d');
            }
        } catch (\Exception $e) {
            return [];
        }
        $results = [];
        $hashes = [];
        $category = null;
        $now = new DateTime();

        foreach ($response as $_response) {
            foreach ($_response as $category => $__response) {
                $name = '';
                $hash = '';
                $magnet = '';
                $seeders = 0;
                $leechers = 0;
                $size = 0;
                $age = $now;

                $crawler = new Crawler((string)$__response->getBody());
                $items = $crawler->filter('div.results')->filter('dl');
                
                
                foreach ($items as $item) {
                    $itemCrawler = new Crawler($item);

                    try {
            /**Name**/
                        $name = $itemCrawler->filter('a')->text();
            /**Hash**/
                        $hash = $itemCrawler->filter('a')->attr('href');
                        $hash = strtolower($hash);
                        if ($hash) $hash = substr($hash, 1);
                        $magnet = 'magnet:?xt=urn:btih:' . $hash . '&dn=' . $name;
            /**Size**/
                        $size = $itemCrawler->filter('dd')->filter('span:nth-child(3)')->text();
                        preg_match("/MB|GB|TB|KB/", $size, $k_size);
                        preg_match("/[0-9]+(\S[0-9]+)?/", $size, $size);
                        $size = (float)$size[0];
                        switch ($k_size[0]) {
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

            /**Category**/
                        if ($query) {
                            $input_line = $itemCrawler->filter('dt')->text();
                            preg_match("/» (.+)/", $input_line, $output_line);
                            $input_line = $output_line[1];
                            $input_line == 'video tv' ? $output_line[0] = 'video tv' : preg_match("/^\S+/", $input_line, $output_line);
                            $category = $output_line[0];
                        }
                    } catch (\Exception $e) {
                        continue;
                    }

            /**Age**/
                    try {
                        $age = $itemCrawler->filter('dd')->filter('span:nth-child(2)')->attr('title');
                    } catch (\Exception $e) {}
            /**Seeders**/
                    try{
                        $seeders = (int)str_replace([',', '.'], '', $itemCrawler->filter('dd')->filter('span:nth-child(4)')->text());
                    } catch (\Exception $e) {}

            /**Leechers**/
                    try{
                        $leechers = (int)str_replace([',', '.'], '', $itemCrawler->filter('dd')->filter('span:nth-child(5)')->text());;
                    } catch (\Exception $e) {}

                    if (1||in_array($hash, $hashes) == false) {
                        $result = new SearchResult();
                        $result->setName($name)
                            ->setCategory($category)
                            ->setDetailsUrl('https://torrentz2.eu/' . $hash)
                            ->setSource(TorrentScraperService::TORRENTZ2)
                            ->setSeeders($seeders)
                            ->setLeechers($leechers)
                            ->setSize($size)
                            ->setMagnetUrl($magnet)
                            ->setTimestamp($age);
                        $results[] = $result;
                        $hashes[] = $hash;
                    }
                }
            }
        }
        echo "\n T2 - completed. ".count($results)." crawled\n";
        return $results;
    }
}
