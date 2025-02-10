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
 * @version 1.2.1
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
		'Fri' => 'https://schema.org/Friday'
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
							"value" => $info->shippingDetails['handlingTime'][0] ?? null,
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
					"author" => ["@type" => "Person", "name" => $review['author'] ?? "Anonymous"],
					"datePublished" => $review['datePublished'] ?? null,
					"name" => htmlspecialchars(str_replace('"', '', $review['name'] ?? "")),
					"reviewBody" => htmlspecialchars(trim($review['reviewBody'] ?? ""), ENT_QUOTES, 'UTF-8')
				];
			}, $info->reviews);
		}

		$this->structure = array_filter($data, fn($value) => $value !== null);
	}

	public function get(): array
	{
		return $this->structure;
	}
}