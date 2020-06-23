<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use SergeySMoiseev\TorrentScraper\AdapterInterface;
use SergeySMoiseev\TorrentScraper\HttpClientAware;
use SergeySMoiseev\TorrentScraper\LoggerAware;
use SergeySMoiseev\TorrentScraper\Entity\SearchResult;
use SergeySMoiseev\TorrentScraper\TorrentScraperService;
use Symfony\Component\DomCrawler\Crawler;
use DateTime;
use GuzzleHttp\Client;
use Tuna\CloudflareMiddleware;
use GuzzleHttp\Cookie\FileCookieJar;

class Torrentz2Adapter implements AdapterInterface
{
  use HttpClientAware;
  use LoggerAware;

  const ADAPTER_NAME = 'torrentz2';
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
//    $this->options = array_merge(
//      [
//        'node_path' => null,
//        'node_modules_path' => null
//      ],
//      array_filter(
//        $options,
//        function($key){
//          return in_array($key, ['node_path', 'node_modules_path']);
//        },
//        ARRAY_FILTER_USE_KEY
//      )
//    );
  }

  /**
   * {@inheritDoc}
   */
  public function getLabel()
  {
    return 'Torrentz2';
  }

  /**
   * {@inheritDoc}
   */
  public function getUrl()
  {
    return 'https://torrentz.unblockit.pw/';
  }

  /**
   * {@inheritDoc}
   */
  public function search($query='')
  {
      $project = 0;
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "https://app.scrapinghub.com/api/jobs/list.json?project=$project&spider=tz2&state=finished&count=1");
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
          $result->setCategory($item->categories[0]);
          $result->setName($item->title);
          $result->setDetailsUrl($this->getUrl().$item->url);
          $result->setSeeders((int) $item->seeds);
          $result->setLeechers($item->peers);
          $result->setTimestamp((int) $item->released);
          $result->setSource(self::ADAPTER_NAME);
          $result->setMagnetUrl($item->magnet);
          $result->setSize($item->size);
          $result->setIsVerified($item->verified);
          $results[] = $result;
      }
     echo "\n T2 - completed. ".count($results)." crawled\n";
    return $results;
  }

  private function getTmpFilename($tmp)
  {
    $metaData = stream_get_meta_data($tmp);
    return $metaData["uri"];
  }
}
