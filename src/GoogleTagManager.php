<?php

namespace True;

/**
 * $eventData = [
 * 	'name'=>$productName,
 * 	'partNumber'=>$productPartNumber,
 * 	'price'=>$productPartNumber,
 * 	'brand'=>$productBrand,
 * 	'category'=>$productCategory,
 * 	'variant'=>$productVariant, // color or size
 * 	'quantity'=>$productQty
 * ]
 * $GoogleTagManager = new True\GoogleTagManager;
 * echo $GoogleTagManager->event('view_item', $eventData);
 * 
 * Documentation: https://developers.google.com/tag-platform/gtagjs/reference/events
 */

/**
 * @version 1.3.0
 */
class GoogleTagManager
{
	public function event($event, $data)
	{
		switch ($event) {
			case 'view_item':
				$Event = new \True\GoogleTagMangerEvents\ViewItem;
			break;
			
			case 'add_to_cart':
				$Event = new \True\GoogleTagMangerEvents\AddToCart;
			break;

			case 'view_cart':
				$Event = new \True\GoogleTagMangerEvents\ViewCart;
			break;
			
			case 'begin_checkout':
				$Event = new \True\GoogleTagMangerEvents\BeginCheckout;
			break;

			case 'login':
				$Event = new \True\GoogleTagMangerEvents\BeginCheckout;
			break;
			
			case 'purchase':
				$Event = new \True\GoogleTagMangerEvents\Purchase;
			break;
		}

		if (!is_object($Event))
			throw new \Exception("Invalid event type $event");

		return $this->addHTML($event, $Event->generate($data));
	}

	private function addHTML($event, $data)
	{
		$html = '<script>'."\n";
		$html .= 'gtag("event", "'.$event.'", '."\n";
		$html .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
		$html .= ");\n".'</script>'."\n";

		return $html;
	}
}