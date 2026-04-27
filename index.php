<?php

$host = '10.1.1.100';
$username = 'bryce';
$password = '1234';
$database = 'final';
$port = 3306;

/* $host = ''; */
/* $username = ''; */
/* $password = ''; */
/* $database = ''; */
/* $port = ; */

// executes query and returns an array
function run($conn, $query)
{
    $result = $conn->query($query);
    if ($result instanceof mysqli_result) {
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $rows;
    }

    return [];
}

// cleans user input
// num -> string
// uses real escape string to prevent injections
function clean($conn, $value)
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    return "'" . $conn->real_escape_string((string) $value) . "'";
}

// database connection
$conn = new mysqli($host, $username, $password, $database, $port);
if ($conn ->connect_error)
       die('Could not connect: ' . $conn->connect_error);

// add to database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    try{
        switch ($action) {
            case 'add_user':
                run(
                    $conn,
                    'INSERT INTO users (username, hashed_password, cash_balance) VALUES ('
                    . clean($conn, trim($_POST['username'] ?? '')) . ', '
                    . clean($conn, trim($_POST['hashed_password'] ?? '')) . ', '
                    . clean($conn, (float) ($_POST['cash_balance'] ?? 0)) . ')'
                );
                $messages[] = 'User added.';
                break;

            case 'add_market':
                $resolveDate = trim($_POST['resolve_date'] ?? '');
                if ($resolveDate === '') {
                    $resolveDate = null;
                }

                run(
                    $conn,
                    'INSERT INTO markets (event_description, status, resolve_date, outcome) VALUES ('
                    . clean($conn, trim($_POST['event_description'] ?? '')) . ', '
                    . clean($conn, trim($_POST['status'] ?? '')) . ', '
                    . clean($conn, $resolveDate) . ', '
                    . clean($conn, trim($_POST['outcome'] ?? '')) . ')'
                );
                $messages[] = 'Market added.';
                break;

            case 'add_offer':
                run(
                    $conn,
                    'INSERT INTO offers (username, market_id, contract_type, side, price_per_share, quantity) VALUES ('
                    . clean($conn, trim($_POST['username'] ?? '')) . ', '
                    . clean($conn, (int) ($_POST['market_id'] ?? 0)) . ', '
                    . clean($conn, trim($_POST['contract_type'] ?? '')) . ', '
                    . clean($conn, trim($_POST['side'] ?? '')) . ', '
                    . clean($conn, (float) ($_POST['price_per_share'] ?? 0)) . ', '
                    . clean($conn, (int) ($_POST['quantity'] ?? 0)) . ')'
                );
                $messages[] = 'Offer added.';
                break;

            case 'add_transaction':
                run(
                    $conn,
                    'INSERT INTO transactions (username, market_id, contract_type, side, price, quantity) VALUES ('
                    . clean($conn, trim($_POST['username'] ?? '')) . ', '
                    . clean($conn, (int) ($_POST['market_id'] ?? 0)) . ', '
                    . clean($conn, trim($_POST['contract_type'] ?? '')) . ', '
                    . clean($conn, trim($_POST['side'] ?? '')) . ', '
                    . clean($conn, (float) ($_POST['price'] ?? 0)) . ', '
                    . clean($conn, (int) ($_POST['quantity'] ?? 0)) . ')'
                );
                $messages[] = 'Transaction added.';
                break;

            // handle delete from full table at the bottom
            case 'delete_row':
                $table = trim($_POST['table'] ?? '');
                $id = trim($_POST['id'] ?? '');

                if ($table === 'users') {
                    run($conn, 'DELETE FROM users WHERE username = ' . clean($conn, $id));
                } elseif ($table === 'markets') {
                    run($conn, 'DELETE FROM markets WHERE market_id = ' . clean($conn, (int) $id));
                } elseif ($table === 'offers') {
                    run($conn, 'DELETE FROM offers WHERE offer_id = ' . clean($conn, (int) $id));
                } elseif ($table === 'transactions') {
                    run($conn, 'DELETE FROM transactions WHERE transaction_id = ' . clean($conn, (int) $id));
                } else {
                    throw new RuntimeException('Unknown table.');
                }

                $messages[] = 'Row deleted.';
                break;
        }
    } catch (Throwable $e) {
    }
}

