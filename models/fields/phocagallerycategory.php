<?php
/**
 * @package     Joomla.Legacy
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

if (! class_exists('PhocaGalleryLoader')) {
    require_once( JPATH_ADMINISTRATOR.'/components/com_phocagallery/libraries/loader.php');
}
phocagalleryimport('phocagallery.render.renderadmin');
phocagalleryimport('phocagallery.html.category');


JFormHelper::loadFieldClass('list');

/**
 * Form Field class for the Joomla Platform.
 * Supports an HTML select list of categories
 *
 * @since  11.1
 */

class JFormFieldPhocaGalleryCategory extends JFormFieldCategory
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  11.1
	 */
	public $type = 'PhocaGalleryCategory';

	/**
	 * Method to get the field options for category
	 * Use the extension attribute in a form to specify the.specific extension for
	 * which categories should be displayed.
	 * Use the show_root attribute to specify whether to show the global category root in the list.
	 *
	 * @return  array    The field option objects.
	 *
	 * @since   11.1
	 */
	protected function getOptions() {


		$catId = $this->element['catid'] ? (string) $this->element['catid'] : -1;
		$db = JFactory::getDBO();

       //build the list of categories
		$query = 'SELECT a.title AS text, a.id AS value, a.parent_id as parentid'
		. ' FROM #__phocagallery_categories AS a'
		. ' WHERE a.published = 1'
		. ' ORDER BY a.ordering';
		$db->setQuery( $query );
		$phocagallerys = $db->loadObjectList();

		$tree = array();
		$text = '';
		$tree = self::CategoryTreeOption($phocagallerys, $tree, 0, $text, $catId);
		foreach ($tree as $k=>$v) {
			$tree[$k]->text = substr($v->text, 3); // strip - for root category level
		}
		array_unshift($tree, (object)array('value' => 'all', 'text'=>'- '.JText::_('JALL').' -'));
		return $tree;

	}
	public static function CategoryTreeOption($data, $tree, $id=0, $text='', $currentId) {

		foreach ($data as $key) {
			//	$show_text =  $text . $key->text;
			$minus =  $text . ' - ';

			if ($key->parentid == $id && $currentId != $id && $currentId != $key->value) {
				$tree[$key->value] 			= new stdClass;
				$tree[$key->value]->text 	= $minus. $key->text;
				$tree[$key->value]->value 	= $key->value;

				$tree = self::CategoryTreeOption($data, $tree, $key->value, $minus, $currentId );
			}
		}
		return($tree);
	}
}
