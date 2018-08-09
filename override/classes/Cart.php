<?php

/**
 * Created by PhpStorm.
 * User: Guzman
 * Date: 06/08/2018
 * Time: 12:47
 */
class Cart extends CartCore
{
    public function getProducts($refresh = false, $id_product = false, $id_country = null)
    {
        if (!$this->id)
            return array();
        // Product cache must be strictly compared to NULL, or else an empty cart will add dozens of queries
        if ($this->_products !== null && !$refresh)
        {
            // Return product row with specified ID if it exists
            if (is_int($id_product))
            {
                foreach ($this->_products as $product)
                    if ($product['id_product'] == $id_product)
                        return array($product);
                return array();
            }
            return $this->_products;
        }

        // Build query
        $sql = new DbQuery();

        // Build SELECT
        $sql->select('cp.`id_product_attribute`, cp.`id_product`, cp.`quantity` AS cart_quantity, cp.id_shop, pl.`name`, p.`is_virtual`,
						pl.`description_short`, pl.`available_now`, pl.`available_later`, p.`id_product`, product_shop.`id_category_default`, p.`id_supplier`,
						p.`id_manufacturer`, product_shop.`on_sale`, product_shop.`ecotax`, product_shop.`additional_shipping_cost`,
						product_shop.`available_for_order`, product_shop.`price`, product_shop.`active`, product_shop.`unity`, product_shop.`unit_price_ratio`,
						stock.`quantity` AS quantity_available, p.`width`, p.`height`, p.`depth`, stock.`out_of_stock`, p.`weight`,
						p.`date_add`, p.`date_upd`, IFNULL(stock.quantity, 0) as quantity, pl.`link_rewrite`, cl.`link_rewrite` AS category,
						CONCAT(LPAD(cp.`id_product`, 10, 0), LPAD(IFNULL(cp.`id_product_attribute`, 0), 10, 0), IFNULL(cp.`id_address_delivery`, 0)) AS unique_id, cp.id_address_delivery,
						product_shop.`wholesale_price`, product_shop.advanced_stock_management, ps.product_supplier_reference supplier_reference');

        // Build FROM
        $sql->from('cart_product', 'cp');

        // Build JOIN
        $sql->leftJoin('product', 'p', 'p.`id_product` = cp.`id_product`');
        $sql->innerJoin('product_shop', 'product_shop', '(product_shop.id_shop=cp.id_shop AND product_shop.id_product = p.id_product)');
        $sql->leftJoin('product_lang', 'pl', '
			p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = '.(int)$this->id_lang.Shop::addSqlRestrictionOnLang('pl', 'cp.id_shop')
        );

        $sql->leftJoin('category_lang', 'cl', '
			product_shop.`id_category_default` = cl.`id_category`
			AND cl.`id_lang` = '.(int)$this->id_lang.Shop::addSqlRestrictionOnLang('cl', 'cp.id_shop')
        );

        $sql->leftJoin('product_supplier', 'ps', 'ps.id_product=cp.id_product AND ps.id_product_attribute=cp.id_product_attribute AND ps.id_supplier=p.id_supplier');

        // @todo test if everything is ok, then refactorise call of this method
        $sql->join(Product::sqlStock('cp', 'cp'));

        // Build WHERE clauses
        $sql->where('cp.`id_cart` = '.(int)$this->id);
        if ($id_product)
            $sql->where('cp.`id_product` = '.(int)$id_product);
        $sql->where('p.`id_product` IS NOT NULL');

        // Build GROUP BY
        $sql->groupBy('unique_id');

        // Build ORDER BY
        $sql->orderBy('p.id_product, cp.id_product_attribute, cp.date_add ASC');

        if (Customization::isFeatureActive())
        {
            $sql->select('cu.`id_customization`, cu.`quantity` AS customization_quantity');
            $sql->leftJoin('customization', 'cu',
                'p.`id_product` = cu.`id_product` AND cp.`id_product_attribute` = cu.id_product_attribute AND cu.id_cart='.(int)$this->id);
        }
        else
            $sql->select('NULL AS customization_quantity, NULL AS id_customization');

        if (Combination::isFeatureActive())
        {
            $sql->select('
				product_attribute_shop.`price` AS price_attribute, product_attribute_shop.`ecotax` AS ecotax_attr,
				IF (IFNULL(pa.`reference`, \'\') = \'\', p.`reference`, pa.`reference`) AS reference,
				(p.`weight`+ pa.`weight`) weight_attribute,
				IF (IFNULL(pa.`ean13`, \'\') = \'\', p.`ean13`, pa.`ean13`) AS ean13,
				IF (IFNULL(pa.`upc`, \'\') = \'\', p.`upc`, pa.`upc`) AS upc,
				pai.`id_image` as pai_id_image, il.`legend` as pai_legend,
				IFNULL(product_attribute_shop.`minimal_quantity`, product_shop.`minimal_quantity`) as minimal_quantity,
				IFNULL(product_attribute_shop.`maximum_quantity`, product_shop.`maximum_quantity`) as maximum_quantity
			');

            $sql->leftJoin('product_attribute', 'pa', 'pa.`id_product_attribute` = cp.`id_product_attribute`');
            $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', '(product_attribute_shop.id_shop=cp.id_shop AND product_attribute_shop.id_product_attribute = pa.id_product_attribute)');
            $sql->leftJoin('product_attribute_image', 'pai', 'pai.`id_product_attribute` = pa.`id_product_attribute`');
            $sql->leftJoin('image_lang', 'il', 'il.id_image = pai.id_image AND il.id_lang = '.(int)$this->id_lang);
        }
        else
            $sql->select(
                'p.`reference` AS reference, p.`ean13`,
				p.`upc` AS upc, product_shop.`minimal_quantity` AS minimal_quantity,
				p.`upc` AS upc, product_shop.`maximum_quantity` AS maximum_quantity'
            );
        $result = Db::getInstance()->executeS($sql);

        // Reset the cache before the following return, or else an empty cart will add dozens of queries
        $products_ids = array();
        $pa_ids = array();
        if ($result)
            foreach ($result as $row)
            {
                $products_ids[] = $row['id_product'];
                $pa_ids[] = $row['id_product_attribute'];
            }
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        Product::cacheProductsFeatures($products_ids);
        Cart::cacheSomeAttributesLists($pa_ids, $this->id_lang);

        $this->_products = array();
        if (empty($result))
            return array();

        $cart_shop_context = Context::getContext()->cloneContext();
        foreach ($result as &$row)
        {
            if (isset($row['ecotax_attr']) && $row['ecotax_attr'] > 0)
                $row['ecotax'] = (float)$row['ecotax_attr'];

            $row['stock_quantity'] = (int)$row['quantity'];
            // for compatibility with 1.2 themes
            $row['quantity'] = (int)$row['cart_quantity'];

            if (isset($row['id_product_attribute']) && (int)$row['id_product_attribute'] && isset($row['weight_attribute']))
                $row['weight'] = (float)$row['weight_attribute'];

            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice')
                $address_id = (int)$this->id_address_invoice;
            else
                $address_id = (int)$row['id_address_delivery'];
            if (!Address::addressExists($address_id))
                $address_id = null;

            if ($cart_shop_context->shop->id != $row['id_shop'])
                $cart_shop_context->shop = new Shop((int)$row['id_shop']);

            if ($this->_taxCalculationMethod == PS_TAX_EXC)
            {
                $row['price'] = Product::getPriceStatic(
                    (int)$row['id_product'],
                    false,
                    isset($row['id_product_attribute']) ? (int)$row['id_product_attribute'] : null,
                    2,
                    null,
                    false,
                    true,
                    (int)$row['cart_quantity'],
                    false,
                    ((int)$this->id_customer ? (int)$this->id_customer : null),
                    (int)$this->id,
                    ((int)$address_id ? (int)$address_id : null),
                    $specific_price_output,
                    true,
                    true,
                    $cart_shop_context
                ); // Here taxes are computed only once the quantity has been applied to the product price

                $row['price_wt'] = Product::getPriceStatic(
                    (int)$row['id_product'],
                    true,
                    isset($row['id_product_attribute']) ? (int)$row['id_product_attribute'] : null,
                    2,
                    null,
                    false,
                    true,
                    (int)$row['cart_quantity'],
                    false,
                    ((int)$this->id_customer ? (int)$this->id_customer : null),
                    (int)$this->id,
                    ((int)$address_id ? (int)$address_id : null),
                    $null,
                    true,
                    true,
                    $cart_shop_context
                );

                $tax_rate = Tax::getProductTaxRate((int)$row['id_product'], (int)$address_id);

                $row['total_wt'] = Tools::ps_round($row['price'] * (float)$row['cart_quantity'] * (1 + (float)$tax_rate / 100), 2);
                $row['total'] = $row['price'] * (int)$row['cart_quantity'];
            }
            else
            {
                $row['price'] = Product::getPriceStatic(
                    (int)$row['id_product'],
                    false,
                    (int)$row['id_product_attribute'],
                    2,
                    null,
                    false,
                    true,
                    $row['cart_quantity'],
                    false,
                    ((int)$this->id_customer ? (int)$this->id_customer : null),
                    (int)$this->id,
                    ((int)$address_id ? (int)$address_id : null),
                    $specific_price_output,
                    true,
                    true,
                    $cart_shop_context
                );

                $row['price_wt'] = Product::getPriceStatic(
                    (int)$row['id_product'],
                    true,
                    (int)$row['id_product_attribute'],
                    2,
                    null,
                    false,
                    true,
                    $row['cart_quantity'],
                    false,
                    ((int)$this->id_customer ? (int)$this->id_customer : null),
                    (int)$this->id,
                    ((int)$address_id ? (int)$address_id : null),
                    $null,
                    true,
                    true,
                    $cart_shop_context
                );

                // In case when you use QuantityDiscount, getPriceStatic() can be return more of 2 decimals
                $row['price_wt'] = Tools::ps_round($row['price_wt'], 2);
                $row['total_wt'] = $row['price_wt'] * (int)$row['cart_quantity'];
                $row['total'] = Tools::ps_round($row['price'] * (int)$row['cart_quantity'], 2);
            }

            if (!isset($row['pai_id_image']) || $row['pai_id_image'] == 0)
            {
                $row2 = Db::getInstance()->getRow('
					SELECT image_shop.`id_image` id_image, il.`legend`
					FROM `'._DB_PREFIX_.'image` i
					JOIN `'._DB_PREFIX_.'image_shop` image_shop ON (i.id_image = image_shop.id_image AND image_shop.cover=1 AND image_shop.id_shop='.(int)$row['id_shop'].')
					LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$this->id_lang.')
					WHERE i.`id_product` = '.(int)$row['id_product'].' AND image_shop.`cover` = 1'
                );

                if (!$row2)
                    $row2 = array('id_image' => false, 'legend' => false);
                else
                    $row = array_merge($row, $row2);
            }
            else
            {
                $row['id_image'] = $row['pai_id_image'];
                $row['legend'] = $row['pai_legend'];
            }

            $row['reduction_applies'] = ($specific_price_output && (float)$specific_price_output['reduction']);
            $row['quantity_discount_applies'] = ($specific_price_output && $row['cart_quantity'] >= (int)$specific_price_output['from_quantity']);
            $row['id_image'] = Product::defineProductImage($row, $this->id_lang);
            $row['allow_oosp'] = Product::isAvailableWhenOutOfStock($row['out_of_stock']);
            $row['features'] = Product::getFeaturesStatic((int)$row['id_product']);

            if (array_key_exists($row['id_product_attribute'].'-'.$this->id_lang, self::$_attributesLists))
                $row = array_merge($row, self::$_attributesLists[$row['id_product_attribute'].'-'.$this->id_lang]);

            $row = Product::getTaxesInformations($row, $cart_shop_context);

            $this->_products[] = $row;
        }

        return $this->_products;
    }

    public function updateQty($quantity, $id_product, $id_product_attribute = null, $id_customization = false,
                              $operator = 'up', $id_address_delivery = 0, Shop $shop = null, $auto_add_cart_rule = true)
    {
        if (!$shop)
            $shop = Context::getContext()->shop;

        if (Context::getContext()->customer->id)
        {
            if ($id_address_delivery == 0 && (int)$this->id_address_delivery) // The $id_address_delivery is null, use the cart delivery address
                $id_address_delivery = $this->id_address_delivery;
            elseif ($id_address_delivery == 0) // The $id_address_delivery is null, get the default customer address
                $id_address_delivery = (int)Address::getFirstCustomerAddressId((int)Context::getContext()->customer->id);
            elseif (!Customer::customerHasAddress(Context::getContext()->customer->id, $id_address_delivery)) // The $id_address_delivery must be linked with customer
                $id_address_delivery = 0;
        }

        $quantity = (int)$quantity;
        $id_product = (int)$id_product;
        $id_product_attribute = (int)$id_product_attribute;
        $product = new Product($id_product, false, Configuration::get('PS_LANG_DEFAULT'), $shop->id);

        if ($id_product_attribute)
        {
            $combination = new Combination((int)$id_product_attribute);
            if ($combination->id_product != $id_product)
                return false;
        }

        /* If we have a product combination, the minimal quantity is set with the one of this combination */
        if (!empty($id_product_attribute)) {
            $minimal_quantity = (int)Attribute::getAttributeMinimalQty($id_product_attribute);
            $maximum_quantity = (int)Attribute::getAttributeMaximumQty($id_product_attribute, $quantity);
        } else {
            $minimal_quantity = (int)$product->minimal_quantity;
            $maximum_quantity = (int)$product->maximum_quantity;
        }

        if (!Validate::isLoadedObject($product))
            die(Tools::displayError());

        if (isset(self::$_nbProducts[$this->id]))
            unset(self::$_nbProducts[$this->id]);

        if (isset(self::$_totalWeight[$this->id]))
            unset(self::$_totalWeight[$this->id]);

        if ((int)$quantity <= 0)
            return $this->deleteProduct($id_product, $id_product_attribute, (int)$id_customization);
        elseif (!$product->available_for_order || Configuration::get('PS_CATALOG_MODE'))
            return false;
        else
        {
            /* Check if the product is already in the cart */
            $result = $this->containsProduct($id_product, $id_product_attribute, (int)$id_customization, (int)$id_address_delivery);

            /* Update quantity if product already exist */
            if ($result)
            {
                if ($operator == 'up')
                {
                    $sql = 'SELECT stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity
							FROM '._DB_PREFIX_.'product p
							'.Product::sqlStock('p', $id_product_attribute, true, $shop).'
							WHERE p.id_product = '.$id_product;

                    $result2 = Db::getInstance()->getRow($sql);
                    $product_qty = (int)$result2['quantity'];
                    // Quantity for product pack
                    if (Pack::isPack($id_product))
                        $product_qty = Pack::getQuantity($id_product, $id_product_attribute);
                    $new_qty = (int)$result['quantity'] + (int)$quantity;
                    $qty = '+ '.(int)$quantity;

                    if (!Product::isAvailableWhenOutOfStock((int)$result2['out_of_stock']))
                        if ($new_qty > $product_qty)
                            return false;
                }
                else if ($operator == 'down')
                {
                    $qty = '- '.(int)$quantity;
                    $new_qty = (int)$result['quantity'] - (int)$quantity;
                    if ($new_qty < $minimal_quantity && $minimal_quantity > 1)
                        return -1;
                    if ($new_qty > $maximum_quantity && $maximum_quantity > 1)
                        return -2;
                }
                else
                    return false;

                /* Delete product from cart */
                if ($new_qty <= 0)
                    return $this->deleteProduct((int)$id_product, (int)$id_product_attribute, (int)$id_customization);
                else if ($new_qty < $minimal_quantity)
                    return -1;
                else if ($new_qty > $maximum_quantity)
                    return -2;
                else
                    Db::getInstance()->execute('
						UPDATE `'._DB_PREFIX_.'cart_product`
						SET `quantity` = `quantity` '.$qty.', `date_add` = NOW()
						WHERE `id_product` = '.(int)$id_product.
                        (!empty($id_product_attribute) ? ' AND `id_product_attribute` = '.(int)$id_product_attribute : '').'
						AND `id_cart` = '.(int)$this->id.(Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery() ? ' AND `id_address_delivery` = '.(int)$id_address_delivery : '').'
						LIMIT 1'
                    );
            }
            /* Add product to the cart */
            elseif ($operator == 'up')
            {
                $sql = 'SELECT stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity
						FROM '._DB_PREFIX_.'product p
						'.Product::sqlStock('p', $id_product_attribute, true, $shop).'
						WHERE p.id_product = '.$id_product;

                $result2 = Db::getInstance()->getRow($sql);

                // Quantity for product pack
                if (Pack::isPack($id_product))
                    $result2['quantity'] = Pack::getQuantity($id_product, $id_product_attribute);

                if (!Product::isAvailableWhenOutOfStock((int)$result2['out_of_stock']))
                    if ((int)$quantity > $result2['quantity'])
                        return false;

                if ((int)$quantity < $minimal_quantity)
                    return -1;
                if ((int)$quantity > $maximum_quantity)
                    return -2;

                $result_add = Db::getInstance()->insert('cart_product', array(
                    'id_product' => 			(int)$id_product,
                    'id_product_attribute' => 	(int)$id_product_attribute,
                    'id_cart' => 				(int)$this->id,
                    'id_address_delivery' => 	(int)$id_address_delivery,
                    'id_shop' => 				$shop->id,
                    'quantity' => 				(int)$quantity,
                    'date_add' => 				date('Y-m-d H:i:s')
                ));

                if (!$result_add)
                    return false;
            }
        }

        // refresh cache of self::_products
        $this->_products = $this->getProducts(true);
        $this->update(true);
        $context = Context::getContext()->cloneContext();
        $context->cart = $this;
        Cache::clean('getContextualValue_*');
        if ($auto_add_cart_rule)
            CartRule::autoAddToCart($context);

        if ($product->customizable)
            return $this->_updateCustomizationQuantity((int)$quantity, (int)$id_customization, (int)$id_product, (int)$id_product_attribute, (int)$id_address_delivery, $operator);
        else
            return true;
    }
}