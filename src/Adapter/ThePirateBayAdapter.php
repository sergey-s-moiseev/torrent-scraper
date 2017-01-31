<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use GuzzleHttp\Exception\ClientException;
use SergeySMoiseev\TorrentScraper\AdapterInterface;
use SergeySMoiseev\TorrentScraper\HttpClientAware;
use SergeySMoiseev\TorrentScraper\Entity\SearchResult;
use SergeySMoiseev\TorrentScraper\TorrentScraperService;
use Symfony\Component\DomCrawler\Crawler;

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
//            $desc = trim($itemCrawler->filter('.detDesc')->text());
            $result->setName(trim($itemCrawler->filter('.detName')->text()));
            $result->setCategory(trim($itemCrawler->filter('.vertTh')->text()));
            $result->setSeeders((int) $itemCrawler->filter('td')->eq(2)->text());
            $result->setLeechers((int) $itemCrawler->filter('td')->eq(3)->text());
            $result->setSource(TorrentScraperService::THEPIRATEBAY);
            $result->setMagnetUrl($itemCrawler->filterXpath('//tr/td/a')->attr('href'));

            $results[] = $result;
        }

        return $results;
    }
}
