USE library_management;

-- Local-development fixture. Sign in with member.demo@librarydesk.test / MemberDemo123!
INSERT IGNORE INTO users (name, email, password_hash, role, status)
VALUES (
    'Morgan Member',
    'member.demo@librarydesk.test',
    '$2y$10$w9TH7pdSW4K.C54obcrSN.Zg2qBDQTq90q7fR.B0xHK2VhZSKshwe',
    'member',
    'active'
);

SET @member_id = (SELECT id FROM users WHERE email = 'member.demo@librarydesk.test' AND role = 'member');

INSERT IGNORE INTO books (isbn, title, author, category, total_copies, available_copies)
VALUES
    ('9781000000001', 'The Garden Atlas', 'Nora Fielding', 'Nature', 1, 0),
    ('9781000000002', 'A Map of Quiet Places', 'Elliot Rivers', 'Fiction', 1, 0),
    ('9781000000003', 'Small Worlds, Big Ideas', 'Amara Chen', 'Science', 1, 0),
    ('9781000000004', 'The Long Way Home', 'Jonas Mercer', 'Fiction', 1, 1);

SET @book_active_id = (SELECT id FROM books WHERE isbn = '9781000000001');
SET @book_soon_id = (SELECT id FROM books WHERE isbn = '9781000000002');
SET @book_overdue_id = (SELECT id FROM books WHERE isbn = '9781000000003');
SET @book_returned_id = (SELECT id FROM books WHERE isbn = '9781000000004');

INSERT INTO loans (user_id, book_id, borrowed_at, due_at, status)
SELECT @member_id, @book_active_id, DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY),
       DATE_ADD(CURRENT_DATE, INTERVAL 12 DAY), 'active'
WHERE @member_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM loans WHERE user_id = @member_id AND book_id = @book_active_id AND returned_at IS NULL
  );

INSERT INTO loans (user_id, book_id, borrowed_at, due_at, status)
SELECT @member_id, @book_soon_id, DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY),
       DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), 'active'
WHERE @member_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM loans WHERE user_id = @member_id AND book_id = @book_soon_id AND returned_at IS NULL
  );

INSERT INTO loans (user_id, book_id, borrowed_at, due_at, status)
SELECT @member_id, @book_overdue_id, DATE_SUB(CURRENT_DATE, INTERVAL 18 DAY),
       DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), 'overdue'
WHERE @member_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM loans WHERE user_id = @member_id AND book_id = @book_overdue_id AND returned_at IS NULL
  );

INSERT INTO loans (user_id, book_id, borrowed_at, due_at, returned_at, status)
SELECT @member_id, @book_returned_id, DATE_SUB(CURRENT_DATE, INTERVAL 45 DAY),
       DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 25 DAY), 'returned'
WHERE @member_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM loans WHERE user_id = @member_id AND book_id = @book_returned_id
  );
