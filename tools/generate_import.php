<?php

class PrestaShopGenerateSampleImport{

    var $columns = 'ID;Active (0/1);Name *;Categories (x,y,z...);Price tax excluded or Price tax included;Tax rules ID;Wholesale price;On sale (0/1);Discount amount;Discount percent;Discount from (yyyy-mm-dd);Discount to (yyyy-mm-dd);Reference #;Supplier reference #;Supplier;Manufacturer;EAN13;UPC;Ecotax;Width;Height;Depth;Weight;Quantity;Minimal quantity;Visibility;Additional shipping cost;Unity;Unit price;Short description;Description;Tags (x,y,z...);Meta title;Meta keywords;Meta description;URL rewritten;Text when in stock;Text when backorder allowed;Available for order (0 = No, 1 = Yes);Product available date;Product creation date;Show price (0 = No, 1 = Yes);Image URLs (x,y,z...);Delete existing images (0 = No, 1 = Yes);Feature(Name:Value:Position);Available online only (0 = No, 1 = Yes);Condition;Customizable (0 = No, 1 = Yes);Uploadable files (0 = No, 1 = Yes);Text fields (0 = No, 1 = Yes);Out of stock;ID / Name of shop;Advanced stock management;Depends On Stock;Warehouse';

    var $categories = array(
        'Women',
        'Tops',
        'Dresses',
        'Blouses',
    );

    public function run($numberProducts)
    {
        $columns = explode(';', $this->columns);
        $emptyRow = array_fill_keys($columns, null);
        $file = fopen('php://stdout', 'w');
        $this->writeCSVRow($file, $columns);
        for($i = 0; $i < $numberProducts; $i++) {
            $row = $this->generateProductRow($emptyRow, $i+1);
            $this->writeCSVRow($file, $row);
        }
        fclose($file);
    }

    protected function writeCSVRow($file, $fields)
    {
        return fputcsv($file, $fields, ';');
    }

    protected function generateProductRow($emptyRow, $index) {
        $emptyRow['ID'] = $index + 100;
        $emptyRow['Reference #'] = sprintf('sku-%08d', $index);
        $emptyRow['Active (0/1)'] = 1;
        $emptyRow['Name *'] = $this->generateName($index);
        $emptyRow['Categories (x,y,z...)'] = $this->categories[$index % count($this->categories)];
        $emptyRow['Price tax excluded or Price tax included'] = $this->generatePrice($index);
        $emptyRow['Tax rules ID'] = 1;
        $emptyRow['Image URLs (x,y,z...)'] = 'http://prestashop.local/img/p/1/1.jpg';
        $emptyRow['Quantity'] = $this->generateQuantity($index);
        $emptyRow['Short description'] = $this->generateDescription($index);
        $emptyRow['Description'] = $this->generateDescription($index);
        return $emptyRow;
    }

    protected function generateDescription($index)
    {
        return 'description-'.$index;
    }

    protected function generateImage($index)
    {
        return '/m/s/msj000t_1.jpg';
    }

    protected function generateName($index)
    {
        return 'Sample product with a long and meaningless name ' . $index;
    }

    protected function generateQuantity($index)
    {
        return $index % 100;
    }

    protected function generatePrice($index)
    {
        return $index % 1000;
    }

}

$numberProducts = 10;
if (isset($argv[1]) && is_numeric($argv[1])) {
    $numberProducts = (int)$argv[1];
}

$prestaShopGenerateSampleImport = new PrestaShopGenerateSampleImport();
$prestaShopGenerateSampleImport->run($numberProducts);
