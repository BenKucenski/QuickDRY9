CREATE TABLE `change_log` (
                              `id` int NOT NULL AUTO_INCREMENT,
                              `entity_class` varchar(255) NOT NULL,
                              `primary_key` varchar(255) NOT NULL,
                              `changes` text NOT NULL,
                              `user_id` int DEFAULT NULL,
                              `created_at` datetime NOT NULL,
                              `is_deleted` tinyint(1) NOT NULL,
                              `year` int DEFAULT NULL,
                              PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
