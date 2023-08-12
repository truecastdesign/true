<?php

namespace True\schemaTypes;

/**
 * Schema for HomeAndConstructionBusiness
 * 
 * https://schema.org/HomeAndConstructionBusiness
 * 
 * @version 1.0.0
 */
class HomeAndConstructionBusiness
{
	private $structure = [];

	/**
	 * Get values with an value object
	 * 
	 * name: Title of the web page
	 * 
	 * address: ['street'=>'', 'city'=>'', 'state'=>'', 'zip'=>'', 'country'=>'US']
	 * 
	 * image: single string or multiple array ['https://example.com/photos/1x1/photo.jpg', 'https://example.com/photos/4x3/photo.jpg']
	 * 
	 * geo: ['latitude'=>'', 'longitude'=>'']
	 *
	 * url: https://www.domain.com
	 * 
	 * telephone: +15415551212
	 * 
	 * hours: [['days'=>["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"], 'opens'=>'08:00', 'closes'=>'17:00'], ['days'=>["Sunday"], 'opens'=>'11:00', 'closes'=>'17:00']]
	 
	 * 
	 * @param object $info
	 * @return void
	 */
	public function set(object $info)
	{
		$data = [
			"@context" => "http://schema.org",
			"@type"=>'HomeAndConstructionBusiness'
		];

		$info = (array) $info;

		foreach ($info as $key=>$value) {
			if (empty($value))
				continue;

			switch ($key) {
				case 'address':
					$data['address'] = ['@type'=>'PostalAddress', 'streetAddress'=>$value['street'], 'addressLocality'=>$value['city'], 'addressRegion'=>$value['state'], 'postalCode'=>$value['city'], 'addressCountry'=>$value['country']];
				break;

				case 'geo':
					if (is_array($value)) 
						$data['geo'] = array_merge(['@type'=>'GeoCoordinates'], $value);
				break;

				case 'hours':
					if (is_array($value)) {
						foreach ($value as $hours) {
							$data['openingHoursSpecification'][] = ["@type"=>'OpeningHoursSpecification', 'dayOfWeek'=>$hours['days'], 'opens'=>$hours['opens'], 'closes'=>$hours['closes']];
						}
					}
				break;

				default:
					$data[$key] = $value;
			}
		}

		$this->structure = $data;
	}

	public function get()
	{
		return $this->structure;
	}
}