<?php

/**
 * Taking care of the feed file
 *
 * @author Håkan Nylén <hakan.nylen@fyndiq.se>
 */
class FmFileHandler
{
    private $filepath = "/files/feed.csv";
    private $fileresource = null;

    function __construct($mode = "w+",$remove = false) {
        $this->openFile($mode, $remove);
    }

    /**
     * Write the header to file
     *
     * @param $keys
     */
    function writeHeader($keys)
    {
        fputcsv($this->fileresource, $keys);
    }

    /**
     * Write over a existing file if it exists and write all fields.
     *
     * @param $products
     */
    function writeOverFile($products)
    {
        $this->openFile(true);
        $keys = array_shift($products);
        $this->writeheader($keys);
        foreach ($products as $product) {
            $this->writeToFile($product, $keys);
        }
        $this->closeFile();
    }

    function removeFile($recreate = false) {
        if (file_exists($this->filepath)) {
            unlink($this->filepath);
        }
        if($recreate) {
            touch($this->filepath);
        }
    }

    /**
     * simplifying the way to write to the file.
     *
     * @param $fields
     * @param $keys
     * @return int|boolean
     */
    private function writeToFile($fields, $keys)
    {
        $printarray = array();
        foreach ($keys as $key) {
            $printarray[] = $fields[$key];
        }
        return fputcsv($this->fileresource, $printarray);
    }

    /**
     * opening the file resource
     *
     * @param bool $removeFile
     * @internal param string $mode
     */
    function openFile($removeFile = false)
    {
        if ($removeFile && file_exists(_PS_ROOT_DIR_.$this->filepath)) {
            unlink(_PS_ROOT_DIR_.$this->filepath);
        }
        $this->closeFile();
        $this->fileresource = fopen(_PS_ROOT_DIR_.$this->filepath, "w+") or die("Can't open file");
    }

    /**
     * Closing the file if isn't already closed
     */
    function closeFile()
    {
        if ($this->fileresource != null) {
            fclose($this->fileresource);
            $this->fileresource = null;
        }
    }

    /**
     * Closing the file if it isn't already closed when destructing the class.
     */
    function __destruct()
    {
        if ($this->fileresource != null) {
            $this->closeFile();
        }
    }

}