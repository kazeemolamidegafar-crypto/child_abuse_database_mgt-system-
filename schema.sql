-- Computerized Child Abuse Database Management System
-- MySQL schema, mirroring the design in Chapter Three / Appendix A of the thesis.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    full_name      VARCHAR(150) NOT NULL,
    username       VARCHAR(80)  NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    role           ENUM('administrator','caseworker','intake_officer') NOT NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS children (
    child_id              INT AUTO_INCREMENT PRIMARY KEY,
    child_reference_code  VARCHAR(40) NOT NULL UNIQUE,
    full_name             VARCHAR(150) NOT NULL,
    date_of_birth         DATE NULL,
    guardian_contact      VARCHAR(150) NULL,
    address                TEXT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cases (
    case_id                 INT AUTO_INCREMENT PRIMARY KEY,
    case_reference_number   VARCHAR(40) NOT NULL UNIQUE,
    child_id                INT NOT NULL,
    category_of_concern     VARCHAR(80) NOT NULL,
    date_reported           DATE NOT NULL,
    reported_by_user_id     INT NOT NULL,
    assigned_caseworker_id  INT NULL,
    status                  ENUM('Reported','Under Review','Referred','Under Investigation','Closed')
                             NOT NULL DEFAULT 'Reported',
    summary                 TEXT NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cases_child     FOREIGN KEY (child_id) REFERENCES children(child_id),
    CONSTRAINT fk_cases_reporter  FOREIGN KEY (reported_by_user_id) REFERENCES users(user_id),
    CONSTRAINT fk_cases_assignee  FOREIGN KEY (assigned_caseworker_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS referrals (
    referral_id        INT AUTO_INCREMENT PRIMARY KEY,
    case_id             INT NOT NULL,
    referred_to         VARCHAR(80) NOT NULL,
    date_referred       DATE NOT NULL,
    referral_status     ENUM('Pending','Acknowledged','Resolved') NOT NULL DEFAULT 'Pending',
    outcome_notes       TEXT NULL,
    created_by_user_id  INT NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_referrals_case    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    CONSTRAINT fk_referrals_creator FOREIGN KEY (created_by_user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS case_notes (
    note_id         INT AUTO_INCREMENT PRIMARY KEY,
    case_id          INT NOT NULL,
    author_user_id   INT NOT NULL,
    note_text        TEXT NOT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notes_case   FOREIGN KEY (case_id) REFERENCES cases(case_id),
    CONSTRAINT fk_notes_author FOREIGN KEY (author_user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
    log_id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NULL,
    username        VARCHAR(80) NULL,
    action          VARCHAR(60) NOT NULL,
    target_entity   VARCHAR(60) NULL,
    target_id       INT NULL,
    details         TEXT NULL,
    timestamp       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address      VARCHAR(45) NULL,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
