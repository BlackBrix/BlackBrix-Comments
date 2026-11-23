/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- Currently used tables
DROP TABLE IF EXISTS `#__bbcomments_comments`;
DROP TABLE IF EXISTS `#__bbcomments_unsubscribe`;



-- Mail templates
DELETE FROM `#__mail_templates` WHERE `extension` = 'com_bbcomments';

DROP TABLE IF EXISTS `#__bbcomments_import_map`;
