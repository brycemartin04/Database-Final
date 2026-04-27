INSERT INTO users (username, hashed_password, cash_balance) VALUES
    ('alice', 'sha256_demo_alice', 104.20),
    ('bob', 'sha256_demo_bob', 93.10),
    ('carol', 'sha256_demo_carol', 121.70),
    ('dave', 'sha256_demo_dave', 88.50);

INSERT INTO markets (market_id, event_description, status, resolve_date, outcome) VALUES
    (1, 'Will the Federal Reserve cut rates before September 2026?', 'trading', NULL, 'pending'),
    (2, 'Will a crewed mission land on Mars before 2035?', 'trading', NULL, 'pending'),
    (3, 'Will the Chiefs win the championship this season?', 'resolved', '2026-03-20', 'no');

INSERT INTO transactions (username, market_id, contract_type, side, price, quantity, transacted_at) VALUES
    ('alice', 1, 'yes', 'buy', 0.44, 20, '2026-04-10 09:10:00'),
    ('bob', 1, 'yes', 'sell', 0.44, 20, '2026-04-10 09:10:00'),
    ('carol', 1, 'no', 'buy', 0.57, 12, '2026-04-10 10:05:00'),
    ('dave', 1, 'no', 'sell', 0.57, 12, '2026-04-10 10:05:00'),
    ('bob', 2, 'yes', 'buy', 0.26, 15, '2026-04-11 14:25:00'),
    ('carol', 2, 'yes', 'sell', 0.26, 15, '2026-04-11 14:25:00'),
    ('alice', 3, 'yes', 'buy', 0.61, 10, '2026-02-08 11:15:00'),
    ('dave', 3, 'yes', 'sell', 0.61, 10, '2026-02-08 11:15:00'),
    ('carol', 3, 'no', 'buy', 0.42, 9, '2026-02-08 12:20:00'),
    ('bob', 3, 'no', 'sell', 0.42, 9, '2026-02-08 12:20:00');

INSERT INTO offers (username, market_id, contract_type, side, price_per_share, quantity, created_at) VALUES
    ('alice', 1, 'yes', 'buy', 0.48, 10, '2026-04-15 08:00:00'),
    ('bob', 1, 'yes', 'sell', 0.50, 7, '2026-04-15 08:05:00'),
    ('carol', 1, 'no', 'buy', 0.53, 8, '2026-04-15 08:10:00'),
    ('dave', 2, 'yes', 'sell', 0.31, 11, '2026-04-15 09:00:00'),
    ('alice', 2, 'no', 'buy', 0.68, 5, '2026-04-15 09:15:00');

