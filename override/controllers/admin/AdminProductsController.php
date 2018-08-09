<?php

/**
 * Created by PhpStorm.
 * User: Guzman
 * Date: 07/08/2018
 * Time: 13:01
 */
class AdminProductsController extends AdminProductsControllerCore
{
    public function processAdd()
    {
        $this->checkProduct();

        if (!empty($this->errors))
        {
            $this->display = 'add';
            return false;
        }

        $this->object = new $this->className();
        $this->_removeTaxFromEcotax();
        $this->copyFromPost($this->object, $this->table);

        if ($this->object->add())
        {
            Logger::addLog(sprintf($this->l('%s addition'), $this->className), 1, null, $this->className, (int)$this->object->id, true, (int)$this->context->employee->id);
            $this->addCarriers();
            $this->updateAccessories($this->object);
            $this->updatePackItems($this->object);
            $this->updateDownloadProduct($this->object);

            if (empty($this->errors))
            {
                $languages = Language::getLanguages(false);
                if ($this->isProductFieldUpdated('category_box') && !$this->object->updateCategories(Tools::getValue('categoryBox')))
                    $this->errors[] = Tools::displayError('An error occurred while linking the object.').' <b>'.$this->table.'</b> '.Tools::displayError('To categories');
                elseif (!$this->updateTags($languages, $this->object))
                    $this->errors[] = Tools::displayError('An error occurred while adding tags.');
                else
                {
                    Hook::exec('actionProductAdd', array('product' => $this->object));
                    if (in_array($this->object->visibility, array('both', 'search')) && Configuration::get('PS_SEARCH_INDEXATION'))
                        Search::indexation(false, $this->object->id);
                }

                // Save and preview
                if (Tools::isSubmit('submitAddProductAndPreview'))
                    $this->redirect_after = $this->getPreviewUrl($this->object);

                // Save and stay on same form
                if ($this->display == 'edit')
                    $this->redirect_after = self::$currentIndex.'&id_product='.(int)$this->object->id
                        .(Tools::getIsset('id_category') ? '&id_category='.(int)Tools::getValue('id_category') : '')
                        .'&updateproduct&conf=3&key_tab='.Tools::safeOutput(Tools::getValue('key_tab')).'&token='.$this->token;
                else
                    // Default behavior (save and back)
                    $this->redirect_after = self::$currentIndex
                        .(Tools::getIsset('id_category') ? '&id_category='.(int)Tools::getValue('id_category') : '')
                        .'&conf=3&token='.$this->token;
            }
            else
                $this->object->delete();
            // if errors : stay on edit page
            $this->display = 'edit';
        }
        else
            $this->errors[] = Tools::displayError('An error occurred while creating an object.').' <b>'.$this->table.'</b>';

        return $this->object;
    }

