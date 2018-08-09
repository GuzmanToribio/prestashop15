<?php

/**
 * Created by PhpStorm.
 * User: Guzman
 * Date: 07/08/2018
 * Time: 14:51
 */
class Combination extends CombinationCore
{
    public $maximum_quantity;

    public static $definition = array(
        'table' => 'product_attribute',
        'primary' => 'id_product_attribute',
        'fields' => array(
            'id_product' => 		array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'location' => 			array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64),
            'ean13' => 				array('type' => self::TYPE_STRING, 'validate' => 'isEan13', 'size' => 13),
            'upc' => 				array('type' => self::TYPE_STRING, 'validate' => 'isUpc', 'size' => 12),
            'quantity' => 			array('type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 10),
            'reference' => 			array('type' => self::TYPE_STRING, 'size' => 32),
            'supplier_reference' => array('type' => self::TYPE_STRING, 'size' => 32),

            /* Shop fields */
            'wholesale_price' =>	array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice', 'size' => 27),
            'price' => 				array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isNegativePrice', 'size' => 20),
            'ecotax' => 			array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice', 'size' => 20),
            'weight' => 			array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isFloat'),
            'unit_price_impact' => 	array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isNegativePrice', 'size' => 20),
            'minimal_quantity' => 	array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId', 'required' => true),
            'maximum_quantity' => 	array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId', 'required' => true),
            'default_on' => 		array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isBool'),
            'available_date' => 	array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat'),
        ),
    );
}