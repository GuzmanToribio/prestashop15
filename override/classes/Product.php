<?php

/**
 * Created by PhpStorm.
 * User: Guzman
 * Date: 03/08/2018
 * Time: 14:54
 */
class Product extends ProductCore
{
    /** @var integer Maximum quantity for add to cart */
    public $maximum_quantity;

    public static $definition = array(
        'table' => 'product',
        'primary' => 'id_product',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => array(
            // Classic fields
            'id_shop_default' => 			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_manufacturer' => 			array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_supplier' => 				array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'reference' => 					array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 32),
            'supplier_reference' => 		array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 32),
            'location' => 					array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 64),
            'width' => 						array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'height' => 					array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'depth' => 						array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'weight' => 					array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'quantity_discount' => 			array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'ean13' => 						array('type' => self::TYPE_STRING, 'validate' => 'isEan13', 'size' => 13),
            'upc' => 						array('type' => self::TYPE_STRING, 'validate' => 'isUpc', 'size' => 12),
            'cache_is_pack' => 				array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'cache_has_attachments' => 		array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'is_virtual' => 				array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),

            /* Shop fields */
            'id_category_default' => 		array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'),
            'id_tax_rules_group' => 		array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'),
            'on_sale' => 					array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'online_only' => 				array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'ecotax' => 					array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice'),
            'minimal_quantity' => 			array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'maximum_quantity' => 			array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'price' => 						array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice', 'required' => true),
            'wholesale_price' => 			array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice'),
            'unity' => 						array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString'),
            'unit_price_ratio' => 			array('type' => self::TYPE_FLOAT, 'shop' => true),
            'additional_shipping_cost' => 	array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice'),
            'customizable' => 				array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'text_fields' => 				array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'uploadable_files' => 			array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'active' => 					array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'redirect_type' => 				array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString'),
            'id_product_redirected' => 		array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'),
            'available_for_order' => 		array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'available_date' => 			array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat'),
            'condition' => 					array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isGenericName', 'values' => array('new', 'used', 'refurbished'), 'default' => 'new'),
            'show_price' => 				array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'indexed' => 					array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'visibility' => 				array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isProductVisibility', 'values' => array('both', 'catalog', 'search', 'none'), 'default' => 'both'),
            'cache_default_attribute' => 	array('type' => self::TYPE_INT, 'shop' => true),
            'advanced_stock_management' => 	array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'date_add' => 					array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat'),
            'date_upd' => 					array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat'),

            /* Lang fields */
            'meta_description' => 			array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255),
            'meta_keywords' => 				array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255),
            'meta_title' => 				array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128),
            'link_rewrite' => 				array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 128),
            'name' => 						array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128),
            'description' => 				array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
            'description_short' => 			array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
            'available_now' => 				array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255),
            'available_later' => 			array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'IsGenericName', 'size' => 255),
        ),
        'associations' => array(
            'manufacturer' => 				array('type' => self::HAS_ONE),
            'supplier' => 					array('type' => self::HAS_ONE),
            'default_category' => 			array('type' => self::HAS_ONE, 'field' => 'id_category_default', 'object' => 'Category'),
            'tax_rules_group' => 			array('type' => self::HAS_ONE),
            'categories' =>					array('type' => self::HAS_MANY, 'field' => 'id_category', 'object' => 'Category', 'association' => 'category_product'),
            'stock_availables' =>			array('type' => self::HAS_MANY, 'field' => 'id_stock_available', 'object' => 'StockAvailable', 'association' => 'stock_availables'),
        ),
    );

    public function addProductAttribute($price, $weight, $unit_impact, $ecotax, $quantity, $id_images, $reference,
                                        $id_supplier = null, $ean13, $default, $location = null, $upc = null, $minimal_quantity = 1, $maximum_quantity)
    {
        Tools::displayAsDeprecated();

        $id_product_attribute = $this->addAttribute(
            $price, $weight, $unit_impact, $ecotax, $id_images,
            $reference, $ean13, $default, $location, $upc, $minimal_quantity, $maximum_quantity
        );

        if (!$id_product_attribute)
            return false;

        StockAvailable::setQuantity($this->id, $id_product_attribute, $quantity);
        //Try to set the default supplier reference
        $this->addSupplierReference($id_supplier, $id_product_attribute);
        return $id_product_attribute;
    }

    public function generateMultipleCombinations($combinations, $attributes)
    {
        $attributes_list = array();
        $res = true;
        $default_on = 1;
        foreach ($combinations as $key => $combination)
        {
            $id_combination = (int)$this->productAttributeExists($attributes[$key], false, null, true, true);
            $obj = new Combination($id_combination);

            if ($id_combination)
            {
                $obj->minimal_quantity = 1;
                $obj->maximum_quantity;
                $obj->available_date = '0000-00-00';
            }

            foreach ($combination as $field => $value)
                $obj->$field = $value;

            $obj->default_on = $default_on;
            $default_on = 0;

            $obj->save();

            if (!$id_combination)
            {
                $attribute_list = array();
                foreach ($attributes[$key] as $id_attribute)
                    $attribute_list[] = array(
                        'id_product_attribute' => (int)$obj->id,
                        'id_attribute' => (int)$id_attribute
                    );
                $res &= Db::getInstance()->insert('product_attribute_combination', $attribute_list);
            }
        }

        return $res;
    }

    public function addCombinationEntity($wholesale_price, $price, $weight, $unit_impact, $ecotax, $quantity,
                                         $id_images, $reference, $id_supplier, $ean13, $default, $location = null, $upc = null, $minimal_quantity = 1, $maximum_quantity,  array $id_shop_list = array())
    {
        $id_product_attribute = $this->addAttribute(
            $price, $weight, $unit_impact, $ecotax, $id_images,
            $reference, $ean13, $default, $location, $upc, $minimal_quantity, $maximum_quantity, $id_shop_list);

        $this->addSupplierReference($id_supplier, $id_product_attribute);
        $result = ObjectModel::updateMultishopTable('Combination', array(
            'wholesale_price' => (float)$wholesale_price,
        ), 'a.id_product_attribute = '.(int)$id_product_attribute);

        if (!$id_product_attribute || !$result)
            return false;

        return $id_product_attribute;
    }

    /**
     * Update a product attribute
     *
     * @param integer $id_product_attribute Product attribute id
     * @param float $wholesale_price Wholesale price
     * @param float $price Additional price
     * @param float $weight Additional weight
     * @param float $unit
     * @param float $ecotax Additional ecotax
     * @param integer $id_image Image id
     * @param string $reference Reference
     * @param string $ean13 Ean-13 barcode
     * @param int $default Default On
     * @param string $upc Upc barcode
     * @param string $minimal_quantity Minimal quantity
     * @param string $maximum_quantity Maximum quantity
     * @return array Update result
     */
    public function updateProductAttribute($id_product_attribute, $wholesale_price, $price, $weight, $unit, $ecotax,
                                           $id_images, $reference, $id_supplier = null, $ean13, $default, $location = null, $upc = null, $minimal_quantity, $maximum_quantity, $available_date)
    {
        Tools::displayAsDeprecated();

        $return = $this->updateAttribute(
            $id_product_attribute, $wholesale_price, $price, $weight, $unit, $ecotax,
            $id_images, $reference, $ean13, $default, $location = null, $upc = null, $minimal_quantity, $maximum_quantity, $available_date
        );
        $this->addSupplierReference($id_supplier, $id_product_attribute);

        return $return;
    }

    public function updateAttribute($id_product_attribute, $wholesale_price, $price, $weight, $unit, $ecotax,
                                    $id_images, $reference, $ean13, $default, $location = null, $upc = null, $minimal_quantity = null, $maximum_quantity = null, $available_date = null, $update_all_fields = true, array $id_shop_list = array())
    {
        $combination = new Combination($id_product_attribute);

        if (!$update_all_fields)
            $combination->setFieldsToUpdate(array(
                'price' => !is_null($price),
                'wholesale_price' => !is_null($wholesale_price),
                'ecotax' => !is_null($ecotax),
                'weight' => !is_null($weight),
                'unit_price_impact' => !is_null($unit),
                'default_on' => !is_null($default),
                'minimal_quantity' => !is_null($minimal_quantity),
                'maximum_quantity' => !is_null($maximum_quantity),
                'available_date' => !is_null($available_date),
            ));

        $price = str_replace(',', '.', $price);
        $weight = str_replace(',', '.', $weight);

        $combination->price = (float)$price;
        $combination->wholesale_price = (float)$wholesale_price;
        $combination->ecotax = (float)$ecotax;
        $combination->weight = (float)$weight;
        $combination->unit_price_impact = (float)$unit;
        $combination->reference = pSQL($reference);
        $combination->location = pSQL($location);
        $combination->ean13 = pSQL($ean13);
        $combination->upc = pSQL($upc);
        $combination->default_on = (int)$default;
        $combination->minimal_quantity = (int)$minimal_quantity;
        $combination->maximum_quantity = (int)$maximum_quantity;
        $combination->available_date = $available_date ? pSQL($available_date) : '0000-00-00';

        if (count($id_shop_list))
            $combination->id_shop_list = $id_shop_list;

        $combination->save();

        if (!empty($id_images))
            $combination->setImages($id_images);

        Product::updateDefaultAttribute($this->id);

        Hook::exec('actionProductAttributeUpdate', array('id_product_attribute' => $id_product_attribute));

        return true;
    }

    public function addAttribute($price, $weight, $unit_impact, $ecotax, $id_images, $reference, $ean13,
                                 $default, $location = null, $upc = null, $minimal_quantity = 1, $maximum_quantity, array $id_shop_list = array())
    {
        if (!$this->id)
            return;

        $price = str_replace(',', '.', $price);
        $weight = str_replace(',', '.', $weight);

        $combination = new Combination();
        $combination->id_product = (int)$this->id;
        $combination->price = (float)$price;
        $combination->ecotax = (float)$ecotax;
        $combination->quantity = 0;
        $combination->weight = (float)$weight;
        $combination->unit_price_impact = (float)$unit_impact;
        $combination->reference = pSQL($reference);
        $combination->location = pSQL($location);
        $combination->ean13 = pSQL($ean13);
        $combination->upc = pSQL($upc);
        $combination->default_on = (int)$default;
        $combination->minimal_quantity = (int)$minimal_quantity;
        $combination->maximum_quantity = (int)$maximum_quantity;

        // if we add a combination for this shop and this product does not use the combination feature in other shop,
        // we clone the default combination in every shop linked to this product
        if ($default && !$this->hasAttributesInOtherShops())
        {
            $id_shop_list_array = Product::getShopsByProduct($this->id);
            foreach ($id_shop_list_array as $array_shop)
                $id_shop_list[] = $array_shop['id_shop'];
            $id_shop_list = array_unique($id_shop_list);
        }

        if (count($id_shop_list))
            $combination->id_shop_list = array_unique($id_shop_list);

        $combination->add();

        if (!$combination->id)
            return false;

        Product::updateDefaultAttribute($this->id);

        if (!empty($id_images))
            $combination->setImages($id_images);

        return (int)$combination->id;
    }

    public function getAttributesGroups($id_lang)
    {
        if (!Combination::isFeatureActive())
            return array();
        $sql = 'SELECT ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, agl.`public_name` AS public_group_name,
					a.`id_attribute`, al.`name` AS attribute_name, a.`color` AS attribute_color, product_attribute_shop.`id_product_attribute`,
					IFNULL(stock.quantity, 0) as quantity, product_attribute_shop.`price`, product_attribute_shop.`ecotax`, product_attribute_shop.`weight`,
					product_attribute_shop.`default_on`, pa.`reference`, product_attribute_shop.`unit_price_impact`,
					product_attribute_shop.`minimal_quantity`, product_attribute_shop.`maximum_quantity`, product_attribute_shop.`available_date`, ag.`group_type`
				FROM `'._DB_PREFIX_.'product_attribute` pa
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				'.Product::sqlStock('pa', 'pa').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
				LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group`)
				'.Shop::addSqlAssociation('attribute', 'a').'
				WHERE pa.`id_product` = '.(int)$this->id.'
					AND al.`id_lang` = '.(int)$id_lang.'
					AND agl.`id_lang` = '.(int)$id_lang.'
				GROUP BY id_attribute_group, id_product_attribute
				ORDER BY ag.`position` ASC, a.`position` ASC, agl.`name` ASC';
        return Db::getInstance()->executeS($sql);
    }
}