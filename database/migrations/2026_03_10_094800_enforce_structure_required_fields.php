<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS trg_dioceses_required_insert;
DROP TRIGGER IF EXISTS trg_dioceses_required_update;
DROP TRIGGER IF EXISTS trg_parishes_required_insert;
DROP TRIGGER IF EXISTS trg_parishes_required_update;

CREATE TRIGGER trg_dioceses_required_insert
BEFORE INSERT ON dioceses
FOR EACH ROW
BEGIN
    SELECT RAISE(ABORT, 'dioceses.name is required') WHERE NEW.name IS NULL OR trim(NEW.name) = '';
    SELECT RAISE(ABORT, 'dioceses.archbishop_name is required') WHERE NEW.archbishop_name IS NULL OR trim(NEW.archbishop_name) = '';
    SELECT RAISE(ABORT, 'dioceses.established_year is required') WHERE NEW.established_year IS NULL;
    SELECT RAISE(ABORT, 'dioceses.address is required') WHERE NEW.address IS NULL OR trim(NEW.address) = '';
    SELECT RAISE(ABORT, 'dioceses.phone is required') WHERE NEW.phone IS NULL OR trim(NEW.phone) = '';
    SELECT RAISE(ABORT, 'dioceses.email is required') WHERE NEW.email IS NULL OR trim(NEW.email) = '';
    SELECT RAISE(ABORT, 'dioceses.country is required') WHERE NEW.country IS NULL OR trim(NEW.country) = '';
END;

CREATE TRIGGER trg_dioceses_required_update
BEFORE UPDATE ON dioceses
FOR EACH ROW
BEGIN
    SELECT RAISE(ABORT, 'dioceses.name is required') WHERE NEW.name IS NULL OR trim(NEW.name) = '';
    SELECT RAISE(ABORT, 'dioceses.archbishop_name is required') WHERE NEW.archbishop_name IS NULL OR trim(NEW.archbishop_name) = '';
    SELECT RAISE(ABORT, 'dioceses.established_year is required') WHERE NEW.established_year IS NULL;
    SELECT RAISE(ABORT, 'dioceses.address is required') WHERE NEW.address IS NULL OR trim(NEW.address) = '';
    SELECT RAISE(ABORT, 'dioceses.phone is required') WHERE NEW.phone IS NULL OR trim(NEW.phone) = '';
    SELECT RAISE(ABORT, 'dioceses.email is required') WHERE NEW.email IS NULL OR trim(NEW.email) = '';
    SELECT RAISE(ABORT, 'dioceses.country is required') WHERE NEW.country IS NULL OR trim(NEW.country) = '';
END;

CREATE TRIGGER trg_parishes_required_insert
BEFORE INSERT ON parishes
FOR EACH ROW
BEGIN
    SELECT RAISE(ABORT, 'parishes.diocese_id is required') WHERE NEW.diocese_id IS NULL;
    SELECT RAISE(ABORT, 'parishes.name is required') WHERE NEW.name IS NULL OR trim(NEW.name) = '';
    SELECT RAISE(ABORT, 'parishes.patron_saint is required') WHERE NEW.patron_saint IS NULL OR trim(NEW.patron_saint) = '';
    SELECT RAISE(ABORT, 'parishes.established_year is required') WHERE NEW.established_year IS NULL;
    SELECT RAISE(ABORT, 'parishes.address is required') WHERE NEW.address IS NULL OR trim(NEW.address) = '';
    SELECT RAISE(ABORT, 'parishes.phone is required') WHERE NEW.phone IS NULL OR trim(NEW.phone) = '';
    SELECT RAISE(ABORT, 'parishes.email is required') WHERE NEW.email IS NULL OR trim(NEW.email) = '';
END;

CREATE TRIGGER trg_parishes_required_update
BEFORE UPDATE ON parishes
FOR EACH ROW
BEGIN
    SELECT RAISE(ABORT, 'parishes.diocese_id is required') WHERE NEW.diocese_id IS NULL;
    SELECT RAISE(ABORT, 'parishes.name is required') WHERE NEW.name IS NULL OR trim(NEW.name) = '';
    SELECT RAISE(ABORT, 'parishes.patron_saint is required') WHERE NEW.patron_saint IS NULL OR trim(NEW.patron_saint) = '';
    SELECT RAISE(ABORT, 'parishes.established_year is required') WHERE NEW.established_year IS NULL;
    SELECT RAISE(ABORT, 'parishes.address is required') WHERE NEW.address IS NULL OR trim(NEW.address) = '';
    SELECT RAISE(ABORT, 'parishes.phone is required') WHERE NEW.phone IS NULL OR trim(NEW.phone) = '';
    SELECT RAISE(ABORT, 'parishes.email is required') WHERE NEW.email IS NULL OR trim(NEW.email) = '';
