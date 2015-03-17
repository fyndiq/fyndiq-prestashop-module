<?php

/**
 * Taking care of the feed file
 *
 * @author Håkan Nylén <hakan.nylen@fyndiq.se>
 */
class FmFileHandler
{
    /**
     * Write over a existing file if it exists and write all fields.
     *
     * @param $products
     * @param $keys
     * @return boolean
     */
    public static function writeToFile($file, $keys, $products)
    {
        // Write header
        fputcsv($file, $keys);
        foreach ($products as $product) {
            self::writeRow($file, $keys, $product);
        }
        return true;
    }

    /**
     * Write single row to file
     *
     * @param $fields
     * @param $keys
     * @return int|boolean
     */
    private static function writeRow($file, $keys, $fields)
    {
        $row = array();
        foreach ($keys as $key) {
            if (isset($fields[$key])) {
                $row[] = $fields[$key];
            } else {
                $row[] = '';
            }
        }
        return fputcsv($file, $row);
    }
}