$q1Status = trim($_GET['q1_status'] ?? '');
$q1Keyword = trim($_GET['q1_keyword'] ?? '');
$q1Sql = 'SELECT market_id, event_description, status, resolve_date, outcome FROM markets WHERE 1 = 1';
if ($q1Status !== '') {
    $q1Sql .= ' AND status = ' . clean($conn, $q1Status);
}
if ($q1Keyword !== '') {
    $q1Sql .= ' AND event_description LIKE ' . clean($conn, '%' . $q1Keyword . '%');
}
$q1Sql .= ' ORDER BY market_id';
$query1Rows = run($conn, $q1Sql);

$q2User = trim($_GET['q2_user'] ?? '');
$q2Side = trim($_GET['q2_side'] ?? '');
$q2Sql = "SELECT o.offer_id, o.username, m.event_description, o.contract_type, o.side, o.price_per_share, o.quantity, o.created_at
          FROM offers o
          JOIN markets m ON o.market_id = m.market_id";
if ($q2User !== '') {
    $q2Sql .= ' AND o.username = ' . clean($conn, $q2User);
}
if ($q2Side !== '') {
    $q2Sql .= ' AND o.side = ' . clean($conn, $q2Side);
}
$q2Sql .= ' ORDER BY o.created_at DESC, o.offer_id DESC';
$query2Rows = run($conn, $q2Sql);

$q3User = trim($_GET['q3_user'] ?? '');
$q3MarketId = trim($_GET['q3_market_id'] ?? '');
$q3Sql = "SELECT t.transaction_id, t.username, m.event_description, t.contract_type, t.side, t.price, t.quantity, t.transacted_at
          FROM transactions t
          JOIN markets m ON t.market_id = m.market_id";
if ($q3User !== '') {
    $q3Sql .= ' AND t.username = ' . clean($conn, $q3User);
}
if ($q3MarketId !== '' && ctype_digit($q3MarketId)) {
    $q3Sql .= ' AND t.market_id = ' . clean($conn, (int) $q3MarketId);
}
$q3Sql .= ' ORDER BY t.transacted_at DESC, t.transaction_id DESC';
$query3Rows = run($conn, $q3Sql);

$q4Status = trim($_GET['q4_status'] ?? '');
$q4MinOffers = trim($_GET['q4_min_offers'] ?? '');
$q4Sql = "SELECT m.market_id,
                 m.event_description,
                 m.status,
                 COUNT(DISTINCT o.offer_id) AS open_offers,
                 COUNT(DISTINCT t.transaction_id) AS total_transactions
          FROM markets m
          LEFT JOIN offers o ON m.market_id = o.market_id
          LEFT JOIN transactions t ON m.market_id = t.market_id";
if ($q4Status !== '') {
    $q4Sql .= ' AND m.status = ' . clean($conn, $q4Status);
}
$q4Sql .= ' GROUP BY m.market_id, m.event_description, m.status';
if ($q4MinOffers !== '' && ctype_digit($q4MinOffers)) {
    $q4Sql .= ' HAVING COUNT(DISTINCT o.offer_id) >= ' . clean($conn, (int) $q4MinOffers);
}
$q4Sql .= ' ORDER BY m.market_id';
$query4Rows = run($conn, $q4Sql);

$q5MinCash = trim($_GET['q5_min_cash'] ?? '');
$q5MinCashValue = is_numeric($q5MinCash) ? (float) $q5MinCash : 0.0;
$query5Rows = run(
    $conn,
    'SELECT username, cash_balance FROM users WHERE cash_balance >= '
    . clean($conn, $q5MinCashValue)
    . ' ORDER BY cash_balance DESC, username ASC'
);

// display all databases

