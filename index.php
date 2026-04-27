<?php

$host = '10.1.1.100';
$username = 'bryce';
$password = '1234';
$database = 'final';
$port = 3306;

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

// database connection
$conn = new mysqli($host, $username, $password, $database, $port);
if ($conn ->connect_error)
       die('Could not connect: ' . $conn->connect_error);









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
        .card {
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
    </style>
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
<html>
