<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use GuzzleHttp\Exception\ClientException;
use SergeySMoiseev\TorrentScraper\AdapterInterface;
use SergeySMoiseev\TorrentScraper\HttpClientAware;
use SergeySMoiseev\TorrentScraper\LoggerAware;
use SergeySMoiseev\TorrentScraper\Entity\SearchResult;
use SergeySMoiseev\TorrentScraper\TorrentScraperService;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\DomCrawler\Crawler;
use DateTime;
use DateInterval;
use Torrent;



class ThePirateBayAdapter implements AdapterInterface
{
  use HttpClientAware;
  use LoggerAware;

  const ADAPTER_NAME = 'thePirateBay';

  /**
   * {@inheritDoc}
   */
  public function __construct(array $options = [])
  {

  }

  /**
   * {@inheritDoc}
   */
  public function getLabel()
  {
    return 'The Pirate Bay';
  }

  /**
   * {@inheritDoc}
   */
  public function getUrl()
  {
    return 'https://thepirate-bay.org/';
  }

  /**
   * {@inheritDoc}
   */
  public function search($query='')
  {
    $urls = empty($query) ?
      [
        // 'https://thepiratebay.se/recent',
        'https://tpb.party/top/all',
        'https://tpb.party/top/100', //Audio
        'https://tpb.party/top/101', //Music
        'https://tpb.party/top/200', //Video
        'https://tpb.party/top/201', //Movies
        'https://tpb.party/top/400', //games
        'https://tpb.party/top/300', //soft
        'https://tpb.party/top/301', //soft Windows
        'https://tpb.party/top/48hall', //
        'https://tpb.party/top/601'  //eBooks
      ] :
      [sprintf('https://tpb.party/search/%s/0/7/0', urlencode($query))]
    ;
    $httpClient = $this->httpClient;
    $response = array_filter(array_map(
      function($url) use($httpClient)
      {
        try{
          return $httpClient->get($url);
        } catch (\Exception $e) {
          $this->log(\Psr\Log\LogLevel::ERROR, $e->getMessage());
          // var_dump($e->getMessage());
          return [];
        }
      },
      $urls
    ));
    $results = [];

    foreach ($response as $_response) {
      $crawler = new Crawler((string)$_response->getBody());
      $items = $crawler->filter('#searchResult tr');

      $first = true;
      foreach ($items as $item) {
        // Ignore the first row, the header
        if ($first) {
          $first = false;
          continue;
        }

        $result = new SearchResult();
        $itemCrawler = new Crawler($item);
        try {
          $desc = trim($itemCrawler->filter('.detDesc')->text());
          $name = trim($itemCrawler->filter('.detName')->filter('a')->text());
          $magnet = $itemCrawler->filterXpath('//tr/td/a')->attr('href');
          $attr = null;
          $verified = false;
          for ($i = 1; ($i <= 4 && $verified == false); $i++) {
            try {
              $attr = $itemCrawler->filter('td')->eq('1')->filter('a')->eq($i)->filter('img')->attr('title');
            } catch (\Exception $e) {
              $attr = null;
            }
            if ($attr == 'VIP' || $attr == 'Trusted') {
              $verified = true;
            }
          }
        } catch (\Exception $e) {
          continue;
        }

        /**Validate hash **/
        preg_match("/urn:btih:(.{40}).*/", $magnet, $out);
        if (isset($out[1])) $hash = strtolower($out[1]);
        if (!(preg_match("/^[a-f0-9]{40}$/", $hash))) {
          continue;
        }


        $now = new DateTime();

        try {
          /** Time */
          if (!preg_match("/\d{2}:\d{2}/", $desc, $time_str)) {
            $time_str[0] = '00:00';
          }
          /** Month Day */
          if (preg_match("/\d{2}-\d{2}|Today|Y-day/", $desc, $date_str)) {
            if ($date_str[0] == 'Today') {
              $month_day = $now->format('m-j');
            } elseif ($date_str[0] == 'Y-day') {
              $month_day = $now->modify('-1 day')->format('m-j');

            } else {
              $month_day = $date_str[0];
            }
          } else {
            $month_day = $now->format('m-j');
          };

          /** Year */
          try {
            $year = (preg_match("/(\d{4}),[^\.]/", $desc, $year)) ? $year[1] : $now->format('Y');
          } catch (\Exception $e) {
          }
          /** DateTime object */
          $date_time_str = $month_day . '-' . $year . ' ' . $time_str[0];
          $date = \DateTime::createFromFormat('m-j-Y H:i', $date_time_str);
        } catch (\Exception $e) {
          $date = $now;
        }

        try {
          /**Size**/
          preg_match('/(\d+(?:\.\d+)?)\s(MiB|GiB|TiB|KiB)/u', $desc, $size);

          $size = (float)$size[1];
          switch ($size[2]) {
            case 'KiB':
              $size = $size / 1024;
              break;

            case 'MiB':
              break;

            case 'GiB':
              $size = $size * 1024;
              break;

            case 'TiB':
              $size = $size * 1024 * 1024;
              break;

          }
        } catch (\Exception $e) {
//                var_dump('last') ;        var_dump($desc);
          continue;
        };

        /**Category**/
        try {
          $category = trim($itemCrawler->filter('.vertTh')->text());
          preg_match('/\pL+/u', $category, $parent_cat);
          preg_match('/\(((.?)+)\)/', $category, $child_cat);
          $category = implode(":", [$parent_cat[0], $child_cat[1]]);
        } catch (\Exception $e) {
          $category = null;
        }
        try {
          $link = $itemCrawler->filter('.detName')->children(1)->attr('href');
        } catch (\Exception $e) {
          $link = null;
        }
        try {
          $seeds = (int)$itemCrawler->filter('td')->eq(2)->text();
        } catch (\Exception $e) {
          $seeds = 0;
        }
        try {
          $peers = (int)$itemCrawler->filter('td')->eq(3)->text();
        } catch (\Exception $e) {
          $peers = 0;
        }

        $result->setName($name);
        $result->setDetailsUrl('https://www.pirate-bay.net' . $link);
        $result->setCategory($category);
        $result->setSeeders((int)$seeds);
        $result->setLeechers((int)$peers);
        $result->setSource(self::ADAPTER_NAME);
        $result->setMagnetUrl($magnet);
        $result->setTimestamp($date->getTimestamp());
        $result->setSize($size);
        $result->setIsVerified($verified);

        $results[] = $result;
      }
    }
    return $results;
  }
}
