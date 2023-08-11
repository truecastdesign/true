<?php

namespace True\schemaTypes;

/**
 * Schema for Organization
 * 
 * @version 1.0.2
 */
class Organization
{
	private $structure = [];

	/**
	 * Get values with an value object
	 * 
	 * url: https://www.domain.com
	 * name: Website name
	 * description: A description of the site
	 *
	 * address: PostalAddress ['street'=>'', 'city'=>'', 'state'=>'', 'postalcode'=>'', 'country'=>'US']
	 * https://en.wikipedia.org/wiki/ISO_3166-1
	 * 
	 * aggregateRating: ['ratingValue'=>5, 'bestRating'=>5, 'worstRating'=>0, 'ratingCount'=>10]
	 * 
	 * image: An image of the organization. This can be a URL."
	 * 
	 * logo: An image logo url or ImageObject - https://schema.org/ImageObject ['contentUrl'=>'', 'width'=>'', 'height'=>, 'caption'=>]
	 * 
	 * areaServed: GeoCircle ['midpoint'=>['latitude'=>41.108237, 'longitude'=>-80.642982], 'radius'=>'distance in meters'] OR "Oregon"
	 * 
	 * audience: "Home Owners and Business Owners"
	 * 
	 * sameAs: ['https://www.facebook.com/page/ourname', 'https://www.youtube.com/jekljrewlr'], 'url'=>"https://www.domain.com/"]
	 * 
	 * award: text: Super service award
	 * 
	 * A brand is a name used by an organization or business person for labeling a product, product group, or similar.
	 * brand: ['name'=>"Brand name like Nike", 'logo'=>'https://www.domain.com/assets/images/global/brand-logo.jpg', 'slogan'=>"Our slogan", 'alternateName'=>"Our other name", 'description'=>"We do great things", 'disambiguatingDescription'=>"A sub property of description. A short description of the item used to disambiguate from other, similar items.", 'identifier'=>"ISBNs, GTIN codes, UUIDs etc.", 'image'=>"An image of the item. This can be a URL.", 'sameAs'=>['https://www.facebook.com/page/ourname', 'https://www.youtube.com/jekljrewlr'], 'url'=>"https://www.domain.com/"]
	 * 
	 * contactPoint: ['telephone'=>'+1-800-405-6687', 'type'=>'receptionist', "contactOption"=>"TollFree", "availableLanguage"=>["English","French"], 'areaServed'=>'US']
	 * 
	 * correctionsPolicy: url 'For an Organization (e.g. NewsMediaOrganization), a statement describing (in news media, the newsroom’s) disclosure and correction policy for errors.'
	 * 
	 * diversityPolicy: url 'Statement on diversity policy by an Organization e.g. a NewsMediaOrganization. For a NewsMediaOrganization, a statement describing the newsroom’s diversity policy on both staffing and sources, typically providing staffing data.'
	 * 
	 * diversityStaffingReport: url 'For an Organization (often but not necessarily a NewsMediaOrganization), a report on staffing diversity issues. In a news context this might be for example ASNE or RTDNA (US) reports, or self-reported.'
	 * 
	 * department: [array of types Organization]
	 * 
	 * duns: text 'The Dun & Bradstreet DUNS number for identifying an organization or business person.'
	 * 
	 * email: text 'user@domain.com'
	 * 
	 * employee: Person [['firstname'=>'John', 'lastname'=>'Dough', 'image'=>'url', 'telephone'=>'', 'email'=>'', 'jobTitle'=>'']]
	 * 
	 * ethicsPolicy: url "Statement about ethics policy, e.g. of a NewsMediaOrganization regarding journalistic and publishing practices, or of a Restaurant, a page describing food source policies. In the case of a NewsMediaOrganization, an ethicsPolicy is typically a statement describing the personal, organizational, and corporate standards of behavior expected by the organization"
	 * 
	 * event: Event [['location'=>'Memphis, TN, US', 'startDate'=>"2016-05-23", 'url'=>'http...']]
	 * 
	 * faxNumber: '800-555-1212'
	 * 
	 * founder: Person [['firstname'=>'John', 'lastname'=>'Dough', 'image'=>'url', 'telephone'=>'', 'email'=>'', 'jobTitle'=>'']]
	 * 
	 * foundingDate: Date ISO 8601 date format.
	 * 
	 * foundingLocation: Place ['name'=>'', 'address'=>['street'=>'', 'city'=>'', 'state'=>'', 'postalcode'=>'', 'country'=>'US'], 'hasMap'=>'url']
	 * 
	 * 
	 
	 * 
	 * @param object $info
	 * @return void
	 */
	public function set(object $info)
	{
		$data = [
			"@context" => "http://schema.org",
			"@type"=>'Organization'
		];

		$info = (array) $info;

		foreach ($info as $key=>$value) {
			if (empty($value))
				continue;

			switch ($key) {
				case 'address':
					$data['address'] = ['@type'=>'PostalAddress'];
					
					$addressKeys = ['street'=>'streetAddress', 'city'=>'addressLocality', 'state'=>'addressRegion', 'postalcode'=>'postalCode', 'country'=>'addressCountry'];

					foreach ($data['address'] as $key => $value)
						$date['address'][$addressKeys[$key]] = $value;
				break;

				case 'aggregateRating':
					if (is_array($value)) {
						$data['aggregateRating'] = ['@type'=>'AggregateRating'];
						
						$data['aggregateRating'] = array_merge($data['aggregateRating'], $value);
					}
				break;
				
				case 'areaServed':
					if (is_string($value['areaServed']))
						$data['areaServed'] = $value;
					elseif (is_array($value) and isset($value['midpoint'])) {
						$data['areaServed'] = ['@type'=>'GeoCircle', 'geoMidpoint'=>["@type"=>"GeoCoordinates", 'latitude'=>$value['midpoint']['latitude'], 'longitude'=>$value['midpoint']['longitude']], 'geoRadius'=>$value['radius']];
					}
				break;

				case 'brand':
					if (is_array($value)) {
						$data['brand'] = ['@type'=>'Brand'];
					
						$data['brand'] = array_merge($data['brand'], $value);
					}
				break;
				
				case 'contactPoint':
					if (is_array($value['contactPoint'])) {
						$data['contactPoint'] = ['@type'=>'ContactPoint'];
					
						$data['contactPoint'] = array_merge($data['contactPoint'], $value);
					}
				break;

				case 'employee':
					$data['employee'] = [];
					if (is_array($value['employee']))
						foreach ($value['employee'] as $person) {
							$person['@type'] = 'Person';
							$data['employee'][] = $person;
						}
				break;

				case 'event':
					$data['event'] = [];
					if (is_array($value['event']))
						foreach ($value['event'] as $event) {
							$event['@type'] = 'Event';
							$data['event'][] = $event;
						}
				break;

				case 'founder':
					$data['founder'] = ['@type'=>'Person'];
					$data['founder'] = array_merge($data['founder'], $value['founder']);
				break;

				case 'foundingLocation':
					$data['foundingLocation'] = ['@type'=>'Place'];
					$data['foundingLocation'] = array_merge($data['foundingLocation'], $value['foundingLocation']);
					
					$addressKeys = ['street'=>'streetAddress', 'city'=>'addressLocality', 'state'=>'addressRegion', 'postalcode'=>'postalCode', 'country'=>'addressCountry'];

					foreach ($data['foundingLocation']['address'] as $key => $value)
						$date['foundingLocation']['address'][$addressKeys[$key]] = $value;
				break;

				case 'logo':
					if (is_array($value)) {
						$data['logo'] = ['@type'=>'ImageObject'];
						$data['logo'] = array_merge($data['logo'], $value);
					} else
						$data['logo'] = $value;
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