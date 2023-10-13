CREATE TABLE `jobs` (
  `id` int(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `class` varchar(150) NOT NULL,
  `method` varchar(150) NOT NULL,
  `parameter` longtext NOT NULL,
  `status` ENUM('PENDING', 'RUNNING', 'SUCCESS', 'FAILURE') NOT NULL DEFAULT 'PENDING',
  `loop` int(0) UNSIGNED NOT NULL DEFAULT 0,
  `frequency` ENUM('SECOND', 'MINUTE', 'HOUR', 'DAY', 'WEEK', 'MONTH', 'YEAR') NULL,
  `run_at` datetime(0) NULL DEFAULT NULL,
  `created_at` datetime(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),
  `updated_at` datetime(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE
);