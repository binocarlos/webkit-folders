1. run the sql below

2. goto /app/root and create 'new' system

3. delete 'default' system

4. run fix_tree_old on system

5. change 'new' system to 'default' system

5.5 change name of system item to Xara

6. run urls script in root on system

7. ALTER TABLE `item` DROP `item_id` ;

8. delete danM tutorial

9. add the models for xara_widgets and xara_news

10. change the news_release_collection item to a folder

10.5 change the news folder icon

11.

update item set item_type = 'xara_news_item'
where item_type = 'news_release';

update item set item_type = 'xara_widget_step'
where item_type = 'widget_guide_step';

update item set item_type = 'xara_widget'
where item_type = 'widget_guide';


update item set item_type = 'xara_widget_group'
where item_type = 'widget_guide_collection';



--------------------------------


CREATE TABLE `xaracom_dev`.`installation` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `name` VARCHAR (255) NOT NULL, `config` TEXT, PRIMARY KEY(`id`), INDEX(`id`,`name`)) TYPE = MyISAM /*!40100 DEFAULT CHARSET utf8 COLLATE utf8_general_ci */ ;

INSERT INTO `xaracom_dev`.`installation` (`name`) VALUES ('xara') ;
ALTER TABLE `item` ADD `installation_id` INT UNSIGNED NOT NULL AFTER `id` ;
ALTER TABLE `item` DROP INDEX `id`, ADD INDEX `id` (`id`, `item_type`, `item_id`, `installation_id`) ;
ALTER TABLE `item_keyword` ADD `installation_id` INT UNSIGNED NOT NULL AFTER `id` ;
ALTER TABLE `item_keyword` ADD INDEX `id` (`id`, `installation_id`, `item_id`) ;
ALTER TABLE `item_keyword` DROP INDEX `value_type`, DROP INDEX `number_value` ;
ALTER TABLE `item_keyword` DROP INDEX `name`, DROP INDEX `value`, DROP INDEX `keyword_type`, DROP INDEX `item_id`, DROP INDEX `date_value` ;
ALTER TABLE `item_keyword` ADD INDEX `value` (`value`, `number_value`, `date_value`) ;
ALTER TABLE `item_keyword` DROP INDEX `id`, ADD INDEX `id` (`id`, `installation_id`, `item_id`, `name`) ;

update item
set installation_id = 1;

update item_keyword
set installation_id = 1;


CREATE TABLE `xaracom_dev`.`item_link` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `installation_id` INT UNSIGNED NOT NULL, `item_id` INT UNSIGNED NOT NULL, `parent_id` INT UNSIGNED NOT NULL, `l` INT UNSIGNED NOT NULL, `r` INT UNSIGNED NOT NULL, PRIMARY KEY(`id`), INDEX(`id`,`item_id`,`parent_id`,`l`,`r`)) TYPE = MyISAM /*!40100 DEFAULT CHARSET utf8 COLLATE utf8_general_ci */ ;
ALTER TABLE `item_link` CHANGE `parent_id` `parent_id` INT(10) UNSIGNED NULL  ;
ALTER TABLE `item_link` ADD `parent_link_id` INT UNSIGNED NULL AFTER `parent_id` ;
ALTER TABLE `item_link` CHANGE `parent_id` `parent_item_id` INT(10) UNSIGNED DEFAULT NULL NULL;


#ALTER TABLE `item` DROP `item_id` ;

CREATE TABLE `xaracom_dev`.`domain` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `installation_id` INT UNSIGNED NOT NULL, `name` VARCHAR (64) DEFAULT 'default' NOT NULL, PRIMARY KEY(`id`), INDEX(`id`,`installation_id`)) TYPE = MyISAM /*!40100 DEFAULT CHARSET utf8 COLLATE utf8_general_ci */ ;

ALTER TABLE `domain` DROP INDEX `id`, ADD INDEX `id` (`id`, `installation_id`, `name`);

ALTER TABLE `item_link` ADD `domain_id` INT UNSIGNED NOT NULL AFTER `installation_id` ;

ALTER TABLE `item_link` DROP INDEX `id`, ADD INDEX `id` (`id`, `item_id`, `parent_item_id`, `l`, `r`, `installation_id`, `parent_link_id`, `domain_id`) ;

ALTER TABLE `domain` RENAME `system`;

ALTER TABLE `item_link` CHANGE `domain_id` `system_id` INT(10) UNSIGNED NOT NULL ;

INSERT INTO `xaracom_dev`.`system` (`installation_id`, `name`) VALUES ('1', 'default') ;

RENAME TABLE `installation` TO `item_installation` ;

RENAME TABLE `system` TO `item_system` ;

#update item_link set system_id = 1 ;

#update item_link set installation_id = 1;

ALTER TABLE `item_link` ADD `link_type` VARCHAR(32) DEFAULT 'item' NOT NULL AFTER `parent_link_id` ;
ALTER TABLE `item_link` DROP INDEX `id`, ADD INDEX `id` (`id`, `item_id`, `parent_item_id`, `l`, `r`, `installation_id`, `parent_link_id`, `system_id`, `link_type`) ;

delete from item_keyword where name = 'url' or name = 'parent_url';

ALTER TABLE `item_keyword` ADD `id_value` int(10) UNSIGNED default NULL AFTER `value` ;