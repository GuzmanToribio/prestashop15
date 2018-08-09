<?php

/**
 * Created by PhpStorm.
 * User: Guzman
 * Date: 06/08/2018
 * Time: 11:31
 */
class Attribute extends AttributeCore
{
    /**
     * Get maximum quantity for product with attributes quantity
     *
     * @acces public static
     * @param integer $id_product_attribute
     * @return mixed Maximum Quantity or false
     */
    public static function getAttributeMaximumQty($id_product_attribute)
    {
        $maximum_quantity = Db::getInstance()->getValue('
			SELECT `maximum_quantity`
			FROM `'._DB_PREFIX_.'product_attribute_shop` pas
			WHERE `id_shop` = '.(int)Context::getContext()->shop->id.'
			AND `id_product_attribute` = '.(int)$id_product_attribute
        );

        if ($maximum_quantity > 1)
            return (int)$maximum_quantity;

        return false;
    }
}