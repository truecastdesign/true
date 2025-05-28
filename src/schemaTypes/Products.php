<?php

namespace True\schemaTypes;
/**
 * Product structure
 * 
 * name: Cool Product
 * image: https://www.example/images/photo.jpg
 * description: This is a great product
 * mpn: RU8
 * sku: RUOi83
 * upc: 4654654654657879
 * brand: Apple
 * ratingValue:4
 * reviewCount:3
 * bestRating:5
 * worstRating:1
 * 
 * offers: true
 * priceCurrency: USD
 * itemCondition: new
 * availability: instock
 * price: 1.00
 * priceValidUntil: 2023-01-01
 * seller: Macs R Us
 * url: https://www.example.com/products/RUOi83
 * shippingDetails: url or array
 * 	rate: 7.50
 * 	rateCurrency:USD
 * 	shippingDays: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
 * 	cutoffTime: "20:00:00Z" // UTC timezone
 * reviews: array
 * 	ratingValue:3
 *		bestRating:5
 * 	worstRating:1
 * 	author:"A Name"
 * 	datePublished: "2024-01-01"
 * 	name: "Great review title"
 * 	reviewBody: "The review body"
 * 
 * https://jsonld.com/product/
 * 
 * @version 1.2.2
 */
class Products
{
	private array $structure = [];
	private array $conditions = [
		'used' => "http://schema.org/UsedCondition",
		'damaged' => "http://schema.org/DamagedCondition",
		'new' => "http://schema.org/NewCondition",
		'refurbished' => "http://schema.org/RefurbishedCondition"
	];
	private array $availability = [
		'instock' => "http://schema.org/InStock",
		'backorder' => "http://schema.org/BackOrder",
		'discontinued' => "http://schema.org/Discontinued",
		'instoreonly' => "http://schema.org/InStoreOnly",
		'limited' => "http://schema.org/LimitedAvailability",
		'onlineonly' => "http://schema.org/OnlineOnly",
		'outofstock' => "http://schema.org/OutOfStock",
		'preorder' => "http://schema.org/PreOrder",
		'presale' => "http://schema.org/PreSale",
		'soldout' => "http://schema.org/SoldOut"
	];
	private array $daysOfWeek = [
		'Mon' => 'https://schema.org/Monday',
		'Tue' => 'https://schema.org/Tuesday',
		'Wed' => 'https://schema.org/Wednesday',
		'Thu' => 'https://schema.org/Thursday',
		'Fri' => 'https://schema.org/Friday',
		'Sat' => 'https://schema.org/Saturday',
		'Sun' => 'https://schema.org/Sunday'
	];

	public function set(object $info): void
	{
		$data = [
			"@context" => "http://schema.org",
			"@type" => 'Product'
		];

		$fields = ['name', 'image', 'description', 'mpn', 'sku', 'upc', 'brand', 'ratingValue', 'reviewCount'];
		foreach ($fields as $field) {
			if (!empty($info->$field)) {
				$data[$field] = $info->$field;
			}
		}

		if (!empty($info->brand)) {
			$data['brand'] = ["@type" => "Brand", "name" => $info->brand];
		}

		if (!empty($info->ratingValue)) {
			$data['aggregateRating'] = [
				"@type" => "AggregateRating",
				"ratingValue" => $info->ratingValue,
				"reviewCount" => $info->reviewCount ?? null,
				"bestRating" => $info->bestRating ?? null,
				"worstRating" => $info->worstRating ?? null
			];
		}

		if (!empty($info->offers)) {
			$data['offers'] = [
				"@type" => "Offer",
				"priceCurrency" => $info->priceCurrency ?? "USD",
				"price" => $info->price ?? null,
				"priceValidUntil" => $info->priceValidUntil ?? null,
				"itemCondition" => $this->conditions[$info->itemCondition] ?? null,
				"availability" => $this->availability[$info->availability] ?? null,
				"seller" => ["@type" => "Organization", "name" => $info->seller ?? null],
				"url" => $info->url ?? null
			];

			if (!empty($info->shippingDetails) && is_array($info->shippingDetails)) {
				$data['offers']["shippingDetails"] = [
					"@type" => "OfferShippingDetails",
					"shippingRate" => [
						"@type" => "MonetaryAmount",
						"value" => $info->shippingDetails['rate'] ?? 0,
						"currency" => $info->shippingDetails['rateCurrency'] ?? "USD"
					],
					"shippingDestination" => [
						"@type" => "DefinedRegion",
						"addressCountry" => $info->shippingDetails['shippingDestination'] ?? "US"
					],
					"deliveryTime" => [
						"@type" => "ShippingDeliveryTime",
						"businessDays" => [
							"@type" => "OpeningHoursSpecification",
							"dayOfWeek" => array_map(fn($day) => $this->daysOfWeek[$day] ?? null, $info->shippingDetails['shippingDays'] ?? [])
						],
						"cutoffTime" => $info->shippingDetails['cutoffTime'] ?? null,
						"handlingTime" => [
							"@type" => "QuantitativeValue",
							"minValue"=>$info->shippingDetails['handlingTime'][0] ?? null,
							"maxValue" => $info->shippingDetails['handlingTime'][1] ?? null,
							"unitCode" => $info->shippingDetails['handlingTime'][2] ?? "d"
						],
						"transitTime" => [
							"@type" => "QuantitativeValue",
							"minValue" => $info->shippingDetails['transitTime'][0] ?? null,
							"maxValue" => $info->shippingDetails['transitTime'][1] ?? null,
							"unitCode" => $info->shippingDetails['transitTime'][2] ?? "d"
						]
					]
				];

				if (is_null($data['offers']["shippingDetails"]["deliveryTime"]["handlingTime"]["minValue"]))
					unset($data['offers']["shippingDetails"]["deliveryTime"]["handlingTime"]["minValue"]);

				if (is_null($data['offers']["shippingDetails"]["deliveryTime"]["handlingTime"]["maxValue"]))
					$data['offers']["shippingDetails"]["deliveryTime"]["handlingTime"]["maxValue"] = 1;
			}
		}

		if (!empty($info->reviews) && is_array($info->reviews)) {
			$data['review'] = array_map(function ($review) {
				return [
					"@type" => "Review",
					"reviewRating" => [
						"@type" => "Rating",
						"ratingValue" => $review['ratingValue'] ?? null,
						"bestRating" => $review['bestRating'] ?? null,
						"worstRating" => $review['worstRating'] ?? null
					],
					"author" => ["@type" => "Person", "name" => empty($review['author'])? "Anonymous":$review['author']],
					"datePublished" => $review['datePublished'] ?? null,
					"name" => $this->filter($review['name']),
"reviewBody" => $this->filter($review['reviewBody'])
				];
			}, $info->reviews);
		}

		$this->structure = array_filter($data, fn($value) => $value !== null);
	}

	public function get(): array
	{
		return $this->structure;
	}

	function filter($str)
	{
		return htmlspecialchars(str_replace(['"', "\r", "\n", "\t"], ['',' ',' ',' '], html_entity_decode($str ?? '')), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
	}
}