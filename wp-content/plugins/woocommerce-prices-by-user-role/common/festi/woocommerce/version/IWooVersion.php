<?php

interface IWooVersion
{
    public function getPricesFromVariationProduct($product);
    public function getType($product);
    public function getVariationID($product);
    public function isPostParent($product);
    public function _isChildProduct($product);
    public function getPostParent($product);
    public function getParentID($product);
    public function getID($product);
    public function getPriceExcludingTax($product, $args);
    public function getPriceIncludingTax($product, $args);
    public function getHook($hook);
    
}
