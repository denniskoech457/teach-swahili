<?php
session_start();

/*
=========================================================
SWAHILI CONNECT - SINGLE FILE PHP + HTML + JAVASCRIPT DEMO
=========================================================

WHAT THIS FILE DOES
- Sign up
- Sign in
- Require login before homepage
- Show learners from Europe and USA only
- Require KES 100 MegaPay payment before chat unlock
- Use PHP session for login state
- Use cookies for quick demo user storage
- Use PHP endpoint in this same file for MegaPay STK Push

IMPORTANT
- This is a prototype/starter.
- Storing users in cookies is NOT secure for production.
- For real deployment, move users/payments/messages into MySQL.
- Add real payment verification/callback before permanent unlock.

=========================================================
MEGAPAY API CREDENTIALS - PUT YOUR VALUES HERE
=========================================================
*/
$MEGAPAY_API_URL = 'https://flowcash.co.ke/v1/stkpush';
$MEGAPAY_API_KEY = 'b88a96eb72bd145c8ab02d56b8d08d7cae9c5d1e9451b7ee002797640123af9e';
$MEGAPAY_EMAIL   = 'elishakoskey36@gmail.com';

/*
=========================================================
CONFIG
=========================================================
*/
$COOKIE_USERS = 'swahili_connect_users';
$COOKIE_PAID  = 'swahili_connect_paid';
$COOKIE_PHONE = 'swahili_connect_phone';

$learners = [
  ['id'=>1,'name'=>'Sophie','country'=>'France','level'=>'Beginner','goal'=>'Wants to build confidence in everyday Swahili conversation before visiting East Africa.','rate'=>'KES 650','badge'=>'Popular'],
  ['id'=>2,'name'=>'Luca','country'=>'Italy','level'=>'Intermediate','goal'=>'Needs help with natural speaking and travel expressions for longer stays.','rate'=>'KES 780','badge'=>'Top Rated'],
  ['id'=>3,'name'=>'Emma','country'=>'United Kingdom','level'=>'Beginner','goal'=>'Looking for relaxed practice sessions focused on greetings and daily speech.','rate'=>'KES 700','badge'=>'New'],
  ['id'=>4,'name'=>'Noah','country'=>'Germany','level'=>'Advanced Beginner','goal'=>'Wants more fluency, better pronunciation, and natural phrasing.','rate'=>'KES 820','badge'=>'Fast Learner'],
  ['id'=>5,'name'=>'Mia','country'=>'USA','level'=>'Beginner','goal'=>'Learning Swahili for culture, travel, and meaningful conversation practice.','rate'=>'KES 900','badge'=>'Premium'],
  ['id'=>6,'name'=>'Oliver','country'=>'Netherlands','level'=>'Intermediate','goal'=>'Needs structured chat sessions to improve confidence in real conversation.','rate'=>'KES 760','badge'=>'Verified'],
  ['id'=>7,'name'=>'Isabella','country'=>'Spain','level'=>'Beginner','goal'=>'Wants to master common phrases and sound more natural when speaking.','rate'=>'KES 680','badge'=>'Active'],
  ['id'=>8,'name'=>'Ethan','country'=>'USA','level'=>'Intermediate','goal'=>'Looking for frequent conversation practice with feedback on sentence flow.','rate'=>'KES 840','badge'=>'Serious Learner'],
];

function readUsersFromCookie($cookieName) {
    if (!isset($_COOKIE[$cookieName])) return [];
    $decoded = json_decode($_COOKIE[$cookieName], true);
    return is_array($decoded) ? $decoded : [];
}

function saveUsersToCookie($cookieName, $users) {
    setcookie($cookieName, json_encode($users), time() + (86400 * 30), '/');
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/*
=========================================================
AJAX: SIGN UP
=========================================================
*/
if (isset($_POST['action']) && $_POST['action'] === 'signup') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        jsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
    }

    $users = readUsersFromCookie($COOKIE_USERS);
    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            jsonResponse(['success' => false, 'message' => 'That email is already registered. Please sign in.'], 400);
        }
    }

    $newUser = [
        'id' => time(),
        'name' => $name,
        'email' => $email,
        'password' => $password,
    ];

    $users[] = $newUser;
    saveUsersToCookie($COOKIE_USERS, $users);

    $_SESSION['user'] = [
        'id' => $newUser['id'],
        'name' => $newUser['name'],
        'email' => $newUser['email'],
    ];

    jsonResponse(['success' => true, 'message' => 'Account created successfully.']);
}

