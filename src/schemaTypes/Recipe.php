<?php

namespace True\schemaTypes;

class Recipe
{
	private $structure = [];

	/**
	 * Get values with an value object
	 * 
	 * "name": "Mom's World Famous Banana Bread"
	 * "author": "John Smith"
	 * "cookTime": "PT1H" - The capital letters P, Y, M, W, D, T, H, M, and S are designators for each of the date and time elements and are not replaced.
	 * P is the duration designator (for period) placed at the start of the duration representation.
	 * Y is the year designator that follows the value for the number of calendar years.
	 * M is the month designator that follows the value for the number of calendar months.
	 * W is the week designator that follows the value for the number of weeks.
	 * D is the day designator that follows the value for the number of calendar days.
	 * T is the time designator that precedes the time components of the representation.
	 * H is the hour designator that follows the value for the number of hours.
	 * M is the minute designator that follows the value for the number of minutes.
	 * S is the second designator that follows the value for the number of seconds.
	 * For example, "P3Y6M4DT12H30M5S" represents a duration of "three years, six months, four days, twelve hours, thirty minutes, and five seconds".
	 * 
  	 * "datePublished": "2009-05-08" - YYYY-MM-DD
    * "description": "This classic banana bread recipe comes from my mom -- the walnuts add a nice texture and flavor to the banana bread."
    * "image": "bananabread.jpg"
    * "recipeIngredient": "3 or 4 ripe bananas, smashed\n1 egg\n3/4 cup of sugar"
    * "userInteractionCount": "140"
    * "calories": "240 calories"
    * "fatContent": "9 grams fat"
    * "prepTime": "PT15M" - same format at cookTime
    * "recipeInstructions": "Preheat the oven to 350 degrees. Mix in the ingredients in a bowl. Add the flour last. Pour the mixture into a loaf pan and bake for one hour."
    * "recipeYield": "1 loaf"
    * "suitableForDiet": "LowFatDiet|DiabeticDiet|GlutenFreeDiet|HalalDiet|HinduDiet|KosherDiet|LowCalorieDiet|LowFatDiet|LowLactoseDiet|LowSaltDiet|VeganDiet|VegetarianDiet"
	 *
	 * @param object $info
	 * @return void
	 */
	public function set(object $info)
	{
		$data = [
			"@context" => "http://schema.org",
			"@type"=>'Recipe'
		];

		if ($this->is($info->name))
			$data['name'] = $info->name;
			
		if ($this->is($info->description))
			$data['description'] = $info->description;

		if ($this->is($info->author))
			$data['author'] = $info->author;
			
		if ($this->is($info->cookTime))
			$data['cookTime'] = $info->cookTime;

		if ($this->is($info->datePublished))
			$data['datePublished'] = $info->datePublished;
			
		if ($this->is($info->image))
			$data['image'] = $info->image;
			
		if ($this->is($info->recipeIngredient))
			$data['recipeIngredient'] = explode("\n", $info->recipeIngredient);
			
		if ($this->is($info->userInteractionCount))
			$data['interactionStatistic'] = [
				"@type"=>"InteractionCounter", 
				"interactionType"=>"https://schema.org/Comment",
				"userInteractionCount"=>$info->userInteractionCount
			];

		if ($this->is($info->calories) OR $this->is($info->fatContent)) {
			$data['nutrition'] = ["@type"=>"NutritionInformation"];
			if ($this->is($info->calories))
				$data['nutrition']['calories'] = $info->calories;
			if ($this->is($info->fatContent))
				$data['nutrition']['fatContent'] = $info->fatContent;
		}

		if ($this->is($info->prepTime))
			$data['prepTime'] = $info->prepTime;

		if ($this->is($info->recipeInstructions))
			$data['recipeInstructions'] = $info->recipeInstructions;
			
		if ($this->is($info->recipeYield))
			$data['recipeYield'] = $info->recipeYield;

		if ($this->is($info->suitableForDiet))
			$data['suitableForDiet'] = "https://schema.org/".$info->suitableForDiet;

		$this->structure = $data;
	}

	public function get()
	{
		return $this->structure;
	}

	public function is($value)
	{
		return (isset($value) and !empty($value))? true:false;
	}
}