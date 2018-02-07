ALTER TABLE `checkins` ADD COLUMN
  `24_hour_warning` TINYINT(1) DEFAULT 0;
ALTER TABLE `checkins` ADD COLUMN
  `48_hour_warning` TINYINT(1) DEFAULT 0;