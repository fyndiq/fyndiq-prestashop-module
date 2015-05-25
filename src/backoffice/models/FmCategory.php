<?php

require_once('config.php');

class FmCategory
{

    const ROOT_CATEGORY_ID = 1;

    private static function processCategories($categories)
    {
        $result = array();
        foreach ($categories as $category) {
            $result[] = array(
                'id' => $category['id_category'],
                'name' => $category['name']
            );
        }
        return $result;
    }

    public static function getSubcategories($categoryId)
    {
        $languageId = FmConfig::get('language');
        $categoryId = $categoryId === 0 ? self::ROOT_CATEGORY_ID : $categoryId;
        return self::processCategories(Category::getChildren($categoryId, $languageId));
    }
}
