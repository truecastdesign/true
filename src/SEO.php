<?php
namespace True;

/**
 * @version 1.1.0
 */
class SEO
{
	public function __construct()
	{
		
	}

	/**
	 * Used by Google
	 *
	 * @param object $info {"url", "title", "description", "site_logo_url", "site_logo_width", "site_logo_height", "site_logo_caption", "datePublished", "dateModified", "social_media"=>{"facebook", "twitter", "youtube", "instagram"}}
	 * @return void
	 */
	public function schemaGraph(object $info)
	{
		$info->url = rtrim($info->url, '/');
		
		$sameAs = [];
		if (!empty($info->social_media->facebook))
			$sameAs[] = $info->social_media->facebook;

		if (!empty($info->social_media->twitter))
			$sameAs[] = $info->social_media->twitter;

		if (!empty($info->social_media->youtube))
			$sameAs[] = $info->social_media->youtube;

		if (!empty($info->social_media->instagram))
			$sameAs[] = $info->social_media->instagram;

		$itemListElements = [];
		if (is_array($info->breadcrumbs)) {
			$position = 1;
			foreach ($info->breadcrumbs as $crumb) {
				$itemListElements[] = (object)[
					"@type"=>"ListItem",
					"position"=>$position,
					"item"=>[
						"@type"=>"WebSite",
						"@id"=>$info->base_url.$crumb['url'],
						"name"=>$crumb['name']
					]
				];
				$position++;
			}
		}
		
		$data = [
			"@context" => "http://schema.org",
			"@graph" => [
				0=>[
					"@type"=> "Organization",
					"@id"=> $info->url."/#organization",
					"name"=> $info->title,
					"url"=> $info->url,
					"sameAs"=> $sameAs,
					"logo"=> [
						"@type"=> "ImageObject",
						"@id"=> $info->url."/#logo",
						"inLanguage"=> "en-GB",
						"url"=> $info->site_logo_url,
						"width"=> $info->site_logo_width,
						"height"=> $info->site_logo_height,
						"caption"=> $info->site_logo_caption
					],
					"image"=> [
						"@id"=> $info->url."/#logo"
					]
				],
				1=>[
					"@type"=> "WebSite",
					"@id"=> $info->url."/#website",
					"url"=> $info->url,
					"name"=> $info->title,
					"description"=> $info->description,
					"publisher"=> [
						"@id"=> $info->url."/#organization"
					],
					"inLanguage"=> "en-GB"
				],
				2=>[
					"@type"=> "WebPage",
					"@id"=> $info->url."/#webpage",
					"url"=> $info->url,
					"name"=> $info->title,
					"isPartOf"=> [
						"@id"=> $info->url."/#website"
					],
					"about"=> [
						"@id"=> $info->url."/#organization"
					],
					"datePublished"=> $info->datePublished,
					"dateModified"=> $info->dateModified,
					"description"=> $info->description,
					"inLanguage"=> "en-GB",
					"potentialAction"=> [
						0=>[
							"@type"=> "ReadAction",
							"target"=> [
								$info->url
							]
						]
					]
				],
				3=>[
					"@type"=>"BreadcrumbList",
					"itemListElement"=>$itemListElements 
				]
				
			],
		];
		
		$html = '<script type="application/ld+json">'."\n";
		$html .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$html .= "\n".'</script>'."\n";

		return $html;
	}
}