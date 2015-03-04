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

    function keyvalueFirst($products) {
        $arraykeys = array_keys($products[0]);
        $this->writeToFile($arraykeys);
    }

    /**
     * Add lines to a already used file
     *
     * @param $product
     */
    function appendToFile($product)
    {
        $this->writeToFile($product);
    }

    /**
     * Write over a existing file if it exists and write all fields.
     *
     * @param $products
     */
    function writeOverFile($products)
    {
        $this->keyvalueFirst($products);
        foreach ($products as $product) {
            $this->writeToFile($product);
        }
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
     * @return int|boolean
     */
    private function writeToFile($fields)
    {
        return fputcsv($this->fileresource, $fields);
    }

    /**
     * opening the file resource
     *
     * @param string $mode
     * @param bool $removeFile
     */
    function openFile($mode = "w+",$removeFile = false)
    {
        if ($removeFile && file_exists(_PS_ROOT_DIR_.$this->filepath)) {
            unlink(_PS_ROOT_DIR_.$this->filepath);
        }
        $this->closeFile();
        $this->fileresource = fopen(_PS_ROOT_DIR_.$this->filepath, $mode) or die("Can't open file");
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