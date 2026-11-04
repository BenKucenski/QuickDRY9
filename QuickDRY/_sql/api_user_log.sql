CREATE TABLE `api_user_log` (
                                `id` int NOT NULL AUTO_INCREMENT,
                                `client_id` varchar(255) DEFAULT NULL,
                                `created_at` datetime DEFAULT NULL,
                                `remote_addr` varchar(64) DEFAULT NULL,
                                `is_success` int DEFAULT NULL,
                                PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
