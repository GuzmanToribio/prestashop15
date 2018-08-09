<?php

/**
 * Created by PhpStorm.
 * User: Guzman
 * Date: 06/08/2018
 * Time: 11:42
 */
class CartController extends CartControllerCore
{
    protected function processChangeProductInCart()
    {
        $mode = (Tools::getIsset('update') && $this->id_product) ? 'update' : 'add';

        if ($this->qty == 0)
            $this->errors[] = Tools::displayError('Null quantity.');
        elseif (!$this->id_product)
            $this->errors[] = Tools::displayError('Product not found');

        $product = new Product($this->id_product, true, $this->context->language->id);
        if (!$product->id || !$product->active)
        {
            $this->errors[] = Tools::displayError('This product is no longer available.', false);
            return;
        }

        $qty_to_check = $this->qty;
        $cart_products = $this->context->cart->getProducts();

        if (is_array($cart_products)) {
            foreach ($cart_products as $cart_product) {
                if ((!isset($this->id_product_attribute) || $cart_product['id_product_attribute'] == $this->id_product_attribute) &&
                    (isset($this->id_product) && $cart_product['id_product'] == $this->id_product)
                ) {
                    $qty_to_check = $cart_product['cart_quantity'];

                    if (Tools::getValue('op', 'up') == 'down') {
                        $qty_to_check -= $this->qty;
                    } else {
                        $qty_to_check += $this->qty;
                    }
                    break;
                }
            }
            $maximum_quantity = ($this->id_product_attribute) ? Attribute::getAttributeMaximumQty($this->id_product_attribute) : $product->maximum_quantity;
            if($qty_to_check > $maximum_quantity) {
                $this->errors[] = sprintf(Tools::displayError('You must add %d maximum quantity', false), $maximum_quantity);
            }
        }

        // Check product quantity availability
        if ($this->id_product_attribute)
        {
            if (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty($this->id_product_attribute, $qty_to_check))
                $this->errors[] = Tools::displayError('There isn\'t enough product in stock.');
        }
        elseif ($product->hasAttributes())
        {
            $minimumQuantity = ($product->out_of_stock == 2) ? !Configuration::get('PS_ORDER_OUT_OF_STOCK') : !$product->out_of_stock;
            $this->id_product_attribute = Product::getDefaultAttribute($product->id, $minimumQuantity);
            // @todo do something better than a redirect admin !!
            if (!$this->id_product_attribute)
                Tools::redirectAdmin($this->context->link->getProductLink($product));
            elseif (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty($this->id_product_attribute, $qty_to_check))
                $this->errors[] = Tools::displayError('There isn\'t enough product in stock.');
        }
        elseif (!$product->checkQty($qty_to_check))
            $this->errors[] = Tools::displayError('There isn\'t enough product in stock.');

        // If no errors, process product addition
        if (!$this->errors && $mode == 'add')
        {
            // Add cart if no cart found
            if (!$this->context->cart->id)
            {
                if (Context::getContext()->cookie->id_guest)
                {
                    $guest = new Guest(Context::getContext()->cookie->id_guest);
                    $this->context->cart->mobile_theme = $guest->mobile_theme;
                }
                $this->context->cart->add();
                if ($this->context->cart->id)
                    $this->context->cookie->id_cart = (int)$this->context->cart->id;
            }

            // Check customizable fields
            if (!$product->hasAllRequiredCustomizableFields() && !$this->customization_id)
                $this->errors[] = Tools::displayError('Please fill in all of the required fields, and then save your customizations.');

            if (!$this->errors)
            {
                $cart_rules = $this->context->cart->getCartRules();
                $update_quantity = $this->context->cart->updateQty($this->qty, $this->id_product, $this->id_product_attribute, $this->customization_id, Tools::getValue('op', 'up'), $this->id_address_delivery);
                if ($update_quantity < 0)
                {
                    if($update_quantity == -1) {
                        // If product has attribute, minimal quantity is set with minimal quantity of attribute
                        $minimal_quantity = ($this->id_product_attribute) ? Attribute::getAttributeMinimalQty($this->id_product_attribute) : $product->minimal_quantity;
                        $this->errors[] = sprintf(Tools::displayError('You must add %d minimum quantity', false), $minimal_quantity);
                    } elseif($update_quantity == -2) {
                        // If product has attribute, maximum quantity is set with minimal quantity of attribute
                        $maximum_quantity = ($this->id_product_attribute) ? Attribute::getAttributeMaximumQty($this->id_product_attribute) : $product->maximum_quantity;
                        $this->errors[] = sprintf(Tools::displayError('You must add %d maximum quantity', false), $maximum_quantity);
                    }
                }
                elseif (!$update_quantity)
                    $this->errors[] = Tools::displayError('You already have the maximum quantity available for this product.', false);
                elseif ((int)Tools::getValue('allow_refresh'))
                {
                    // If the cart rules has changed, we need to refresh the whole cart
                    $cart_rules2 = $this->context->cart->getCartRules();
                    if (count($cart_rules2) != count($cart_rules))
                        $this->ajax_refresh = true;
                    else
                    {
                        $rule_list = array();
                        foreach ($cart_rules2 as $rule)
                            $rule_list[] = $rule['id_cart_rule'];
                        foreach ($cart_rules as $rule)
                            if (!in_array($rule['id_cart_rule'], $rule_list))
                            {
                                $this->ajax_refresh = true;
                                break;
                            }
                    }
                }
            }
        }

        $removed = CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();
        if (count($removed) && (int)Tools::getValue('allow_refresh'))
            $this->ajax_refresh = true;
    }
}