<?php

require_once('config.php');

class FmCategory {

    public static function getSubcategories($categoryId) {
        $language_id = FmConfig::get('language');

        $result = array();

        if ($categoryId === 0) {
            $categories = Category::getHomeCategories($language_id);
        } else {
            $categories= Category::getChildren($categoryId, $language_id);
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
