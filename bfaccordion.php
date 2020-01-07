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
	const ACCORDIONSTART = '{bfaccordion-start';
	const ACCORDIONSLIDER = '{bfaccordion-slider';
	const ACCORDIONEND = '{bfaccordion-end}';

	const ACCORDIONPREFIX = 'bfaccordion-';

	static $accordionid = 0;
	static $sliderid = 0;
	static $sliderNameList = null;

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

		if (empty($sliders)) return;

		$thisAccordionName = self::ACCORDIONPREFIX . (self::$accordionid++);
		$sliderPrefix = self::ACCORDIONPREFIX . $article->id . '-slider-';

		$active = JFactory::getApplication()->input->getVar('sliderid', null);
		if (!preg_match('/^' . $sliderPrefix . '[0-9]+$/', $active))
		{
			sscanf($accordionText, self::ACCORDIONSTART . '%d', $initialSliderid);
			$active = ($initialSliderid === null) ? null : $sliderPrefix . $initialSliderid;
		}

		$accordionOptions = array();
		if ($active !== null)
		{
			$accordionOptions['active'] = $active;
		}
		$accordionOptions['toggle'] = true;

		self::$sliderNameList = array();
		$accordion = JHtml::_('bootstrap.startAccordion', $thisAccordionName, $accordionOptions);
		foreach($sliders as $label=>$content)
		{
			$thisSliderName = $sliderPrefix . (self::$sliderid++);
			self::$sliderNameList[] = $thisSliderName;
			$accordion .= JHtml::_('bootstrap.addSlide', $thisAccordionName, $label, $thisSliderName);
			$accordion .= $content;
			$accordion .= JHtml::_('bootstrap.endSlide');
		}
		$accordion .= JHtml::_('bootstrap.endAccordion');

		$article->text = substr($article->text, 0, $accordionStart) .
			$accordion .
			substr($article->text, $accordionEnd);

		JFactory::getDocument()->addScriptDeclaration('
jQuery( document ).ready(function() {
	var hash = location.hash;
	if (hash) {
		var $a = jQuery(hash);
		if ($a.length) $a.collapse("show");
	}
});
');

		$doc = JFactory::getDocument();
		if($this->params->get('cssmode'))
		{
			$css = trim($this->params->get('customcss'));
			if (!empty($css))
			{
				$doc->addStyleDeclaration($css);
			}
		}
		if($this->params->get('jsmode'))
		{
			$js = trim($this->params->get('customjs'));
			if (!empty($js))
			{
				$doc->addScriptDeclaration($js);
			}
		}

		return;
	}

	/**
	 * Listener for the `onAfterRender` event
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterRender()
	{
		$documentbody = JResponse::getBody();

		$documentbody = preg_replace_callback(
			'@/(' . self::ACCORDIONPREFIX . '[0-9]+-slider-[0-9]+)"@',
			function ($matches) {
				if (!empty(self::$sliderNameList) && in_array($matches[1], self::$sliderNameList))
				{
					return '#' . $matches[1] . '" onclick=\'
var $a = jQuery("#' . $matches[1] . '");
if ($a.length) $a.collapse("show");
return false;					
\'';
				}
				return '#' . $matches[1] . '"';
			},
			$documentbody
		);

		JResponse::setBody($documentbody);
	}
}
?>
