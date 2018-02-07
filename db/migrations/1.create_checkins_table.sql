CREATE TABLE `checkins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `latitude` FLOAT(12,8) DEFAULT NULL,
  `longitude` FLOAT(12,8) DEFAULT NULL,
  `message` VARCHAR(255) DEFAULT NULL,
  `speed` VARCHAR(255) DEFAULT NULL,
  `bearing` VARCHAR(255) DEFAULT NULL,
  `battery` VARCHAR(255) DEFAULT NULL,
  `altitude` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT NOW(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;