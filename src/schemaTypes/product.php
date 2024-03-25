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
 * 
 * https://jsonld.com/product/
 */
class Product
{
	private $structure = [];
	private $conditions = [
		'used'=>"http://schema.org/UsedCondition",
		'damaged'=>"http://schema.org/DamagedCondition",
		'new'=>"http://schema.org/NewCondition",
		'refurbished'=>"http://schema.org/RefurbishedCondition"
	];
	private $availability = [
		'instock'=>"http://schema.org/InStock",
		'backorder'=>"http://schema.org/BackOrder",
		'discontinued'=>"http://schema.org/Discontinued",
		'instoreonly'=>"http://schema.org/InStoreOnly",
		'limited'=>"http://schema.org/LimitedAvailability",
		'onlineonly'=>"http://schema.org/OnlineOnly",
		'outofstock'=>"http://schema.org/OutOfStock",
		'preorder'=>"http://schema.org/PreOrder",
		'presale'=>"http://schema.org/PreSale",
		'soldout'=>"http://schema.org/SoldOut"
	];

	private $daysOfWeek = [
		'Mon'=>'https://schema.org/Monday',
		'Tue'=>'https://schema.org/Tuesday',
		'Wed'=>'https://schema.org/Wednesday',
		'Thu'=>'https://schema.org/Thursday',
		'Fri'=>'https://schema.org/Friday'
	];

	public function set(object $info)
	{
		$data = [
			"@context" => "http://schema.org",
			"@type"=>'Product'
		];

		if (isset($info->name) and !empty($info->name))
			$data['name'] = $info->name;
			
		if (isset($info->image) and !empty($info->image))
			$data['image'] = $info->image;

		if (isset($info->description) and !empty($info->description))
			$data['description'] = htmlspecialchars(trim($info->description), ENT_QUOTES, 'UTF-8');

		if (isset($info->mpn) and !empty($info->mpn))
			$data['mpn'] = $info->mpn;

		if (isset($info->sku) and !empty($info->sku))
			$data['sku'] = $info->sku;
			
		if (isset($info->upc) and !empty($info->upc))
			$data['gtin12'] = $info->upc;

		if (isset($info->brand) and !empty($info->brand))
			$data['brand'] = ["@type"=>"Brand", "name"=>$info->brand];

		if (isset($info->ratingValue) and !empty($info->ratingValue)) {
			$data['aggregateRating'] = ["@type"=>"AggregateRating"];

			if (isset($info->ratingValue) and !empty($info->ratingValue))
				$data['aggregateRating']['ratingValue'] = $info->ratingValue;

			if (isset($info->reviewCount) and !empty($info->reviewCount))
				$data['aggregateRating']['reviewCount'] = $info->reviewCount;

			if (isset($info->name) and !empty($info->name))
				$data['aggregateRating']['itemReviewed'] = htmlentities($info->name);

			if (isset($info->bestRating) and !empty($info->bestRating))
				$data['aggregateRating']['bestRating'] = $info->bestRating;
				
			if (isset($info->worstRating) and !empty($info->worstRating))
				$data['aggregateRating']['worstRating'] = $info->worstRating;
		}

		if (isset($info->offers) and $info->offers) {
			$data['offers'] = ["@type"=>"Offer"];
			
			if (isset($info->priceCurrency) and !empty($info->priceCurrency))
				$data['offers']["priceCurrency"] = $info->priceCurrency;

			if (isset($info->price) and !empty($info->price))
				$data['offers']["price"] = $info->price;

			if (isset($info->priceValidUntil) and !empty($info->priceValidUntil))
				$data['offers']["priceValidUntil"] = $info->priceValidUntil;

			if (isset($info->itemCondition) and !empty($info->itemCondition))
				$data['offers']["itemCondition"] = $this->conditions[$info->itemCondition];

			if (isset($info->availability) and !empty($info->availability))
				$data['offers']["availability"] = $this->availability[$info->availability];

			if (isset($info->seller) and !empty($info->seller))
				$data['offers']["seller"] = ['@type'=>'Organization', 'name'=>$info->seller];

			if (isset($info->url) and !empty($info->url))
				$data['offers']["url"] = $url->url;

			/*
			rate: 7.50
			* 	rateCurrency:USD
			* 	shippingDays: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
			* 	cutoffTime: "20:00:00Z" // UTC timezone
			*/
				
			if (isset($info->shippingDetails) and is_object($info->shippingDetails)) {
				$data['offers']["shippingDetails"]['@type'] = 'OfferShippingDetails';
				if (isset($info->shippingDetails->rate))
					$data['offers']["shippingDetails"]['shippingRate'] = ['@type'=>'MonetaryAmount', 'value'=>$info->shippingDetails->rate, 'currency'=>(isset($info->shippingDetails->rateCurrency)? $info->shippingDetails->rateCurrency:'USD')];
				if (isset($info->shippingDetails->shippingDays) or isset($info->shippingDetails->cutoffTime)) {
					$data['offers']["shippingDetails"]['deliveryTime'] = ['@type'=>'ShippingDeliveryTime'];

					if (isset($info->shippingDetails->shippingDays))
						$data['offers']["shippingDetails"]['deliveryTime']['businessDays'] = ['@type'=>'OpeningHoursSpecification', 'dayOfWeek'=>array_map(function($day) {
							return $this->daysOfWeek[$day];
						}, $info->shippingDetails->shippingDays)];

					if (isset($info->shippingDetails->cutoffTime))
						$data['offers']["shippingDetails"]['cutoffTime'] = $info->shippingDetails->cutoffTime;
				}
			} elseif (isset($info->shippingDetails) and !empty($info->shippingDetails))
				$data['offers']["shippingDetails"] = $url->url;
		}

		if (isset($info->reviews) and is_array($info->reviews) and count($info->reviews) > 0) {
			$data['review'] = [];
			
			foreach ($info->reviews as $review) {
				$reviewItem = [];

				$reviewItem['@type'] = "Review";

				if (isset($review->ratingValue) and !empty($review->ratingValue)) {
					$reviewItem['reviewRating'] = ["@type"=>"Rating"];
					
					$reviewItem['reviewRating']['ratingValue'] = $review->ratingValue;

					if (isset($review->bestRating) and !empty($review->bestRating))
						$reviewItem['reviewRating']['bestRating'] = $review->bestRating;

					if (isset($review->worstRating) and !empty($review->worstRating))
						$reviewItem['reviewRating']['worstRating'] = $review->worstRating;
				}

				$reviewItem['author'] = [
					"@type"=>"Person", "name"=>(empty($review->author)? 'Anonymous':htmlspecialchars(trim($review->author)))
				];

				if (isset($review->datePublished) and !empty($review->datePublished))
					$reviewItem['datePublished'] = $review->datePublished;

				if (isset($review->name) and !empty($review->name))
					$reviewItem['name'] = htmlspecialchars(str_replace('"','',$review->name));

				if (isset($review->reviewBody) and !empty($review->reviewBody))
					$reviewItem['reviewBody'] = htmlspecialchars(trim($review->reviewBody), ENT_QUOTES, 'UTF-8');

				$data['review'][] = $reviewItem;
			}

		$this->structure = $data;
	}

	public function get()
	{
		return $this->structure;
	}
}