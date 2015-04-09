<?php

require_once('config.php');

class FmCategory {

    private static function processCategories($categories) {
        $result = array();
        foreach ($categories as $category) {
            $result[] = array(
                'id' => $category['id_category'],
                'name' => $category['name']
            );
        }
        return $result;
    }

    public static function getSubcategories($categoryId) {
        $languageId = FmConfig::get('language');

        if ($categoryId === 0) {
            return self::processCategories(Category::getHomeCategories($languageId));
        }
        return self::processCategories(Category::getChildren($categoryId, $languageId));
    }
}