    public function processProductAttribute()
    {
        // Don't process if the combination fields have not been submitted
        if (!Combination::isFeatureActive() || !Tools::getValue('attribute_combination_list'))
            return;

        if (Validate::isLoadedObject($product = $this->object))
        {
            if ($this->isProductFieldUpdated('attribute_price') && (!Tools::getIsset('attribute_price') || Tools::getIsset('attribute_price') == null))
                $this->errors[] = Tools::displayError('The price attribute is required.');
            if (!Tools::getIsset('attribute_combination_list') || Tools::isEmpty(Tools::getValue('attribute_combination_list')))
                $this->errors[] = Tools::displayError('You must add at least one attribute.');

            $array_checks = array(
                'reference' => 'isReference',
                'supplier_reference' => 'isReference',
                'location' => 'isReference',
                'ean13' => 'isEan13',
                'upc' => 'isUpc',
                'wholesale_price' => 'isPrice',
                'price' => 'isPrice',
                'ecotax' => 'isPrice',
                'quantity' => 'isInt',
                'weight' => 'isUnsignedFloat',
                'unit_price_impact' => 'isPrice',
                'default_on' => 'isBool',
                'minimal_quantity' => 'isUnsignedInt',
                'maximum_quantity' => 'isUnsignedInt',
                'available_date' => 'isDateFormat'
            );
            foreach ($array_checks as $property => $check)
                if (Tools::getValue('attribute_'.$property) !== false && !call_user_func(array('Validate', $check), Tools::getValue('attribute_'.$property)))
                    $this->errors[] = sprintf(Tools::displayError('Field %s is not valid'), $property);

            if (!count($this->errors))
            {
                if (!isset($_POST['attribute_wholesale_price'])) $_POST['attribute_wholesale_price'] = 0;
                if (!isset($_POST['attribute_price_impact'])) $_POST['attribute_price_impact'] = 0;
                if (!isset($_POST['attribute_weight_impact'])) $_POST['attribute_weight_impact'] = 0;
                if (!isset($_POST['attribute_ecotax'])) $_POST['attribute_ecotax'] = 0;
                if (Tools::getValue('attribute_default'))
                    $product->deleteDefaultAttributes();

                // Change existing one
                if (($id_product_attribute = (int)Tools::getValue('id_product_attribute')) || ($id_product_attribute = $product->productAttributeExists(Tools::getValue('attribute_combination_list'), false, null, true, true)))
                {
                    if ($this->tabAccess['edit'] === '1')
                    {
                        if ($this->isProductFieldUpdated('available_date_attribute') && (Tools::getValue('available_date_attribute') != '' &&!Validate::isDateFormat(Tools::getValue('available_date_attribute'))))
                            $this->errors[] = Tools::displayError('Invalid date format.');
                        else
                        {
                            $product->updateAttribute((int)$id_product_attribute,
                                $this->isProductFieldUpdated('attribute_wholesale_price') ? Tools::getValue('attribute_wholesale_price') : null,
                                $this->isProductFieldUpdated('attribute_price_impact') ? Tools::getValue('attribute_price') * Tools::getValue('attribute_price_impact') : null,
                                $this->isProductFieldUpdated('attribute_weight_impact') ? Tools::getValue('attribute_weight') * Tools::getValue('attribute_weight_impact') : null,
                                $this->isProductFieldUpdated('attribute_unit_impact') ? Tools::getValue('attribute_unity') * Tools::getValue('attribute_unit_impact') : null,
                                $this->isProductFieldUpdated('attribute_ecotax') ? Tools::getValue('attribute_ecotax') : null,
                                Tools::getValue('id_image_attr'),
                                Tools::getValue('attribute_reference'),
                                Tools::getValue('attribute_ean13'),
                                $this->isProductFieldUpdated('attribute_default') ? Tools::getValue('attribute_default') : null,
                                Tools::getValue('attribute_location'),
                                Tools::getValue('attribute_upc'),
                                $this->isProductFieldUpdated('attribute_minimal_quantity') ? Tools::getValue('attribute_minimal_quantity') : null,
                                $this->isProductFieldUpdated('attribute_maximum_quantity') ? Tools::getValue('attribute_maximum_quantity') : null,
                                $this->isProductFieldUpdated('available_date_attribute') ? Tools::getValue('available_date_attribute') : null, false);
                            StockAvailable::setProductDependsOnStock((int)$product->id, $product->depends_on_stock, null, (int)$id_product_attribute);
                            StockAvailable::setProductOutOfStock((int)$product->id, $product->out_of_stock, null, (int)$id_product_attribute);
                        }
                    }
                    else
                        $this->errors[] = Tools::displayError('You do not have permission to add this.');
                }
                // Add new
                else
                {
                    if ($this->tabAccess['add'] === '1')
                    {
                        if ($product->productAttributeExists(Tools::getValue('attribute_combination_list')))
                            $this->errors[] = Tools::displayError('This combination already exists.');
                        else
                        {
                            $id_product_attribute = $product->addCombinationEntity(
                                Tools::getValue('attribute_wholesale_price'),
                                Tools::getValue('attribute_price') * Tools::getValue('attribute_price_impact'),
                                Tools::getValue('attribute_weight') * Tools::getValue('attribute_weight_impact'),
                                Tools::getValue('attribute_unity') * Tools::getValue('attribute_unit_impact'),
                                Tools::getValue('attribute_ecotax'),
                                0,
                                Tools::getValue('id_image_attr'),
                                Tools::getValue('attribute_reference'),
                                null,
                                Tools::getValue('attribute_ean13'),
                                Tools::getValue('attribute_default'),
                                Tools::getValue('attribute_location'),
                                Tools::getValue('attribute_upc'),
                                Tools::getValue('attribute_minimal_quantity'),
                                Tools::getValue('attribute_maximum_quantity')
                            );
                            StockAvailable::setProductDependsOnStock((int)$product->id, $product->depends_on_stock, null, (int)$id_product_attribute);
                            StockAvailable::setProductOutOfStock((int)$product->id, $product->out_of_stock, null, (int)$id_product_attribute);
                        }
                    }
                    else
                        $this->errors[] = Tools::displayError('You do not have permission to').'<hr>'.Tools::displayError('edit here.');
                }
                if (!count($this->errors))
                {
                    $combination = new Combination((int)$id_product_attribute);
                    $combination->setAttributes(Tools::getValue('attribute_combination_list'));

                    // images could be deleted before
                    $id_images = Tools::getValue('id_image_attr');
                    if (!empty($id_images))
                        $combination->setImages($id_images);

                    $product->checkDefaultAttributes();
                    if (Tools::getValue('attribute_default'))
                    {
                        Product::updateDefaultAttribute((int)$product->id);
                        if(isset($id_product_attribute))
                            $product->cache_default_attribute = (int)$id_product_attribute;
                        if ($available_date = Tools::getValue('available_date_attribute'))
                            $product->setAvailableDate($available_date);
                    }
                }
            }
        }
    }

