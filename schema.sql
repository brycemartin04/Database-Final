DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS offers;
DROP TABLE IF EXISTS markets;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    username VARCHAR(50) PRIMARY KEY,
    hashed_password VARCHAR(255) NOT NULL,
    cash_balance DECIMAL(10, 2) NOT NULL CHECK (cash_balance >= 0)
);

CREATE TABLE markets (
    market_id INT AUTO_INCREMENT PRIMARY KEY,
    event_description VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'trading',
    resolve_date DATE DEFAULT NULL,
    outcome NOT NULL DEFAULT 'pending'
);

CREATE TABLE offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    market_id INT NOT NULL,
    contract_type VARCHAR(10) NOT NULL,
    side VARCHAR(20) NOT NULL,
    price_per_share DECIMAL(4, 2) NOT NULL,
    quantity INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT offers_user_fk FOREIGN KEY (username) REFERENCES users(username),
    CONSTRAINT offers_market_fk FOREIGN KEY (market_id) REFERENCES markets(market_id),
    CONSTRAINT offers_price_chk CHECK (price_per_share >= 0.01 AND price_per_share <= 0.99),
    CONSTRAINT offers_quantity_chk CHECK (quantity > 0)
);

CREATE TABLE transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    market_id INT NOT NULL,
    contract_type VARCHAR(10) NOT NULL,
    side VARCHAR(20) NOT NULL,
    price DECIMAL(4, 2) NOT NULL,
    quantity INT NOT NULL,
    transacted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT transactions_user_fk FOREIGN KEY (username) REFERENCES users(username),
    CONSTRAINT transactions_market_fk FOREIGN KEY (market_id) REFERENCES markets(market_id),
    CONSTRAINT transactions_price_chk CHECK (price >= 0.01 AND price <= 0.99),
    CONSTRAINT transactions_quantity_chk CHECK (quantity > 0)
);
