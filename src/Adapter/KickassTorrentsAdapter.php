<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use SergeySMoiseev\TorrentScraper\AdapterInterface;
use SergeySMoiseev\TorrentScraper\HttpClientAware;
use SergeySMoiseev\TorrentScraper\Entity\SearchResult;
use SergeySMoiseev\TorrentScraper\TorrentScraperService;
use Symfony\Component\DomCrawler\Crawler;

class KickassTorrentsAdapter implements AdapterInterface
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
            if (!empty($query)){
                $response = $this->httpClient->get('http://kickasstorrents.to/usearch/' . urlencode($query) . '/');
            }
            else {$response = $this->httpClient->get('http://kickasstorrents.to/');
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return [];
        }
        $crawler = new Crawler((string) $response->getBody());
        if (!empty($query)) {
            $items = $crawler->filter('#mainSearchTable tr');
        }
        else {
            $items = $crawler->filter('div.mainpart');
        }
        $results = [];
        $i = 0;
        foreach ($items as $item) {
//
//            // Ignores advertisement and header
//            if ($i < 2) {
//
//                $i ++;
//                continue;
//
//            }

            $itemCrawler = new Crawler($item);
            $name = $itemCrawler->filter('.cellMainLink')->text();
//
//            if (!stristr($name, $query)) {
//                continue;
//            }

            $data = json_decode(str_replace("'", '"', $itemCrawler->filter('div[data-sc-params]')->attr('data-sc-params')));

            $result = new SearchResult();
            $result->setName($name)
                ->setCategory('TV shows')
                ->setSource(TorrentScraperService::KICKASS)
                ->setSeeders((int) $itemCrawler->filter('td:nth-child(5)')->text())
                ->setLeechers((int) $itemCrawler->filter('td:nth-child(6)')->text())
                ->setSize($itemCrawler->filter('td:nth-child(2)')->text())
                ->setMagnetUrl('-')
            ;

            var_dump($result);
            exit;

            $results[] = $result;

        }

        return $results;
    }
}
