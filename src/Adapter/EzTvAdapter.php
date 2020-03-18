<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use GuzzleHttp\Exception\ClientException;
use SergeySMoiseev\TorrentScraper\AdapterInterface;
use SergeySMoiseev\TorrentScraper\HttpClientAware;
use SergeySMoiseev\TorrentScraper\LoggerAware;
use SergeySMoiseev\TorrentScraper\Entity\SearchResult;
use SergeySMoiseev\TorrentScraper\TorrentScraperService;
use Symfony\Component\DomCrawler\Crawler;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Tuna\CloudflareMiddleware;
use GuzzleHttp\Cookie\FileCookieJar;

class EzTvAdapter implements AdapterInterface
{
    use HttpClientAware;
    use LoggerAware;

    const ADAPTER_NAME = 'ezTv';
    const SHUB_KEY = '';

    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritDoc}
     */
    public function __construct(array $options = [])
    {
        $defaults = ['seeders' => 1, 'leechers' => 1];

        $this->options = array_merge($defaults, $options);
        $this->options = array_merge(
            [
                'node_path' => null,
                'node_modules_path' => null,
                'seeders' => 1,
                'leechers' => 1
            ],
            array_filter(
                $options,
                function($key){
                  return in_array($key, ['node_path', 'node_modules_path', 'seeders', 'leechers']);
                },
                ARRAY_FILTER_USE_KEY
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return 'EzTV';
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        return 'https://eztv.io';
    }

    /**
     * {@inheritDoc}
     */
    public function search($query='')
    {
        $project = 0;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.scrapinghub.com/api/jobs/list.json?project=$project&spider=eztv&state=finished&count=1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERNAME, self::SHUB_KEY);
        $output = curl_exec($ch);
        curl_close($ch);
        $job_info = json_decode($output);
        $job_id = $job_info->jobs[0]->id;

        $url = "https://storage.scrapinghub.com/items/$job_id?format=json";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERNAME, self::SHUB_KEY);
        $output = curl_exec($ch);
        curl_close($ch);
        $items = json_decode($output);

        $results = [];
        foreach ($items as $item) {
            $result = new SearchResult();
            $result->setCategory('Tv Show');
            $result->setName($item->title);
            $result->setDetailsUrl($this->getUrl().$item->url);
            $result->setSeeders((int) $item->seeds);
            $result->setLeechers($item->peers);
            $result->setTimestamp((int) $item->released);
            $result->setSource(self::ADAPTER_NAME);
            $result->setMagnetUrl($item->magnet);
            $result->setSize($item->size);
            $result->setIsVerified(true);
            $results[] = $result;
        }
        echo "\n EZ - completed. ".count($results)." crawled \n";

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

    private function getPeers(Client $client, $url) {
        $response = $this->getDataFromHttpClient($client, $url);
        if(null === $response) {
            return [];
        }
        $crawler = new Crawler((string) $response->getBody());
        $item = $crawler->filter('td.episode_middle_column');
        $peers = trim($item->filter('span.stat_red')->text());

        return (int)$peers;

    }

    private function getRating(Client $client, $url) {
        $response = $this->getDataFromHttpClient($client, $url);
        if(null === $response) {
            return [];
        }
        $crawler = new Crawler((string) $response->getBody());
        $item = $crawler->filter('#header_holder');
        $item = $item->filter('td.show_info_rating_score');
        $rating = $item->filter('span')->text();
        return (float)$rating;
    }

    private function getTmpFilename($tmp)
    {
      $metaData = stream_get_meta_data($tmp);
      return $metaData["uri"];
    }

    private function getDataFromHttpClient(Client $client, $url)
    {
        for($i = 0; $i < 5; $i++) {
            try {
                return $client->get($url, [
                    'config' => [
                        'curl' => [
                            CURLOPT_INTERFACE => '127.0.0.1'
                        ]
                    ]
                ]);
            } catch(\Exception $e) {
                $this->log(\Psr\Log\LogLevel::ERROR, $e->getMessage());
            }
        }
    }
}
