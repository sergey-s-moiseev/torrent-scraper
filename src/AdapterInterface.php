<?php

namespace SergeySMoiseev\TorrentScraper;

use \Psr\Log\LoggerAwareInterface;

interface AdapterInterface extends LoggerAwareInterface
{
    /**
     * Construct the adapter with its options.
     *
     * @param array $options
     */
    public function __construct(array $options);

    /**
     * Set the Guzzle client instance
     *
     * @param \GuzzleHttp\Client $httpClient
     */
    public function setHttpClient(\GuzzleHttp\Client $httpClient);

    /**
     * Get Guzzle client
     *
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient();

    /**
     * Perform the search
     *
     * @param string $query
     */
    public function search($query);

    /**
     * Get text label of the scraper
     * 
     * @return string
     */
    public function getLabel();

    /**
     * Get Url of scraper's site
     * 
     * @return string
     */
    public function getUrl();

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = array());
}