/*
=========================================================
AJAX: SIGN IN
=========================================================
*/
if (isset($_POST['action']) && $_POST['action'] === 'signin') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        jsonResponse(['success' => false, 'message' => 'Email and password are required.'], 400);
    }

    $users = readUsersFromCookie($COOKIE_USERS);
    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email) && $user['password'] === $password) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ];
            jsonResponse(['success' => true, 'message' => 'Signed in successfully.']);
        }
    }

    jsonResponse(['success' => false, 'message' => 'Invalid email or password.'], 401);
}

/*
=========================================================
AJAX: LOGOUT
=========================================================
*/
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_destroy();
    setcookie($COOKIE_PAID, '', time() - 3600, '/');
    setcookie($COOKIE_PHONE, '', time() - 3600, '/');
    jsonResponse(['success' => true]);
}

/*
=========================================================
AJAX: INITIATE MEGAPAY PAYMENT
PUT YOUR API KEY + EMAIL ABOVE
=========================================================
*/
if (isset($_POST['action']) && $_POST['action'] === 'initiate_payment') {
    if (!isset($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'You must sign in first.'], 401);
    }

    $amount = trim($_POST['amount'] ?? '100');
    $msisdn = trim($_POST['msisdn'] ?? '');
    $reference = trim($_POST['reference'] ?? ('swahili-chat-' . time()));

    if ($msisdn === '') {
        jsonResponse(['success' => false, 'message' => 'Phone number is required.'], 400);
    }

    if ($MEGAPAY_API_KEY === 'PUT_YOUR_MEGAPAY_API_KEY_HERE' || $MEGAPAY_EMAIL === 'PUT_YOUR_MEGAPAY_LOGIN_EMAIL_HERE') {
        jsonResponse(['success' => false, 'message' => 'Please add your MegaPay API key and MegaPay email in the PHP config section.'], 500);
    }

    $payload = json_encode([
        'api_key' => $MEGAPAY_API_KEY,
        'email' => $MEGAPAY_EMAIL,
        'amount' => $amount,
        'msisdn' => $msisdn,
        'reference' => $reference,
    ]);

    $ch = curl_init($MEGAPAY_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        jsonResponse(['success' => false, 'message' => 'cURL error: ' . $curlError], 500);
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        $decoded = ['raw_response' => $response];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        setcookie($COOKIE_PAID, 'true', time() + (86400 * 7), '/');
        setcookie($COOKIE_PHONE, $msisdn, time() + (86400 * 7), '/');
        jsonResponse([
            'success' => true,
            'message' => 'STK Push sent successfully. Confirm the payment on your phone.',
            'provider_response' => $decoded
        ]);
    }

    jsonResponse([
        'success' => false,
        'message' => $decoded['message'] ?? 'MegaPay request failed.',
        'provider_response' => $decoded
    ], 400);
}

