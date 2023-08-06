
DROP TABLE IF EXISTS `staff_roles`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `roles`;


CREATE TABLE `staff`(
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `active` TINYINT(1) DEFAULT NULL,
    `invitation_hash` CHAR(128) DEFAULT NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


CREATE TABLE `roles`(
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


CREATE TABLE `staff_roles`(
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,

    FOREIGN KEY(`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY(`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


INSERT INTO `staff` SET
    `first_name` = 'Super Admin',
    `email` = 'admin@example.com',
    -- password_hash('123456', PASSWORD_DEFAULT);
    `password` = '$2y$10$o/j10hvKN8rQ9checYI/RupDPaRyRQMb.mOh/9qhLU.aLNHjd8.vG',
    `created_at` = NOW(),
    `last_login` = NOW(),
    `active` = 1,
    `invitation_hash` = NULL;


INSERT INTO `roles` SET
    `name` = 'super_admin',
    `description` = 'Super Admin';


INSERT INTO `staff_roles` SET
    `staff_id` = 1,
    `role_id` = 1;
