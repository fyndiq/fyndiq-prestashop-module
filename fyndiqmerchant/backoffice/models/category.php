<?php

require_once('config.php');

class FmCategory {

    public static function getSubcategories($categoryId) {
        $languageId = FmConfig::get('language');

        $result = array();

        if ($categoryId === 0) {
            $categories = Category::getHomeCategories($languageId);
        } else {
            $categories= Category::getChildren($categoryId, $languageId);
        }

        foreach ($categories as $category) {
            $result[] = array(
                'id' => $category['id_category'],
                'name' => $category['name']
            );
        }
        return $result;
    }
}
