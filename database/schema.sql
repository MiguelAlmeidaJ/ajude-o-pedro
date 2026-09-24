CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('dev','admin') NOT NULL DEFAULT 'admin',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(180) NOT NULL UNIQUE,
    title VARCHAR(180) NOT NULL,
    subtitle VARCHAR(255) NULL,
    story TEXT NULL,
    goal_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    number_price DECIMAL(10,2) NOT NULL DEFAULT 10,
    total_numbers INT UNSIGNED NOT NULL DEFAULT 1000,
    pix_key VARCHAR(190) NOT NULL,
    pix_receiver_name VARCHAR(25) NOT NULL,
    pix_receiver_city VARCHAR(15) NOT NULL,
    hero_image VARCHAR(255) NULL,
    gallery_json JSON NULL,
    whatsapp VARCHAR(30) NULL,
    instagram VARCHAR(100) NULL,
    draw_date DATE NULL,
    status ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    public_token CHAR(40) NOT NULL UNIQUE,
    customer_name VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    customer_email VARCHAR(190) NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','paid','cancelled','expired') NOT NULL DEFAULT 'pending',
    reserved_until DATETIME NOT NULL,
    payment_reference VARCHAR(80) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    INDEX idx_orders_campaign_status (campaign_id, status),
    INDEX idx_orders_reserved_until (reserved_until),
    CONSTRAINT fk_orders_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS raffle_numbers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    number INT UNSIGNED NOT NULL,
    status ENUM('available','reserved','paid') NOT NULL DEFAULT 'available',
    order_id BIGINT UNSIGNED NULL,
    reserved_until DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_campaign_number (campaign_id, number),
    INDEX idx_raffle_status (campaign_id, status),
    INDEX idx_raffle_order (order_id),
    CONSTRAINT fk_number_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_number_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_numbers (
    order_id BIGINT UNSIGNED NOT NULL,
    raffle_number_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (order_id, raffle_number_id),
    UNIQUE KEY uq_raffle_number_order (raffle_number_id),
    CONSTRAINT fk_order_numbers_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_numbers_number FOREIGN KEY (raffle_number_id) REFERENCES raffle_numbers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;