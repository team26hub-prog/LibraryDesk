CREATE TABLE IF NOT EXISTS book_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    book_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_book_requests_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_book_requests_book FOREIGN KEY (book_id) REFERENCES books (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_book_requests_user_book (user_id, book_id),
    INDEX idx_book_requests_book (book_id),
    INDEX idx_book_requests_created (created_at)
);
