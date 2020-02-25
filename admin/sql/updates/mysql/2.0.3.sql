ALTER TABLE `#__sentinel_form` ADD `guid` VARCHAR(36) NOT NULL DEFAULT '' AFTER `alias`;

ALTER TABLE `#__sentinel_data_set` ADD `guid` VARCHAR(36) NOT NULL DEFAULT '' AFTER `asset_id`;
