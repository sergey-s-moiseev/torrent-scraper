Torrent Scraper
===============

## About
This library provides an abstraction to search for torrent files accross some torrent websites.

## Usage
```php
<?php

$scraperService = new \SergeySMoiseev\TorrentScraper\TorrentScraperService(['ezTv', 'kickassTorrents']);
$results = $scraperService->search('elementaryos');

foreach ($results as $result) {
	$result->getName();
    $result->getSeeders();
    $result->getLeechers();
    $result->getTorrentUrl();
    $result->getMagnetUrl();
}
```

## Available adapters

* [ezTv](https://eztv.ag/)
* [kickassTorrents](http://kickass.to)
* [thePirateBay](http://thepiratebay.se)
