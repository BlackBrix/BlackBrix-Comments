/**
 * @package   BlackBrixComments
 * @copyright Copyright (c)2020-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

CREATE TABLE `#__bbcomments_comments` (
    `id`          BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
    `parent_id`   BIGINT(20) unsigned NULL,
    `asset_id`    int(10) unsigned NOT NULL,
    `body`        longtext     NOT NULL,
    `name`        varchar(255) DEFAULT NULL,
    `email`       varchar(255) DEFAULT NULL,
    `ip`          varchar(64)  DEFAULT NULL,
    `user_agent`  varchar(255) NOT NULL,
    `enabled`     tinyint(3) NOT NULL DEFAULT '0',
    `created`     datetime     DEFAULT NULL,
    `created_by`  int(11) DEFAULT NULL,
    `modified`    datetime     DEFAULT NULL,
    `modified_by` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY           `#__bbcomments_comments_asset` (`asset_id`),
    KEY           `#__bbcomments_comments_created` (`created` DESC)
) ENGINE InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci COMMENT='Content comments';

CREATE TABLE `#__bbcomments_unsubscribe` (
    `asset_id` bigint(20) NOT NULL,
    `email`    varchar(255) NOT NULL,
    PRIMARY KEY (`asset_id`, `email`(100))
) ENGINE InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci COMMENT='Unsubscribed emails';


CREATE TABLE IF NOT EXISTS `#__bbcomments_import_map` (
    `source` VARCHAR(64) NOT NULL,
    `old_comment_id` BIGINT UNSIGNED NOT NULL,
    `new_comment_id` BIGINT UNSIGNED NOT NULL,
    `article_id` INT UNSIGNED NOT NULL,
    `imported_at` DATETIME NOT NULL,
    PRIMARY KEY (`source`, `old_comment_id`),
    KEY `#__bbcomments_import_new_id` (`new_comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci COMMENT='BlackBrix Comments import identity mapping';
