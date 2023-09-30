CREATE TABLE `queue` (
  `id` int(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `class` varchar(150) NOT NULL,
  `action` varchar(150) NOT NULL,
  `parameter` longtext NULL,
  `status` tinyint(0) UNSIGNED NOT NULL DEFAULT 0,
  `loop` int(0) UNSIGNED NOT NULL DEFAULT 0,
  `run_at` datetime(0) NULL DEFAULT NULL,
  `created_at` datetime(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0),
  `updated_at` datetime(0) NOT NULL DEFAULT CURRENT_TIMESTAMP(0) ON UPDATE CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE
);