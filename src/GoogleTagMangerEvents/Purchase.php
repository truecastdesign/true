<?php

namespace True\GoogleTagMangerEvents;

/**
 * Values: 
 * orderNumber = order number
 * total = purchase total
 * source = Website or store it was bought at
 * coupon = coupon code used
 * shipping = 1.00 
 * tax = 1.00
 * items = Array of items
 * 
 * Items array:
 * partNumber = part number
 * name = item name
 * coupon = coupon code used on this item
 * discount = 1.00
 * brand = item manufacturer
 * category = String of category hierarchy: Apparel > Adult > Shirts > Short Sleeves
 * variant = Color, Size, etc
 * price = 1.00
 * quantity = 1
 */

class Purchase
{
	public function generate($values)
	{
		$data = ["currency" => "USD"];

		if (isset($values['currency']) and !empty($values['currency']))
			$data['currency'] = $values['currency'];

		if (isset($values['total']) and !empty($values['total']))
			$data['value'] = (float) ltrim($values['total'], "$");
		else
			throw new \Exception("The total is required!");

		if (isset($values['orderNumber']) and !empty($values['orderNumber']))
			$data['transaction_id'] = $values['orderNumber'];
		else
			throw new \Exception("The order number is required!");

		if (isset($values['source']) and !empty($values['source']))
			$data['affiliation'] = $values['source'];
			
		if (isset($values['coupon']) and !empty($values['coupon']))
			$data['coupon'] = $values['coupon'];

		if (isset($values['shipping']) and !empty($values['shipping']))
			$data['shipping'] = (float) $values['shipping'];

		if (isset($values['tax']) and !empty($values['tax']))
			$data['tax'] = (float) $values['tax'];

		$items = [];

		if (!isset($values['items']) or !is_array($values['items']))
			throw new \Exception("The items array is required!");

		$j = 0;
		foreach ($values['items'] as $item) {
			$product = [];

			$product['index'] = $j;
			
			if (isset($item['name']) and !empty($item['name']))
				$product['item_name'] = $item['name'];

			if (isset($item['partNumber']) and !empty($item['partNumber']))
				$product['item_id'] = $item['partNumber'];

			if (isset($item['coupon']) and !empty($item['coupon']))
				$product['coupon'] = $item['coupon'];

			if (isset($item['discount']) and !empty($item['discount']))
				$product['discount'] = (float) ltrim($item['discount'], "$");

			if (isset($item['brand']) and !empty($item['brand']))
				$product['item_brand'] = $item['brand'];

			if (isset($item['variant']) and !empty($item['variant']))
				$product['item_variant'] = $item['variant'];

			if (isset($item['price']) and !empty($item['price']))
				$product['price'] = (float) ltrim($item['price'], "$");

			if (isset($item['quantity']) and !empty($item['quantity'])) {
				if (!is_numeric($item['quantity']))
					throw new \Exception("Product quantity is not an integer!");
				$product['quantity'] = (int) $item['quantity'];
			}
				

			if (isset($item['category']) and !empty($item['category'])) {
				$categories = explode(" > ", $item['category']);
				
				if (!is_array($categories))
					throw new \Exception("Could not make an array from your category string! Maybe no category was provided?");

				$i = 1;
				foreach ($categories as $category) {
					if ($i == 1)
						$product['item_category'] = trim($category);
					else
						$product['item_category'.$i] = trim($category);
					$i++;
				}
			}

			$items[] = $product;
			$j++;
		}

		$data['items'] = $items;

		return $data;
	}
}