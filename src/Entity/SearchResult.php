<?php

namespace SergeySMoiseev\TorrentScraper\Entity;

class SearchResult
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $seeders;

    /**
     * @var int
     */
    protected $leechers;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $torrentUrl;

    /**
     * @var string
     */
    protected $magnetUrl;

    /**
     * @var string
     */
    protected $category;

    /**
     * @var string
     */
    protected $timestamp;

    /**
     * @var string
     */
    protected $size;




    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $seeders
     */
    public function setSeeders($seeders)
    {
        $this->seeders = $seeders;
    }

    /**
     * @return int
     */
    public function getSeeders()
    {
        return $this->seeders;
    }

    /**
     * @param int $leechers
     */
    public function setLeechers($leechers)
    {
        $this->leechers = $leechers;
    }

    /**
     * @return int
     */
    public function getLeechers()
    {
        return $this->leechers;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @param string $torrentUrl
     */
    public function setTorrentUrl($torrentUrl)
    {
        $this->torrentUrl = $torrentUrl;
    }

    /**
     * @return string
     */
    public function getTorrentUrl()
    {
        return $this->torrentUrl;
    }

    /**
     * @param string $magnetUrl
     */
    public function setMagnetUrl($magnetUrl)
    {
        $this->magnetUrl = $magnetUrl;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->magnetUrl;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getMagnetUrl()
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param string $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param string $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }


}
