<?php

namespace True\schemaTypes;

/**
 * Schema for LocalBusiness
 *
 * https://schema.org/LocalBusiness
 *
 * name: Business name
 * url: https://www.domain.com
 * telephone: +15415551212
 * email: user@domain.com
 * description: Business description
 * priceRange: '$' or '$$' or '$$$'
 *
 * address: ['street'=>'', 'city'=>'', 'state'=>'', 'zip'=>'', 'country'=>'US']
 *
 * image: single string or array of strings
 *
 * logo: url string
 *
 * geo: ['latitude'=>'', 'longitude'=>'']
 *
 * hours: [['days'=>["Monday","Tuesday","Wednesday","Thursday","Friday"], 'opens'=>'09:00', 'closes'=>'17:00']]
 *
 * sameAs: ['https://www.facebook.com/...', 'https://www.instagram.com/...']
 *
 * contactPoint: ['telephone'=>'+15415551212', 'type'=>'customer service', 'language'=>'English']
 *
 * @version 1.0.0
 */
class LocalBusiness
{
	private array $structure = [];

	public function set(object $info): void
	{
		$data = [
			"@context" => "http://schema.org",
			"@type"    => 'LocalBusiness',
		];

		$info = (array) $info;

		foreach ($info as $key => $value) {
			if (empty($value))
				continue;

			switch ($key) {
				case 'address':
					$data['address'] = ['@type' => 'PostalAddress'];
					$map = ['street' => 'streetAddress', 'city' => 'addressLocality', 'state' => 'addressRegion', 'zip' => 'postalCode', 'country' => 'addressCountry'];
					foreach ($value as $k => $v) {
						if (isset($map[$k]) && !empty($v))
							$data['address'][$map[$k]] = $v;
					}
				break;

				case 'geo':
					if (is_array($value))
						$data['geo'] = array_merge(['@type' => 'GeoCoordinates'], $value);
				break;

				case 'hours':
					if (is_array($value)) {
						foreach ($value as $hours) {
							$data['openingHoursSpecification'][] = [
								'@type'     => 'OpeningHoursSpecification',
								'dayOfWeek' => $hours['days'],
								'opens'     => $hours['opens'],
								'closes'    => $hours['closes'],
							];
						}
					}
				break;

				case 'contactPoint':
					if (is_array($value)) {
						$cp = ['@type' => 'ContactPoint'];
						if (!empty($value['telephone']))     $cp['telephone']          = $value['telephone'];
						if (!empty($value['type']))          $cp['contactType']        = $value['type'];
						if (!empty($value['language']))      $cp['availableLanguage']  = $value['language'];
						$data['contactPoint'] = $cp;
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