    public function initFormAttributes($product)
    {
        $data = $this->createTemplate($this->tpl_form);
        if (!Combination::isFeatureActive())
            $this->displayWarning($this->l('This feature has been disabled. ').
                ' <a href="index.php?tab=AdminPerformance&token='.Tools::getAdminTokenLite('AdminPerformance').'#featuresDetachables">'.$this->l('Performances').'</a>');
        else if (Validate::isLoadedObject($product))
        {
            if ($this->product_exists_in_shop)
            {
                if ($product->is_virtual)
                {
                    $data->assign('product', $product);
                    $this->displayWarning($this->l('A virtual product cannot have combinations.'));
                }
                else
                {
                    $attribute_js = array();
                    $attributes = Attribute::getAttributes($this->context->language->id, true);
                    foreach ($attributes as $k => $attribute)
                        $attribute_js[$attribute['id_attribute_group']][$attribute['id_attribute']] = $attribute['name'];
                    $currency = $this->context->currency;
                    $data->assign('attributeJs', $attribute_js);
                    $data->assign('attributes_groups', AttributeGroup::getAttributesGroups($this->context->language->id));

                    $data->assign('currency', $currency);

                    $images = Image::getImages($this->context->language->id, $product->id);

                    $data->assign('tax_exclude_option', Tax::excludeTaxeOption());
                    $data->assign('ps_weight_unit', Configuration::get('PS_WEIGHT_UNIT'));

                    $data->assign('ps_use_ecotax', Configuration::get('PS_USE_ECOTAX'));
                    $data->assign('field_value_unity', $this->getFieldValue($product, 'unity'));

                    $data->assign('reasons', $reasons = StockMvtReason::getStockMvtReasons($this->context->language->id));
                    $data->assign('ps_stock_mvt_reason_default', $ps_stock_mvt_reason_default = Configuration::get('PS_STOCK_MVT_REASON_DEFAULT'));
                    $data->assign('minimal_quantity', $this->getFieldValue($product, 'minimal_quantity') ? $this->getFieldValue($product, 'minimal_quantity') : 1);
                    $data->assign('maximum_quantity', $this->getFieldValue($product, 'maximum_quantity') ? $this->getFieldValue($product, 'maximum_quantity') : 1);
                    $data->assign('available_date', ($this->getFieldValue($product, 'available_date') != 0) ? stripslashes(htmlentities($this->getFieldValue($product, 'available_date'), $this->context->language->id)) : '0000-00-00');

                    $i = 0;
                    $data->assign('imageType', ImageType::getByNameNType('small_default', 'products'));
                    $data->assign('imageWidth', (isset($image_type['width']) ? (int)($image_type['width']) : 64) + 25);
                    foreach ($images as $k => $image)
                    {
                        $images[$k]['obj'] = new Image($image['id_image']);
                        ++$i;
                    }
                    $data->assign('images', $images);

                    $data->assign($this->tpl_form_vars);
                    $data->assign(array(
                        'list' => $this->renderListAttributes($product, $currency),
                        'product' => $product,
                        'id_category' => $product->getDefaultCategory(),
                        'token_generator' => Tools::getAdminTokenLite('AdminAttributeGenerator'),
                        'combination_exists' => (Shop::isFeatureActive() && (Shop::getContextShopGroup()->share_stock) && count(AttributeGroup::getAttributesGroups($this->context->language->id)) > 0 && $product->hasAttributes())
                    ));
                }
            }
            else
                $this->displayWarning($this->l('You must save the product in this shop before adding combinations.'));
        }
        else
        {
            $data->assign('product', $product);
            $this->displayWarning($this->l('You must save this product before adding combinations.'));
        }

        $this->tpl_form_vars['custom_form'] = $data->fetch();
    }
}