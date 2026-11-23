<?php
/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use BlackBrix\Component\Bbcomments\Administrator\Helper\LayoutHelper;

$displayData = [
	'textPrefix' => 'COM_BBCOMMENTS_COMMENTS',
	'formURL'    => 'index.php?option=com_bbcomments&view=comments',
	'icon'       => 'icon-bbcomments',
];

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