$isLoggedIn = isset($_SESSION['user']);
$userName = $isLoggedIn ? $_SESSION['user']['name'] : '';
$isPaid = isset($_COOKIE[$COOKIE_PAID]) && $_COOKIE[$COOKIE_PAID] === 'true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Swahili Connect</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: Arial, Helvetica, sans-serif;
      background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
      color: #0f172a;
    }
    .hidden { display: none !important; }
    .container { width: min(1200px, calc(100% - 32px)); margin: 0 auto; }
    .topbar {
      position: sticky; top: 0; z-index: 20;
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid #e2e8f0;
    }
    .topbar-inner, .hero-grid, .main-grid, .auth-grid, .cards-grid, .stats-grid {
      display: grid;
      gap: 24px;
    }
    .topbar-inner {
      grid-template-columns: 1fr auto;
      align-items: center;
      padding: 16px 0;
    }
    .brand { display: flex; align-items: center; gap: 12px; }
    .brand-badge {
      width: 44px; height: 44px; border-radius: 16px; background: #020617; color: white;
      display: flex; align-items: center; justify-content: center; font-weight: bold;
    }
    .brand small { color: #64748b; display: block; margin-top: 3px; }
    .btn {
      border: 0; background: #020617; color: white; padding: 12px 16px; border-radius: 16px;
      cursor: pointer; font-weight: 600; transition: .2s ease;
    }
    .btn:hover { opacity: .92; }
    .btn-outline {
      background: white; color: #0f172a; border: 1px solid #cbd5e1;
    }
    .pill {
      display: inline-block; padding: 8px 14px; border-radius: 999px; font-size: 13px; font-weight: 700;
      background: #e2e8f0; color: #0f172a;
    }
    .hero-grid { grid-template-columns: 1.1fr .9fr; margin-top: 28px; }
    .card {
      background: rgba(255,255,255,.92);
      border: 1px solid rgba(255,255,255,.6);
      border-radius: 32px; padding: 32px; box-shadow: 0 10px 30px rgba(15,23,42,.08);
    }
    .card-dark {
      background: #020617; color: white; border-color: #020617;
    }
    .card-dark p.muted, .muted { color: #64748b; }
    .card-dark .muted { color: #cbd5e1; }
    h1 { font-size: clamp(36px, 7vw, 64px); line-height: .98; margin: 16px 0; }
    h2 { font-size: clamp(28px, 4vw, 40px); margin-bottom: 10px; }
    h3 { font-size: 20px; margin-bottom: 8px; }
    .stats-grid { grid-template-columns: repeat(3, 1fr); margin-top: 28px; }
    .stat-box {
      border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.05); border-radius: 24px; padding: 20px;
    }
    .stat-box.light { background: #f8fafc; border: 1px solid #e2e8f0; }
    .main-grid { grid-template-columns: 1.1fr .9fr; margin: 28px 0 40px; align-items: start; }
    .cards-grid { grid-template-columns: repeat(2, 1fr); }
    .profile-card {
      border-radius: 28px; background: rgba(255,255,255,.92); padding: 24px; box-shadow: 0 10px 24px rgba(15,23,42,.07);
      border: 1px solid rgba(255,255,255,.6);
    }
    .profile-top { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; }
    .avatar-wrap { display: flex; gap: 12px; align-items: center; }
    .avatar {
      width: 48px; height: 48px; border-radius: 50%; background: #e2e8f0; color: #0f172a;
      display: flex; align-items: center; justify-content: center; font-weight: 700;
    }
    .rate-box {
      margin-top: 16px; background: #f8fafc; border-radius: 20px; padding: 16px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
    .chat-box {
      border-radius: 28px; background: rgba(255,255,255,.92); box-shadow: 0 10px 24px rgba(15,23,42,.07);
      border: 1px solid rgba(255,255,255,.6); overflow: hidden; position: sticky; top: 92px;
    }
    .chat-header { padding: 20px 24px; border-bottom: 1px solid #e2e8f0; }
    .chat-messages { height: 420px; overflow-y: auto; background: #f8fafc; padding: 18px; display: flex; flex-direction: column; gap: 12px; }
    .bubble {
      max-width: 85%; padding: 14px 16px; border-radius: 18px; font-size: 14px; line-height: 1.5;
      background: white; border: 1px solid #e2e8f0;
    }
    .bubble.me { margin-left: auto; background: #020617; color: white; border-color: #020617; }
    .chat-controls { padding: 20px 24px; display: grid; gap: 12px; }
    textarea, input {
      width: 100%; border: 1px solid #cbd5e1; border-radius: 16px; padding: 14px 16px; font-size: 15px; outline: none;
      background: white;
    }
    .search-row {
      display: grid; grid-template-columns: 1fr 360px; gap: 16px; align-items: end; margin-top: 12px;
    }
    .auth-wrap {
      min-height: 100vh;
      background: radial-gradient(circle at top left, rgba(15,23,42,.08), transparent 30%), linear-gradient(135deg, #f8fafc 0%, #eef2ff 50%, #f8fafc 100%);
    }
    .auth-grid {
      min-height: 100vh; align-items: center; grid-template-columns: 1.05fr .95fr; padding: 32px 0;
    }
    .tabs { display: flex; background: rgba(255,255,255,.85); border-radius: 18px; padding: 4px; gap: 4px; margin-bottom: 16px; }
    .tab-btn {
      flex: 1; padding: 12px; border: 0; background: transparent; border-radius: 14px; cursor: pointer; font-weight: 700;
    }
    .tab-btn.active { background: white; box-shadow: 0 1px 2px rgba(15,23,42,.08); }
    .auth-card { max-width: 520px; }
    .stack { display: grid; gap: 16px; }
    .notice { padding: 14px 16px; border-radius: 16px; font-size: 14px; }
    .notice.warn { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .notice.ok { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .notice.err { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .modal {
      position: fixed; inset: 0; background: rgba(2,6,23,.55); display: none; align-items: center; justify-content: center; padding: 20px; z-index: 100;
    }
    .modal.show