$users = run($conn, 'SELECT username, hashed_password, cash_balance FROM users ORDER BY username');
$markets = run($conn, 'SELECT market_id, event_description, status, resolve_date, outcome FROM markets ORDER BY market_id');
$offers = run($conn, 'SELECT offer_id, username, market_id, contract_type, side, price_per_share, quantity, created_at FROM offers ORDER BY offer_id DESC');
$transactions = run($conn, 'SELECT transaction_id, username, market_id, contract_type, side, price, quantity, transacted_at FROM transactions ORDER BY transaction_id DESC');

$userOptions = array_column($users, 'username');
$marketOptions = array_column($markets, 'market_id');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prediction Market</title>

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }
        .page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }
        .hero, .card {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }
        form {
            display: grid;
            gap: 10px;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font: inherit;
        }
        button {
            background: #2563eb;
            color: #fff;
            cursor: pointer;
        }
        button.delete {
            background: #dc2626;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 14px;
        }
        th, td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        th {
            background: #f9fafb;
        }
        .inline-form {
            margin: 0;
        }
        .query-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 16px;
        }
        .stat {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px;
        }
        .small {
            color: #6b7280;
            font-size: 14px;
        }
        h1 {
            text-align:center;
        }
    </style>
</head>
<body>
<div class="page">

    <div class="hero">
        <h1>Prediction Market</h1>
        <div class="stats">
            <div class="stat"><strong>Users</strong><br><?= (count($users)) ?></div>
            <div class="stat"><strong>Markets</strong><br><?= (count($markets)) ?></div>
            <div class="stat"><strong>Offers</strong><br><?= (count($offers)) ?></div>
            <div class="stat"><strong>Transactions</strong><br><?= (count($transactions)) ?></div>
        </div>
    </div>

        <div class="grid">
            <div class="card">
                <h2>Add User</h2>
                <form method="post">
                    <input type="hidden" name="action" value="add_user">
                    <input name="username" placeholder="username" required>
                    <input name="hashed_password" placeholder="hashed_password" required>
                    <input name="cash_balance" type="number" step="0.01" min="0" placeholder="cash_balance" required>
                    <button type="submit">Add User</button>
                </form>
            </div>

            <div class="card">
                <h2>Add Market</h2>
                <form method="post">
                    <input type="hidden" name="action" value="add_market">
                    <input name="event_description" placeholder="event description" required>
                    <select name="status">
                        <option value="trading">trading</option>
                        <option value="resolved">resolved</option>
                    </select>
                    <input name="resolve_date" type="date">
                    <select name="outcome">
                        <option value="pending">pending</option>
                        <option value="yes">yes</option>
                        <option value="no">no</option>
                    </select>
                    <button type="submit">Add Market</button>
                </form>
            </div>

            <div class="card">
                <h2>Add Offer</h2>
                <form method="post">
                    <input type="hidden" name="action" value="add_offer">
                    <select name="username" required>
                        <option value="">select user</option>
                        <?php foreach ($userOptions as $user): ?>
                            <option value="<?= ($user) ?>"><?= ($user) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="market_id" required>
                        <option value="">select market</option>
                        <?php foreach ($marketOptions as $marketId): ?>
                            <option value="<?= ($marketId) ?>"><?= ($marketId) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="contract_type">
                        <option value="yes">yes</option>
                        <option value="no">no</option>
                    </select>
                    <select name="side">
                        <option value="buy">buy</option>
                        <option value="sell">sell</option>
                    </select>
                    <input name="price_per_share" type="number" step="0.01" min="0.01" max="0.99" placeholder="price per share" required>
                    <input name="quantity" type="number" min="1" placeholder="quantity" required>
                    <button type="submit">Add Offer</button>
                </form>
            </div>

            <div class="card">
                <h2>Add Transaction</h2>
                <form method="post">
                    <input type="hidden" name="action" value="add_transaction">
                    <select name="username" required>
                        <option value="">select user</option>
                        <?php foreach ($userOptions as $user): ?>
                            <option value="<?= ($user) ?>"><?= ($user) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="market_id" required>
                        <option value="">select market</option>
                        <?php foreach ($marketOptions as $marketId): ?>
                            <option value="<?= ($marketId) ?>"><?= ($marketId) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="contract_type">
                        <option value="yes">yes</option>
                        <option value="no">no</option>
                    </select>
                    <select name="side">
                        <option value="buy">buy</option>
                        <option value="sell">sell</option>
                    </select>
                    <input name="price" type="number" step="0.01" min="0.01" max="0.99" placeholder="price" required>
                    <input name="quantity" type="number" min="1" placeholder="quantity" required>
                    <button type="submit">Add Transaction</button>
                </form>
            </div>
        </div>
    <div class="query-grid">
        <div class="card">
            <h2>View Markets</h2>
            <form method="get">
                <select name="q1_status">
                    <option value="">any status</option>
                    <option value="trading" <?= $q1Status === 'trading' ? 'selected' : '' ?>>trading</option>
                    <option value="resolved" <?= $q1Status === 'resolved' ? 'selected' : '' ?>>resolved</option>
                </select>
                <input name="q1_keyword" value="<?= ($q1Keyword) ?>" placeholder="keyword in event description">
                <button type="submit">Run Query 1</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event</th>
                        <th>Status</th>
                        <th>Resolve Date</th>
                        <th>Outcome</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($query1Rows as $row): ?>
                        <tr>
                            <td><?= ($row['market_id']) ?></td>
                            <td><?= ($row['event_description']) ?></td>
                            <td><?= ($row['status']) ?></td>
                            <td><?= ($row['resolve_date']) ?></td>
                            <td><?= ($row['outcome']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>View Offers On Each Market</h2>
            <form method="get">
                <select name="q2_user">
                    <option value="">any user</option>
                    <?php foreach ($userOptions as $user): ?>
                        <option value="<?= ($user) ?>" <?= $q2User === $user ? 'selected' : '' ?>><?= ($user) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="q2_side">
                    <option value="">any side</option>
                    <option value="buy" <?= $q2Side === 'buy' ? 'selected' : '' ?>>buy</option>
                    <option value="sell" <?= $q2Side === 'sell' ? 'selected' : '' ?>>sell</option>
                </select>
                <button type="submit">Run Query 2</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Offer ID</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Type</th>
                        <th>Side</th>
                        <th>Price</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($query2Rows as $row): ?>
                        <tr>
                            <td><?= ($row['offer_id']) ?></td>
                            <td><?= ($row['username']) ?></td>
                            <td><?= ($row['event_description']) ?></td>
                            <td><?= ($row['contract_type']) ?></td>
                            <td><?= ($row['side']) ?></td>
                            <td>$<?= (number_format((float) $row['price_per_share'], 2)) ?></td>
                            <td><?= ($row['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>View Transactions On Each Market</h2>
            <form method="get">
                <select name="q3_user">
                    <option value="">any user</option>
                    <?php foreach ($userOptions as $user): ?>
                        <option value="<?= ($user) ?>" <?= $q3User === $user ? 'selected' : '' ?>><?= ($user) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="q3_market_id">
                    <option value="">any market</option>
                    <?php foreach ($marketOptions as $marketId): ?>
                        <option value="<?= ($marketId) ?>" <?= $q3MarketId === (string) $marketId ? 'selected' : '' ?>><?= ($marketId) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Run Query 3</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Txn ID</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Type</th>
                        <th>Side</th>
                        <th>Price</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($query3Rows as $row): ?>
                        <tr>
                            <td><?= ($row['transaction_id']) ?></td>
                            <td><?= ($row['username']) ?></td>
                            <td><?= ($row['event_description']) ?></td>
                            <td><?= ($row['contract_type']) ?></td>
                            <td><?= ($row['side']) ?></td>
                            <td>$<?= (number_format((float) $row['price'], 2)) ?></td>
                            <td><?= ($row['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Number of Offers and Transactions in Each Market</h2>
            <form method="get">
                <select name="q4_status">
                    <option value="">any status</option>
                    <option value="trading" <?= $q4Status === 'trading' ? 'selected' : '' ?>>trading</option>
                    <option value="resolved" <?= $q4Status === 'resolved' ? 'selected' : '' ?>>resolved</option>
                </select>
                <input name="q4_min_offers" type="number" min="0" value="<?= ($q4MinOffers) ?>" placeholder="minimum open offers">
                <button type="submit">Run Query 4</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Market</th>
                        <th>Event</th>
                        <th>Status</th>
                        <th>Open Offers</th>
                        <th>Transactions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($query4Rows as $row): ?>
                        <tr>
                            <td><?= ($row['market_id']) ?></td>
                            <td><?= ($row['event_description']) ?></td>
                            <td><?= ($row['status']) ?></td>
                            <td><?= ($row['open_offers']) ?></td>
                            <td><?= ($row['total_transactions']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>View Users Above a Certain Balance</h2>
            <form method="get">
                <input name="q5_min_cash" type="number" step="0.01" min="0" value="<?= ($q5MinCash) ?>" placeholder="minimum cash balance">
                <button type="submit">Run Query 5</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Cash Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($query5Rows as $row): ?>
                        <tr>
                            <td><?= ($row['username']) ?></td>
                            <td>$<?= (number_format((float) $row['cash_balance'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- DISPLAY ALL TABLES -->
    <div class="card">
        <h2>Users</h2>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Hashed Password</th>
                    <th>Cash Balance</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td><?= ($row['username']) ?></td>
                        <td><?= ($row['hashed_password']) ?></td>
                        <td>$<?= (number_format((float) $row['cash_balance'], 2)) ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="action" value="delete_row">
                                <input type="hidden" name="table" value="users">
                                <input type="hidden" name="id" value="<?= ($row['username']) ?>">
                                <button class="delete" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Markets</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Event</th>
                    <th>Status</th>
                    <th>Resolve Date</th>
                    <th>Outcome</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($markets as $row): ?>
                    <tr>
                        <td><?= ($row['market_id']) ?></td>
                        <td><?= ($row['event_description']) ?></td>
                        <td><?= ($row['status']) ?></td>
                        <td><?= ($row['resolve_date']) ?></td>
                        <td><?= ($row['outcome']) ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="action" value="delete_row">
                                <input type="hidden" name="table" value="markets">
                                <input type="hidden" name="id" value="<?= ($row['market_id']) ?>">
                                <button class="delete" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Offers</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Market</th>
                    <th>Type</th>
                    <th>Side</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Created</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($offers as $row): ?>
                    <tr>
                        <td><?= ($row['offer_id']) ?></td>
                        <td><?= ($row['username']) ?></td>
                        <td><?= ($row['market_id']) ?></td>
                        <td><?= ($row['contract_type']) ?></td>
                        <td><?= ($row['side']) ?></td>
                        <td>$<?= (number_format((float) $row['price_per_share'], 2)) ?></td>
                        <td><?= ($row['quantity']) ?></td>
                        <td><?= ($row['created_at']) ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="action" value="delete_row">
                                <input type="hidden" name="table" value="offers">
                                <input type="hidden" name="id" value="<?= ($row['offer_id']) ?>">
                                <button class="delete" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Transactions</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Market</th>
                    <th>Type</th>
                    <th>Side</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Time</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $row): ?>
                    <tr>
                        <td><?= ($row['transaction_id']) ?></td>
                        <td><?= ($row['username']) ?></td>
                        <td><?= ($row['market_id']) ?></td>
                        <td><?= ($row['contract_type']) ?></td>
                        <td><?= ($row['side']) ?></td>
                        <td>$<?= (number_format((float) $row['price'], 2)) ?></td>
                        <td><?= ($row['quantity']) ?></td>
                        <td><?= ($row['transacted_at']) ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="action" value="delete_row">
                                <input type="hidden" name="table" value="transactions">
                                <input type="hidden" name="id" value="<?= ($row['transaction_id']) ?>">
                                <button class="delete" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
<html>
