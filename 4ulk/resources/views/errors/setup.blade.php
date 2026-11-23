<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>4U.lk is being set up</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;
               font: 16px/1.6 system-ui, -apple-system, "Segoe UI", sans-serif; color: #1E1B4B;
               background: linear-gradient(160deg, #1E0F45 0%, #2E1065 60%, #3B0F7A 100%); }
        .card { background: #fff; border-radius: 20px; padding: 28px; max-width: 520px; box-shadow: 0 30px 60px -30px rgba(0,0,0,.6); }
        h1 { margin: 0 0 8px; font-size: 22px; }
        p { margin: 0 0 12px; color: #475569; }
        ol { margin: 0; padding-left: 20px; color: #475569; }
        code { background: #F5F3FF; padding: 2px 6px; border-radius: 6px; font-size: 14px; color: #6D28D9; }
        .tag { display: inline-block; background: #FCD34D; color: #1E1B4B; font-weight: 800; font-size: 12px; padding: 3px 10px; border-radius: 99px; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="card">
    <span class="tag">4U.lk</span>
    @if ($problem === 'tables')
        <h1>Almost there: the database is empty</h1>
        <p>The site is uploaded and connected to the database, but the tables have not been created yet.</p>
        <ol>
            <li>Open <code>/setup-check.php?key=YOUR_INSTALL_KEY</code></li>
            <li>Click <b>Run installer</b> (or open <code>/install/YOUR_INSTALL_KEY</code>)</li>
        </ol>
        <p style="margin-top:12px">Your <b>INSTALL_KEY</b> is in the file <code>4ulk/.env</code>.</p>
    @else
        <h1>Cannot connect to the database</h1>
        <p>Check these settings in <code>4ulk/.env</code>:</p>
        <ol>
            <li><code>DB_DATABASE</code>, <code>DB_USERNAME</code> and <code>DB_PASSWORD</code> match cPanel → MySQL Databases (including the <code>cpaneluser_</code> prefix)</li>
            <li>The user is added to the database with <b>ALL PRIVILEGES</b></li>
            <li><code>DB_HOST=localhost</code></li>
        </ol>
        <p style="margin-top:12px">Then open <code>/setup-check.php?key=YOUR_INSTALL_KEY</code> to test the connection.</p>
    @endif
</div>
</body>
</html>
