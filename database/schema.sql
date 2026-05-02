DROP TABLE IF EXISTS declaration_assets;
DROP TABLE IF EXISTS declarations;
DROP TABLE IF EXISTS officials;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS parties;

CREATE TABLE parties (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL UNIQUE,
    short_name  VARCHAR(50),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE positions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150) NOT NULL UNIQUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'official', 'user') DEFAULT 'user',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE officials (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NULL,
    first_name    VARCHAR(100) NOT NULL,
    last_name     VARCHAR(100) NOT NULL,
    phone         VARCHAR(30),
    district      VARCHAR(100),
    party_id      INT NULL,
    position_id   INT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL
);

CREATE TABLE declarations (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    official_id       INT NOT NULL,
    declaration_year  YEAR NOT NULL,
    status            ENUM('draft', 'submitted') DEFAULT 'draft',
    submitted_at      TIMESTAMP NULL DEFAULT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (official_id, declaration_year, status),
    FOREIGN KEY (official_id) REFERENCES officials(id) ON DELETE CASCADE
);

CREATE TABLE declaration_assets (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    declaration_id  INT NOT NULL,
    category        VARCHAR(100) NOT NULL,
    description     VARCHAR(255) NOT NULL,
    amount          DECIMAL(15,2) DEFAULT 0.00,
    currency        VARCHAR(10) DEFAULT 'EUR',
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (declaration_id) REFERENCES declarations(id) ON DELETE CASCADE
);