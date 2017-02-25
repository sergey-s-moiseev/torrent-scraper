Torrent Scraper
===============

## About
This library provides an abstraction to search for torrent files accross some torrent websites.

## Usage
```php
<?php

$scraperService = new \SergeySMoiseev\TorrentScraper\TorrentScraperService([TorrentScraperService::EZTV, TorrentScraperService::KICKASS]);
$results = $scraperService->search('query');

foreach ($results as $result) {
	$result->getName();
    $result->getSource();
    $result->getSeeders();
    $result->getLeechers();
    $result->getTorrentUrl();
    $result->getMagnetUrl();
    $result->getDetailsUrl();
    $result->getCategory();
    $result->getSize();
}
```


## Additional torrents info

For scrap Seeds and Peers by Announce trackers use python daemon in */daemon* folder

## Daemon usage
```bash
python daemon/main.py 
```
Server was started on *0.0.0.0:5000* (by default)
for get additional information you need to send post JSON request [Content-Type:application/json] on server
```json
{"data":
  {
    "trackers": ["udp://tracker1.wasabii.com.tw:6969/announce","…"],
    "hashes": ["89925fb48cae260801f35fb7175530bf6e5e055a", "…"]
  },
  "callback": "http://yousite.url/callback_action",
  "private_key": "your_ip_key" 

}
```
Where: 
- trackers - list of announce servers
- hashes - torrent hashes
- callback - url to you callback action for result JSON ('http://' or 'https://" is `important`!) 
- private_key - self generated key for protect your action (`recommendation:make new key for each request`)
``

Result JSON format:
```json
{"data":
    [
      {"89925fb48cae260801f35fb7175530bf6e5e055a": {"peers": 1234, "seeds": 4321},
      "…"
    ]
}
```

```php
<?php

$scraperService = new \SergeySMoiseev\TorrentScraper\TorrentScraperService();
$query = [
    "trackers": ["udp://tracker1.wasabii.com.tw:6969/announce","…"],
    "hashes": ["89925fb48cae260801f35fb7175530bf6e5e055a", "…"]
];
$results = $scraperService->scrap($query, "http://yousite.url/callback_action", "your_ip_key");

```
Result will come on callback URL after collect information from announce trackers

## Available adapters

* [ezTv](https://eztv.ag/)
* [kickassTorrents](http://kickass.to)
* [thePirateBay](http://thepiratebay.se)
