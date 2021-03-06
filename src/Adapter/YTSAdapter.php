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
use GuzzleHttp\Exception\TransferException;
use Tuna\CloudflareMiddleware;
use GuzzleHttp\Cookie\FileCookieJar;

class YTSAdapter implements AdapterInterface
{
  use HttpClientAware;
  use LoggerAware;

  const ADAPTER_NAME = 'yts';
  const LIMIT = 50;


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
    return 'YTS.AM';
  }

  /**
   * {@inheritDoc}
   */
  public function getUrl()
  {
    return 'https://yts.am/';
  }

  /**
   * {@inheritDoc}
   */
  public function search($query='')
  {
    $results = [];
//     $trackers = ['udp://glotorrents.pw:6969/announce',
//         'udp://tracker.opentrackr.org:1337/announce',
//         'udp://torrent.gresille.org:80/announce',
//         'udp://tracker.openbittorrent.com:80',
//         'udp://tracker.coppersurfer.tk:6969',
//         'udp://tracker.leechers-paradise.org:6969',
//         'udp://p4p.arenabg.ch:1337',
//         'udp://tracker.internetwarriors.net:1337'];
//
//     $cookieFile = tmpfile();
//     $client = new Client([
//         'cookies' => new FileCookieJar($this->getTmpFilename($cookieFile)),
//         'headers' => [ // these headers need to avoid recaptcha request
//             'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
//             'Accept-Encoding' => 'gzip, deflate',
//             'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7'
//         ]
//     ]);
//     $client->getConfig('handler')->push(CloudflareMiddleware::create($this->options['node_path'], $this->options['node_modules_path']));
//
//     $data = $this->getMovies($client, 0, $query);
//     if(null === $data) return $results;
//     $total = ceil($data->movie_count / 50);
//     for ($page = 1; $page <= $total; $page++) {
//       if (property_exists($data, 'movies')) {
//         foreach ($data->movies as $movie) {
//           if (property_exists($movie, 'torrents')) {
//             foreach ($movie->torrents as $torrent) {
//               $seeders = $torrent->seeds;
//               $leechers = $torrent->peers;
//               $size = $torrent->size_bytes / 1048576; // size in MegaBytes
//               $trackers_str = join('&tr=', $trackers);
//               $magnet = "magnet:?xt=urn:btih:{$torrent->hash}&tr={$trackers_str}&dn={$movie->title_long} [{$torrent->quality}] [YTS.AM]&xl={$torrent->size_bytes}&dl={$torrent->size_bytes}&as={$torrent->url}";
//               $age = $torrent->date_uploaded_unix;
//
//               $result = new SearchResult();
//               $result->setName("{$movie->title_long} [{$torrent->quality}]")
//                   ->setCategory('Movies')
//                   ->setDetailsUrl($movie->url)
//                   ->setSource(self::ADAPTER_NAME)
//                   ->setSeeders($seeders)
//                   ->setLeechers($leechers)
//                   ->setSize($size)
//                   ->setMagnetUrl($magnet)
//                   ->setTimestamp($age)
//                   ->setIsVerified(true)
//                 ;
//               $results[] = $result;
//               $hashes[] = $torrent->hash;
//             }
//           }
//         }
//       }
//
//       $data = $this->getMovies($client, $page, $query);
//       if(null === $data) break;
//     }
//     fclose($cookieFile);
//     echo "\n YTS - completed. ".count($results)." crawled\n";
    return $results;
  }

  private function getTmpFilename($tmp)
  {
    $metaData = stream_get_meta_data($tmp);
    return $metaData["uri"];
  }

  private function getMovies(Client $client, $page, $query)
  {
    $url = 'https://yts.am/api/v2/list_movies.jsonp?sort_by=seeds&limit=' . self::LIMIT . '&page=' . $page . '&query_term=' . $query;
    for($i = 0; $i < 5; $i++) {
      try {
        $response = $client->get($url);
        return json_decode($response->getBody()->getContents())->data;
      } catch (TransferException $e) {
        $this->log(\Psr\Log\LogLevel::ERROR, $e->getMessage());
      }
    }
    return null;
  }
}