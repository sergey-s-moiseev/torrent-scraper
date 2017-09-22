<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use SergeySMoiseev\TorrentScraper\AdapterInterface;
use SergeySMoiseev\TorrentScraper\HttpClientAware;
use SergeySMoiseev\TorrentScraper\Entity\SearchResult;
use SergeySMoiseev\TorrentScraper\TorrentScraperService;
use Symfony\Component\DomCrawler\Crawler;
use DateTime;


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
    public function search($query='')
    {
        try {
            if (!empty($query)) {
                $response = [$this->httpClient->get('https://kickass2.nz/usearch/' . urlencode($query) . '/')];
            } else {
                $response['Movies'] = $this->httpClient->get('https://kickass2.nz/movies/?field=time_add&sorder=desc');
                $response['TV'] = $this->httpClient->get('https://kickass2.nz/tv/?field=time_add&sorder=desc');
                $response['Anime'] = $this->httpClient->get('https://kickass2.nz/anime/?field=time_add&sorder=desc');
                $response['Music'] = $this->httpClient->get('https://kickass2.nz/music/?field=time_add&sorder=desc');
                $response['Books'] = $this->httpClient->get('https://kickass2.nz/books/?field=time_add&sorder=desc');
                $response['Games'] = $this->httpClient->get('https://kickass2.nz/games/?field=time_add&sorder=desc');
                $response['Applications'] = $this->httpClient->get('https://kickass2.nz/applications/?field=time_add&sorder=desc');
                $response['XXX'] = $this->httpClient->get('https://kickass2.nz/xxx/?field=time_add&sorder=desc');
                $response['Other'] = $this->httpClient->get('https://kickass2.nz/other/?field=time_add&sorder=desc');
            }
        } catch (\Exception $e) {
            return [];
        }
        $results = [];
        foreach ($response as $category => $_response) {
            $crawler = new Crawler((string)$_response->getBody());
            $items = $crawler->filter('tr#torrent__torrents');
            foreach ($items as $item) {
                $itemCrawler = new Crawler($item);

                /**Category**/
                if ($query){
                    try{
                        $category = $itemCrawler->filterXPath('//*[contains(@id, "cat_")]')->filter('a')->text();
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                /**Name**/
                /**Age**/

                try{
                    $name = $itemCrawler->filter('.cellMainLink')->text();
                    $age =$itemCrawler->filter('td.center:nth-child(4)')->text();
                } catch (\Exception $e) {
                    continue;
                }
                $age = iconv('UTF-8','cp1251',$age);
                $age = str_replace(chr(160), chr(32), $age);
                $age = iconv('cp1251','UTF-8',$age);

                $now = new DateTime();
                try {
                    $date = $now->modify('- ' . $age);
                } catch (\Exception $e) {
                    $date = $now;
                }

                /**Verified**/
                try {
                    $verify = $itemCrawler->filter('div.iaconbox')->filter('a.icon16')->attr('title');
                    if ($verify != 'Verified Torrent'){continue;}
                } catch (\Exception $e) {
                    continue;
                }

                /**Magnet**/
                try {
                    $input = $itemCrawler->filter('div.iaconbox')->filter('a.icon16:nth-child(3)')->attr('href');
//                    preg_match("/'magnet': '(.{0,})'/", $input, $output);
//                    $magnet = $output[1];
                    $magnet = $input;
                } catch (\Exception $e) {
                    continue;
                }

                /**Validate hash **/
                preg_match("/urn:btih:(.{40}).*/",$magnet,$out);
                if (isset($out[1])) $hash = strtolower($out[1]);
                if(!(preg_match("/^[a-f0-9]{40}$/",$hash))){continue;}

                try {
                    $link = $itemCrawler->filter('.cellMainLink')->attr('href');
                } catch (\Exception $e) {
                    continue;
                }
                /**Size**/
                try {
                    $size = $itemCrawler->filter('td:nth-child(2)')->text();
                preg_match("/MB|GB|TB|KB/", $size, $k_size);
                preg_match("/[0-9]+(\S[0-9]+)?/", $size, $size);
                $size =(float) $size[0];
                switch ($k_size[0]){
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
                } catch (\Exception $e) {
                    continue;
                }
                /**Result**/
                $result = new SearchResult();
                $result->setName($name)
                    ->setCategory($category)
                    ->setDetailsUrl('https://kickass2.nz'.$link)
                    ->setSource(TorrentScraperService::KICKASS)
                    ->setSeeders((int)$itemCrawler->filter('td:nth-child(4)')->text())
                    ->setLeechers((int)$itemCrawler->filter('td:nth-child(5)')->text())
                    ->setSize($size)
                    ->setMagnetUrl($magnet)
                    ->setTimestamp($date->getTimestamp())
                    ;
                $results[] = $result;

            }
        }

        echo "\n KA - completed. ".count($results)." crawled\n";
        return $results;
    }
}