END;
SQL);

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS trg_dioceses_required_insert;
DROP TRIGGER IF EXISTS trg_dioceses_required_update;
DROP TRIGGER IF EXISTS trg_parishes_required_insert;
DROP TRIGGER IF EXISTS trg_parishes_required_update;

CREATE TRIGGER trg_dioceses_required_insert
BEFORE INSERT ON dioceses
FOR EACH ROW
BEGIN
    IF NEW.name IS NULL OR TRIM(NEW.name) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.name is required'; END IF;
    IF NEW.archbishop_name IS NULL OR TRIM(NEW.archbishop_name) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.archbishop_name is required'; END IF;
    IF NEW.established_year IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.established_year is required'; END IF;
    IF NEW.address IS NULL OR TRIM(NEW.address) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.address is required'; END IF;
    IF NEW.phone IS NULL OR TRIM(NEW.phone) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.phone is required'; END IF;
    IF NEW.email IS NULL OR TRIM(NEW.email) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.email is required'; END IF;
    IF NEW.country IS NULL OR TRIM(NEW.country) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.country is required'; END IF;
END;

CREATE TRIGGER trg_dioceses_required_update
BEFORE UPDATE ON dioceses
FOR EACH ROW
BEGIN
    IF NEW.name IS NULL OR TRIM(NEW.name) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.name is required'; END IF;
    IF NEW.archbishop_name IS NULL OR TRIM(NEW.archbishop_name) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.archbishop_name is required'; END IF;
    IF NEW.established_year IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.established_year is required'; END IF;
    IF NEW.address IS NULL OR TRIM(NEW.address) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.address is required'; END IF;
    IF NEW.phone IS NULL OR TRIM(NEW.phone) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.phone is required'; END IF;
    IF NEW.email IS NULL OR TRIM(NEW.email) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.email is required'; END IF;
    IF NEW.country IS NULL OR TRIM(NEW.country) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'dioceses.country is required'; END IF;
END;

CREATE TRIGGER trg_parishes_required_insert
BEFORE INSERT ON parishes
FOR EACH ROW
BEGIN
    IF NEW.diocese_id IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.diocese_id is required'; END IF;
    IF NEW.name IS NULL OR TRIM(NEW.name) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.name is required'; END IF;
    IF NEW.patron_saint IS NULL OR TRIM(NEW.patron_saint) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.patron_saint is required'; END IF;
    IF NEW.established_year IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.established_year is required'; END IF;
    IF NEW.address IS NULL OR TRIM(NEW.address) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.address is required'; END IF;
    IF NEW.phone IS NULL OR TRIM(NEW.phone) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.phone is required'; END IF;
    IF NEW.email IS NULL OR TRIM(NEW.email) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.email is required'; END IF;
END;

CREATE TRIGGER trg_parishes_required_update
BEFORE UPDATE ON parishes
FOR EACH ROW
BEGIN
    IF NEW.diocese_id IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.diocese_id is required'; END IF;
    IF NEW.name IS NULL OR TRIM(NEW.name) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.name is required'; END IF;
    IF NEW.patron_saint IS NULL OR TRIM(NEW.patron_saint) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.patron_saint is required'; END IF;
    IF NEW.established_year IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.established_year is required'; END IF;
    IF NEW.address IS NULL OR TRIM(NEW.address) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.address is required'; END IF;
    IF NEW.phone IS NULL OR TRIM(NEW.phone) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.phone is required'; END IF;
    IF NEW.email IS NULL OR TRIM(NEW.email) = '' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'parishes.email is required'; END IF;
END;
SQL);
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS trg_dioceses_required_insert;
DROP TRIGGER IF EXISTS trg_dioceses_required_update;
DROP TRIGGER IF EXISTS trg_parishes_required_insert;
DROP TRIGGER IF EXISTS trg_parishes_required_update;
SQL);
            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS trg_dioceses_required_insert;
DROP TRIGGER IF EXISTS trg_dioceses_required_update;
DROP TRIGGER IF EXISTS trg_parishes_required_insert;
DROP TRIGGER IF EXISTS trg_parishes_required_update;
SQL);
        }
    }
};
