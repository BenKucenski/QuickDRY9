CREATE TABLE `curl_log` (
                            `id` int NOT NULL AUTO_INCREMENT,
                            `path` varchar(255) DEFAULT NULL,
                            `params` text,
                            `method` varchar(45) DEFAULT NULL,
                            `created_at` datetime DEFAULT NULL,
                            `duration` float DEFAULT NULL,
                            `created_date` date DEFAULT NULL,
                            `host` varchar(255) DEFAULT NULL,
                            `endpoint` varchar(255) DEFAULT NULL,
                            `account` varchar(255) DEFAULT NULL,
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
