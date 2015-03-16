<?php

/**
 * Taking care of the feed file
 *
 * @author Håkan Nylén <hakan.nylen@fyndiq.se>
 */
class FmFileHandler
{
    private $filepath = "/files/feed.csv";
    private $rootpath = "";
    private $fileresource = null;
    private $mode = null;

    function __construct($path, $mode = null, $remove = false)
    {
        $this->rootpath = $path;
        if(!is_null($mode)) {
            $this->mode = $mode;
            $this->openFile($remove);
        }
    }

    /**
     * Write over a existing file if it exists and write all fields.
     *
     * @param $products
     * @param $keys
     * @return boolean
     */
    function writeOverFile($keys, $products)
    {
        $this->openFile(true);
        $this->writeheader($keys);
        foreach ($products as $product) {
            $this->writeToFile($keys, $product);
        }
        $this->closeFile();
        return true;
    }


    function fileExists()
    {
        return file_exists($this->filepath);
    }

    function removeFile($recreate = false)
    {
        if (file_exists($this->filepath)) {
            unlink($this->filepath);
        }
        if ($recreate) {
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
    private function writeToFile($keys, $fields)
    {
        $printarray = array();
        foreach ($keys as $key) {
            if (isset($fields[$key])) {
                $printarray[] = $fields[$key];
            } else {
                $printarray[] = "";
            }
        }

        return fputcsv($this->fileresource, $printarray);
    }

    /**
     * opening the file resource
     *
     * @param bool $removeFile
     * @internal param string $mode
     */
    private function openFile($removeFile = false)
    {
        if ($removeFile && file_exists($this->rootpath . $this->filepath)) {
            unlink($this->rootpath . $this->filepath);
        }
        $this->closeFile();
        $this->fileresource = fopen($this->rootpath . $this->filepath, $this->mode) or die("Can't open file");
    }

    public function getContentfromFile()
    {
        fpassthru($this->fileresource);
    }

    /**
     * Closing the file if isn't already closed
     */
    private function closeFile()
    {
        if ($this->fileresource != null) {
            fclose($this->fileresource);
            $this->fileresource = null;
        }
    }


    /**
     * Write the header to file
     *
     * @param $keys
     */
    private function writeHeader($keys)
    {
        fputcsv($this->fileresource, $keys);
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