-- Migration to add template versions table
CREATE TABLE IF NOT EXISTS template_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    version INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY (template_id, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add tag column and index
ALTER TABLE template_versions 
ADD COLUMN tag VARCHAR(50) NULL AFTER created_by,
ADD INDEX idx_template_tag (tag);

-- Add comment to explain the table
ALTER TABLE template_versions COMMENT 'Stores historical versions of email templates';
