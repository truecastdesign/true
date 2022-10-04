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
		}

		if (!is_object($Event))
			throw new \Exception("Invalid event type $event");

		return $this->addHTML($event, $Event->generate($data));
	}

	private function addHTML($event, $data)
	{
		$html = '<script>'."\n";
		$html .= 'gtag("event", "'.$event.'", {'."\n";
		$html .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$html .= "\n".'</script>'."\n";

		return $html;
	}
}
