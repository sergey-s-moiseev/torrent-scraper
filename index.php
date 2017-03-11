<?php
require_once __DIR__ . '/vendor/autoload.php';

use SergeySMoiseev\TorrentScraper\TorrentScraperService;

$params = $_REQUEST;
$response = [];
if (isset($params['task']) && isset($params['key']))
{
    $task = $params['task'];

    $service = new TorrentScraperService(isset($task['sources']) ? $task['sources'] : []);

    switch ($task['name']){
        case 'crawl':
            $result = [];
            $search = $service->search($task['query']);
            foreach ($search as $_item) {
                $result[] = [
                    'name' => $_item->getName(),
                    'seeds' => (int) $_item->getSeeders(),
                    'peers' => (int) $_item->getLeechers(),
                    'source' => $_item->getSource(),
                    'details' => $_item->getDetailsUrl(),
                    'magnet' => $_item->getMagnetUrl(),
                    'category' => $_item->getCategory(),
                    'torrent' => $_item->getTorrentUrl(),
                    'size' =>$_item->getSize(),
                    'timestamp' =>$_item->getTimestamp(),
                    'rating' => $_item->getRating()
                ];
            }
            echo(json_encode(['key' => $params['key'], 'result' => $result]));
            exit;
        default:
            break;
    }

}
exit;