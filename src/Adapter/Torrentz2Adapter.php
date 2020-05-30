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

  /**
   * @var array
   */
  protected $options;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $options = [])
  {
    $this->options = array_merge(
      [
        'node_path' => null,
        'node_modules_path' => null
      ],
      array_filter(
        $options,
        function($key){
          return in_array($key, ['node_path', 'node_modules_path']);
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
    return 'Torrentz2';
  }

  /**
   * {@inheritDoc}
   */
  public function getUrl()
  {
    return 'https://torrentz2.eu/';
  }

  /**
   * {@inheritDoc}
   */
  public function search($query='')
  {
//     $cookieFile = tmpfile();
//
//     $client = new Client([
//       // 'debug' => true,
//       'cookies' => new FileCookieJar($this->getTmpFilename($cookieFile)),
//       'headers' => [ // these headers need to avoid recaptcha request
//         'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
//         'Accept-Encoding' => 'gzip, deflate',
//         'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7'
//       ]
//     ]);
//     $client->getConfig('handler')->push(CloudflareMiddleware::create($this->options['node_path'], $this->options['node_modules_path']));
//
//     $urls = empty($query) ?
//       [
//         [
//           'video' => 'https://torrentz2.eu/searchA?f=movies%20added%3A3d',
//           'video tv' => 'https://torrentz2.eu/searchA?f=tv%20added%3A9d',
//           'music' => 'https://torrentz2.eu/searchA?f=music%20added%3A30d',
//           'game' => 'https://torrentz2.eu/searchA?f=games%20added%3A40d'
//         ],
//         [
//           'video' => 'https://torrentz2.eu/search?f=movies%20added%3A3d',
//           'video tv' => 'https://torrentz2.eu/search?f=tv%20added%3A9d',
//           'music' => 'https://torrentz2.eu/search?f=music%20added%3A30d',
//           'game' => 'https://torrentz2.eu/search?f=games%20added%3A40d'
//         ]
//       ] :
//       [
//         [sprintf('https://torrentz2.eu/searchA?f=%s/', urlencode($query))],
//         [sprintf('https://torrentz2.eu/search?f=%s/', urlencode($query))]
//       ]
//     ;
//
//     $response = array_map(
//       function($urlSet) use($client)
//       {
//         return array_filter(array_map(
//           function($url) use($client)
//           {
//             try{
//               return $client->request('GET', $url);
//             } catch (\Exception $e) {
//               $this->log(\Psr\Log\LogLevel::ERROR, $e->getMessage());
//               // var_dump($e->getMessage());
//               return [];
//             }
//           },
//           $urlSet
//         ));
//       },
//       $urls
//     );
//
//     fclose($cookieFile);

    $results = [];
//     $hashes = [];
//     $category = null;
//     $now = new DateTime();
//
//     foreach ($response as $_response) {
//       foreach ($_response as $category => $__response) {
//         $name = '';
//         $hash = '';
//         $magnet = '';
//         $seeders = 0;
//         $leechers = 0;
//         $size = 0;
//         $age = $now;
//
//         $crawler = new Crawler((string)$__response ->getBody());
//         $items = $crawler->filter('div.results')->filter('dl');
//
//         foreach ($items as $item) {
//           $itemCrawler = new Crawler($item);
//
//           try {
//             /**Name**/
//             $name = $itemCrawler->filter('a')->text();
//             /**Hash**/
//             $hash = $itemCrawler->filter('a')->attr('href');
//             $hash = strtolower($hash);
//             if ($hash) $hash = substr($hash, 1);
//             $magnet = 'magnet:?xt=urn:btih:' . $hash . '&dn=' . $name;
//
//             /**Validate hash **/
//             if(!(preg_match("/^[a-f0-9]{40}$/",$hash))){continue;}
//
//             /**Size**/
//             $size = $itemCrawler->filter('dd')->filter('span:nth-child(3)')->text();
//             preg_match("/MB|GB|TB|KB/", $size, $k_size);
//             preg_match("/[0-9]+(\S[0-9]+)?/", $size, $size);
//             $size = (float)$size[0];
//             switch ($k_size[0]) {
//               case 'KB':
//                 $size = $size * 1 / 1024;
//                 break;
//               case 'MB':
//                 break;
//               case 'GB':
//                 $size = $size * 1024;
//                 break;
//               case 'TB':
//                 $size = $size * 1024 * 1024;
//                 break;
//             }
//
//             /**Category**/
//             if ($query) {
//               $input_line = $itemCrawler->filter('dt')->text();
//               preg_match("/» (.+)/", $input_line, $output_line);
//               $input_line = $output_line[1];
//               $input_line == 'video tv' ? $output_line[0] = 'video tv' : preg_match("/^\S+/", $input_line, $output_line);
//               $category = $output_line[0];
//             }
//           } catch (\Exception $e) {
//             continue;
//           }
//
//           /**Age**/
//           try {
//             $age = $itemCrawler->filter('dd')->filter('span:nth-child(2)')->attr('title');
//           } catch (\Exception $e) {}
//           /**Seeders**/
//           try{
//             $seeders = (int)str_replace([',', '.'], '', $itemCrawler->filter('dd')->filter('span:nth-child(4)')->text());
//           } catch (\Exception $e) {}
//
//           /**Leechers**/
//           try{
//             $leechers = (int)str_replace([',', '.'], '', $itemCrawler->filter('dd')->filter('span:nth-child(5)')->text());;
//           } catch (\Exception $e) {}
//
//           /**Verified**/
//           try{
//             $verified = 0 < strlen(preg_replace('/\s/', '', $itemCrawler->filter('dd')->filter('span:nth-child(1)')->text()));
//           } catch (\Exception $e) {}
//
//           if (in_array($hash, $hashes) == false) {
//             $result = new SearchResult();
//             $result->setName($name)
//               ->setCategory($category)
//               ->setDetailsUrl('https://torrentz2.eu/' . $hash)
//               ->setSource(self::ADAPTER_NAME)
//               ->setSeeders($seeders)
//               ->setLeechers($leechers)
//               ->setSize($size)
//               ->setMagnetUrl($magnet)
//               ->setTimestamp($age)
//               ->setIsVerified($verified)
//             ;
//             $results[] = $result;
//             $hashes[] = $hash;
//           }
//         }
//       }
//     }
//     echo "\n T2 - completed. ".count($results)." crawled\n";
    return $results;
  }

  private function getTmpFilename($tmp)
  {
    $metaData = stream_get_meta_data($tmp);
    return $metaData["uri"];
  }
}
