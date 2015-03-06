<?php

require_once('config.php');

class FmCategory {

    public static function get_subcategories($category_id) {
        $language_id = FmConfig::get('language');

        $result = array();

        if ($category_id === 0) {
            $categories = Category::getHomeCategories($language_id);
        } else {
            $categories= Category::getChildren($category_id, $language_id);
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
