ALTER TABLE book_requests
    ADD COLUMN status ENUM('pending', 'granted', 'cancelled') NOT NULL DEFAULT 'pending' AFTER book_id;
