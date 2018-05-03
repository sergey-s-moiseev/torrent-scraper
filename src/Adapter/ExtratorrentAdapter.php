<?php

namespace SergeySMoiseev\TorrentScraper\Adapter;

use SergeySMoiseev\TorrentScraper\AdapterInterface;
use SergeySMoiseev\TorrentScraper\HttpClientAware;
use SergeySMoiseev\TorrentScraper\Entity\SearchResult;
use SergeySMoiseev\TorrentScraper\TorrentScraperService;
use Symfony\Component\DomCrawler\Crawler;
use DateTime;


class ExtratorrentAdapter implements AdapterInterface
{
    use HttpClientAware;

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
        return 'Extratorrent';
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        return 'http://extratorrent.cc/';
    }

    /**
     * {@inheritDoc}
     */
    public function search($query='')
    {
        $search = ["[","]","\\","/","^","$",".","|","?","*","+","(",")","{","}"];
        $replace = ["\[","\]","\\","\/","\^","\$","\.","\|","\?","\*","\+","\(","\)","\{","\}"];
        try {
            if (!empty($query)) {
                $response[0] = [$this->httpClient->get('http://extratorrent.cc/search/?search=' . urlencode($query) . '&s_cat=&pp=&srt=added&order=desc/')];
                $response[1] = [$this->httpClient->get('http://extratorrent.cc/search/?search=' . urlencode($query) . '&s_cat=&pp=&srt=seeds&order=desc/')];
            } else {
                $response[0]['Movies'] = $this->httpClient->get('http://extratorrent.cc/category/4/Movies+Torrents.html?srt=added&order=desc');
                $response[0]['TV'] = $this->httpClient->get('http://extratorrent.cc/category/8/TV+Torrents.html?srt=added&order=desc');
                $response[0]['Music'] = $this->httpClient->get('http://extratorrent.cc/category/5/Music+Torrents.html?srt=added&order=desc');
                $response[0]['Games'] = $this->httpClient->get('http://extratorrent.cc/category/3/Games+Torrents.html?srt=added&order=desc');
                $response[0]['Software'] = $this->httpClient->get('http://extratorrent.cc/category/7/Software+Torrents.html?srt=added&order=desc');
                $response[0]['Adult / Porn'] = $this->httpClient->get('http://extratorrent.cc/category/533/Adult+-+Porn+Torrents.html?srt=added&order=desc');
                $response[0]['Anime'] = $this->httpClient->get('http://extratorrent.cc/category/1/Anime+Torrents.html?srt=added&order=desc');

                $response[1]['Movies'] = $this->httpClient->get('http://extratorrent.cc/category/4/Movies+Torrents.html?srt=seeds&order=');
                $response[1]['TV'] = $this->httpClient->get('http://extratorrent.cc/category/8/TV+Torrents.html?srt=seeds&order=');
                $response[1]['Music'] = $this->httpClient->get('http://extratorrent.cc/category/5/Music+Torrents.html?srt=seeds&order=');
                $response[1]['Games'] = $this->httpClient->get('http://extratorrent.cc/category/3/Games+Torrents.html?srt=seeds&order=');
                $response[1]['Software'] = $this->httpClient->get('http://extratorrent.cc/category/7/Software+Torrents.html?srt=seeds&order=');
                $response[1]['Adult / Porn'] = $this->httpClient->get('http://extratorrent.cc/category/533/Adult+-+Porn+Torrents.html?srt=seeds&order=');
                $response[1]['Anime'] = $this->httpClient->get('http://extratorrent.cc/category/1/Anime+Torrents.html?srt=seeds&order=');
            }
        } catch (\Exception $e) {
            return [];
        }
        $results = [];
        $hashes = [];
        $category = null;
        $now = new DateTime();
        foreach ($response as $_response) {
            foreach ($_response as $category => $__response) {
                $name = '';
                $hash = '';
                $magnet = '';
                $seeders = 0;
                $leechers = 0;
                $size = 0;
                $age = $now;

                $crawler = new Crawler((string)$__response->getBody());
                $items = $crawler->filter('table.tl')->filter('tr');



                foreach ($items as $item) {
                    $itemCrawler = new Crawler($item);


                    try {


                        /**Name**/
                        $input_line = $itemCrawler->filter('td')->filter('a:nth-child(1)')->attr('title');
                        $name = preg_replace(['/Download /','/ torrent/'], ['',''], $input_line);

                        /**Magnet**/
                        $magnet = $itemCrawler->filter('a:nth-child(2)')->attr('href');

                        /**Hash**/
                        /**Validate hash **/
                        preg_match("/urn:btih:(.{40}).*/",$magnet,$out);
                        if (isset($out[1])) $hash = strtolower($out[1]);
                        if(!(preg_match("/^[a-f0-9]{40}$/",$hash))){continue;}
                        /**Size**/
                        $size = $itemCrawler->filter('td:nth-child(4)')->text();

                        preg_match("/MB|GB|TB|KB/", $size, $k_size);
                        preg_match("/[0-9]+(\S[0-9]+)?/", $size, $size);

                        $size = (float)$size[0];
                        switch ($k_size[0]) {
                            case 'KB':
                                $size = $size * 1 / 1024;
                                break;
                            case 'MB':
                                break;
                            case 'GB':
                                $size = $size * 1024;
                                break;
                            case 'TB':
                                $size = $size * 1024 * 1024;
                                break;
                        }
                        /**Category**/
                        if ($query) {
                            $input_line = $itemCrawler->filter('span.c_tor')->text();
                            $input_line = str_replace($search, $replace, $input_line);
                            preg_match("/in (\w*)/", $input_line, $output_array);
                            $category = $output_array[1];
                        }
                        /**Verified**/
                        $verified = $itemCrawler->filter('div.usr')->filter('div')->filter('a')->attr('style');
                        if ($verified == 'CC0000') continue;

                    } catch (\Exception $e) {
                        continue;
                    }

                    /**Age**/
                    $now = new DateTime();
                    try {
                        $age = $itemCrawler->filter('td:nth-child(3)')->text();
                        if(preg_match("/([0-9]{1,3})(\w)/", $age, $output)) {
                            $_age = str_replace('m', ' minute', $age, $count);
                            if (!$count) $_age = str_replace('h', ' hour', $age, $count);
                            if (!$count) $_age = str_replace('d', ' day', $age, $count);
                            if (!$count) $_age = str_replace('y', ' year', $age, $count);
                            $date = $now->modify('- '.$_age);
                        }
                    } catch (\Exception $e) {$date = $now;}
                    /**Seeders**/
                    try{
                        $seeders = (int) $itemCrawler->filter('td.sy')->text();
                    } catch (\Exception $e) {$seeders = 0;}

                    /**Leechers**/
                    try{
                        $leechers = (int) $itemCrawler->filter('td.ly')->text();
                    } catch (\Exception $e) {$leechers = 0;}

                    /**Ulr**/
                    $link = null;
                    $input_line = $itemCrawler->filter('td.tli')->filter('a')->attr('href');
                    if (preg_match("/(.*)#/", $input_line, $output_array)) {
                        $link = $output_array[1];
                    } else {$link = $input_line;}

                    if (in_array($hash, $hashes) == false) {
                        $result = new SearchResult();
                        $result->setName($name)
                            ->setCategory($category)
                            ->setDetailsUrl('http://extratorrent.cc/' . $link)
                            ->setSource(TorrentScraperService::EXTRATORRENT)
                            ->setSeeders($seeders)
                            ->setLeechers($leechers)
                            ->setSize($size)
                            ->setMagnetUrl($magnet)
                            ->setTimestamp($date->getTimestamp());
                        $results[] = $result;
                        $hashes[] = $hash;
                    }
                }
            }
        }
        echo "\n Extra - completed. ".count($results)." crawled\n";
        return $results;
    }
}
