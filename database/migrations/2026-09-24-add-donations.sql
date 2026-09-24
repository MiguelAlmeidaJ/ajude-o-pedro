CREATE TABLE IF NOT EXISTS donations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    public_token CHAR(40) NOT NULL UNIQUE,
    donor_name VARCHAR(120) NOT NULL,
    donor_phone VARCHAR(30) NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    INDEX idx_donations_campaign_status (campaign_id, status),
    CONSTRAINT fk_donations_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
