<?php

class FmCategory {
    public static function get_all() {
        $result = [];

        $levels = Category::getCategories();
        foreach ($levels as $level_k => $level_v) {

            foreach ($level_v as $container) {
                $category = $container['infos'];

                $result[] = array(
                    'level' => $level_k,
                    'id' => $category['id_category'],
                    'name' => $category['name']
                );
            }
        }

        return $result;
    }
}
