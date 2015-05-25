<?php

class FmCategory extends FmModel
{

    const ROOT_CATEGORY_ID = 1;

    private function processCategories($categories)
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

    public function getSubcategories($categoryId)
    {
        $languageId = $this->fmConfig->get('language');
        $categoryId = $categoryId === 0 ? self::ROOT_CATEGORY_ID : $categoryId;
        return $this->fmPrestashop->categoryGetChildren($categoryId, $languageId);
    }
}
