CREATE TABLE `api_user` (
                            `id` int NOT NULL AUTO_INCREMENT,
                            `email_address` varchar(255) DEFAULT NULL,
                            `account` varchar(255) DEFAULT NULL,
                            `account_type` varchar(255) DEFAULT NULL,
                            `client_id` varchar(255) DEFAULT NULL,
                            `client_secret` varchar(255) DEFAULT NULL,
                            `issued_date` datetime DEFAULT NULL,
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
