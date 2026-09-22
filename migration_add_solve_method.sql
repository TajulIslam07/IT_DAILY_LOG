-- Run this ONCE against your EXISTING database to add the new "Solve Method"
-- column. This does NOT delete or change any of your existing rows/data —
-- it just adds one new column (empty for old entries until you edit them).
--
-- How to run it:
--   Open http://localhost/phpmyadmin, select the "it_support_log" database,
--   click the "SQL" tab, paste the line below, and click "Go".
--   OR from a terminal: mysql -u root -p it_support_log < migration_add_solve_method.sql
--
-- If you are setting this app up FRESH (brand new database), you do NOT
-- need this file — just run schema.sql, which already includes this column.

USE it_support_log;
ALTER TABLE problems ADD COLUMN solve_method VARCHAR(20) DEFAULT '' AFTER solution;
