<?php
/**
 * @package plugin Create accordion
 * @version 2.0.0
 * @copyright Copyright (C) 2018 Jonathan Brain - brainforge. All rights reserved.
 * @license GPL
 * @author http://www.brainforge.co.uk
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgContentBfaccordion extends JPlugin
{
	const ACCORDIONSTART = '{bfaccordion-start}';
	const ACCORDIONSLIDER = '{bfaccordion-slider';
	const ACCORDIONEND = '{bfaccordion-end}';

	static $accordionsetid = 0;
	static $accordionid = 0;

	public function onContentPrepare($context, &$article, &$params, $limitstart)
	{
		$app = JFactory::getApplication();
		if($app->isAdmin()) return true;

		$accordionStart = strpos($article->text, self::ACCORDIONSTART);
		if ($accordionStart === false) return;
		$accordionEnd = strpos($article->text, self::ACCORDIONEND, $accordionStart);
		if ($accordionEnd === false) return;

		$accordionText = substr($article->text, $accordionStart, $accordionEnd - $accordionStart);
		if (preg_match('@' . self::ACCORDIONSLIDER . '[^}]*}</p>@', $accordionText)) return;
		$accordionEnd += strlen(self::ACCORDIONEND);

		$sliders = array();
		$sliderLabelEnd = false;
		$accordionLabel = '';
		$posn1 = 0;
		while (($posn2 = strpos($accordionText, self::ACCORDIONSLIDER, $posn1)) !== false)
		{
			if ($sliderLabelEnd !== false)
			{
				$sliders[$accordionLabel] = trim(substr($accordionText, $sliderLabelEnd+1, $posn2-$sliderLabelEnd-1));
			}
			$posn2 += strlen(self::ACCORDIONSLIDER);
			if (($sliderLabelEnd = strpos($accordionText, '}', $posn2)) === false) return;
			$accordionLabel = trim(substr($accordionText, $posn2, $sliderLabelEnd-$posn2));
			$posn1 = $posn2;
			if (empty($accordionLabel)) return;
		}

		if ($sliderLabelEnd !== false)
		{
			$sliders[$accordionLabel] = trim(substr($accordionText, $sliderLabelEnd+1));
		}

		$thisAccordionName = 'bfaccordion-' . (self::$accordionsetid++);
		$accordionOptions = array();
		if (count($sliders) == 1)
		{
			$accordionOptions['active'] = 'bfaccordion-slider-' . self::$accordionid;
		}
		$accordion = JHtml::_('bootstrap.startAccordion', $thisAccordionName, $accordionOptions);
		foreach($sliders as $label=>$content)
		{
			$accordion .= JHtml::_('bootstrap.addSlide', $thisAccordionName, $label, 'bfaccordion-slider-' . (self::$accordionid++));
			$accordion .= $content;
			$accordion .= JHtml::_('bootstrap.endSlide');
		}
		$accordion .= JHtml::_('bootstrap.endAccordion');

		$article->text = substr($article->text, 0, $accordionStart) .
			$accordion .
			substr($article->text, $accordionEnd);
		return;
	}
}
?>
