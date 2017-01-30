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
            $result->setName(trim($itemCrawler->filter('td')->eq(1)->text()));
            $result->setSeeders($this->options['seeders']);
            $result->setLeechers($this->options['leechers']);
            $result->setSource(TorrentScraperService::EZTV);

            $node = $itemCrawler->filter('a.download_1');
            if ($node->count() > 0) {
                $result->setTorrentUrl($node->eq(0)->attr('href'));
            }

            $node = $itemCrawler->filter('a.magnet');
            if ($node->count() > 0) {
                $result->setMagnetUrl($node->eq(0)->attr('href'));
            }

            $results[] = $result;
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
}
