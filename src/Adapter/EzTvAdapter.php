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

            /**Seeds**/
            try {
                $seeds = trim ($itemCrawler->filter('td')->eq(5)->children()->text());
            } catch(\Exception $e){
                $seeds = 0;
            }
            $vowels = array(",", ".", " ");
            $seeds = str_replace($vowels, "", $seeds);

            /**Size**/
            $size_str = trim($itemCrawler->filter('td')->eq(3)->text());
            $size_arr = explode (" ", $size_str);
            $size = floatval($size_arr[0]);
            $k_size = $size_arr[1];
            switch ($k_size){
                case 'KB':
                    $size = $size * 1/1024;
                    break;

                case 'MB':
                    break;

                case 'GB':
                    $size = $size * 1024;
                    break;

                case 'TB':
                    $size = $size * 1024*1024;
                    break;
            }

            $result->setName(trim($itemCrawler->filter('td')->eq(1)->text()));
            $result->setSeeders($seeds);
            $result->setLeechers($this->options['leechers']);
            $result->setSource(TorrentScraperService::EZTV);
            $result->setMagnetUrl($itemCrawler->filter('td')->eq(2)->children()->attr('href'));
            $result->setSize($size);
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
