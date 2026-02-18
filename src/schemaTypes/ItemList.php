<?php

namespace True\schemaTypes;

/**
 * Schema for ItemList
 *
 * https://schema.org/ItemList
 *
 * name: List name
 * description: List description
 * url: https://www.domain.com/list
 *
 * items: array of items. Each item can be:
 *   Simple link: ['name'=>'Product Name', 'url'=>'https://...']
 *   With product data: ['name'=>'...', 'url'=>'https://...', 'image'=>'https://...', 'description'=>'...', 'price'=>'45.95', 'priceCurrency'=>'USD']
 *
 * @version 1.0.0
 */
class ItemList
{
	private array $structure = [];

	public function set(object $info): void
	{
		$data = [
			"@context" => "http://schema.org",
			"@type"    => 'ItemList',
		];

		$info = (array) $info;

		foreach ($info as $key => $value) {
			if (empty($value))
				continue;

			switch ($key) {
				case 'items':
					if (is_array($value)) {
						$position = 1;
						$list = [];
						foreach ($value as $item) {
							$listItem = [
								'@type'    => 'ListItem',
								'position' => $position,
							];

							if (!empty($item['name']))
								$listItem['name'] = $item['name'];

							if (!empty($item['url']))
								$listItem['url'] = $item['url'];

							// If product-level detail is provided, nest a Thing/Product
							if (!empty($item['image']) || !empty($item['price'])) {
								$thing = ['@type' => 'Product'];
								if (!empty($item['name']))        $thing['name']        = $item['name'];
								if (!empty($item['image']))       $thing['image']       = $item['image'];
								if (!empty($item['description'])) $thing['description'] = $item['description'];
								if (!empty($item['url']))         $thing['url']         = $item['url'];
								if (!empty($item['price'])) {
									$thing['offers'] = [
										'@type'         => 'Offer',
										'price'         => $item['price'],
										'priceCurrency' => $item['priceCurrency'] ?? 'USD',
									];
								}
								$listItem['item'] = $thing;
							}

							$list[] = $listItem;
							$position++;
						}
						$data['itemListElement'] = $list;
					}
				break;

				default:
					$data[$key] = $value;
			}
		}

		$this->structure = $data;
	}

	public function get(): array
	{
		return $this->structure;
	}
}
