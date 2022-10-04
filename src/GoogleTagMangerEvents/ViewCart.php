<?php

namespace True\GoogleTagMangerEvents;

/**
 * 	'total'=>$productsTotal,
 * 	'name'=>$productName,
 * 	'partNumber'=>$productPartNumber,
 * 	'price'=>$productPrice,
 * 	'brand'=>$productBrand,
 * 	'category'=>$productCategory,
 * 	'variant'=>$productVariant, // color or size
 * 	'quantity'=>$productQty
 * 	'discount'=>$productDiscount
 */

class ViewCart
{
	public function generate($values)
	{
		$data = [
			"currency" => "USD"
		];

		if (isset($values['price']) and !empty($values['price']))
			$data['value'] = ltrim($values['price'], "$");

		$items = [];
		$product = [];

		if (isset($values['name']) and !empty($values['name']))
			$product['item_name'] = $values['name'];

		if (isset($values['brand']) and !empty($values['brand']))
			$product['item_brand'] = $values['brand'];

		if (isset($values['category']) and !empty($values['category']))
			$product['item_category'] = $values['category'];

		if (isset($values['variant']) and !empty($values['variant']))
			$product['item_variant'] = $values['variant'];

		if (isset($values['price']) and !empty($values['price']))
			$product['price'] = ltrim($values['price'], "$");

		if (isset($values['quantity']) and !empty($values['quantity']))
			$product['quantity'] = $values['quantity'];
			
		if (isset($values['discount']) and !empty($values['discount']))
			$product['discount'] = $values['discount'];

		$items[] = $product;

		$data['items'] = $items;

		return $data;
	}
}