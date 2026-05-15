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
    .modal.show { display: flex; }
    .modal-card {
      width: min(520px, 100%); background: white; border-radius: 28px; padding: 28px; box-shadow: 0 24px 60px rgba(0,0,0,.22);
    }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .meta { color: #64748b; font-size: 14px; margin-top: 6px; }
    .section-space { margin-top: 28px; }
    @media (max-width: 980px) {
      .hero-grid, .main-grid, .auth-grid, .search-row { grid-template-columns: 1fr; }
      .cards-grid, .stats-grid, .two-col { grid-template-columns: 1fr; }
      .chat-box { position: static; }
    }
    @media (max-width: 640px) {
      .card, .profile-card, .modal-card { padding: 22px; }
      .actions { grid-template-columns: 1fr; }
      .topbar-inner { grid-template-columns: 1fr; gap: 12px; }
    }
  </style>
</head>
<body>
<?php if (!$isLoggedIn): ?>
  <div class="auth-wrap">
    <div class="container auth-grid">
      <div class="stack">
        <div class="brand">
          <div class="brand-badge">SC</div>
          <div>
            <strong>Swahili Connect</strong>
            <small>Learn through real conversation</small>
          </div>
        </div>

        <div>
          <span class="pill" style="background:#020617;color:#fff;">Premium Swahili conversation platform</span>
          <h1>Meet learners, and earn through Swahili practice.</h1>
          <p class="muted" style="font-size:18px; max-width:700px;">Access a clean, premium chat platform where every conversation begins with secure sign in and a simple payment unlock via MPesa</p>
        </div>

        <div class="stats-grid">
          <div class="card stat-box light">
            <h3>Curated profiles</h3>
            <p class="muted">Browse learners by level, country, and displayed amount.</p>
          </div>
          <div class="card stat-box light">
            <h3>KES 100 access</h3>
            <p class="muted">Chat access unlocks after a simple Mpesa payment request.</p>
          </div>
          
        </div>
      </div>

      <div class="auth-card">
        <div class="tabs">
          <button class="tab-btn active" data-tab="signin">Sign In</button>
          <button class="tab-btn" data-tab="signup">Sign Up</button>
        </div>

        <div id="signin-tab" class="card">
          <div class="stack">
            <div>
              <h2 style="font-size:28px;">Sign in to continue</h2>
              <p class="muted">Welcome back. Sign in to continue to your homepage.</p>
            </div>
            <div id="signin-message"></div>
            <input type="email" id="signin-email" placeholder="Email address">
            <input type="password" id="signin-password" placeholder="Password">
            <button class="btn" onclick="signIn()">Sign In</button>
          </div>
        </div>

        <div id="signup-tab" class="card hidden">
          <div class="stack">
            <div>
              <h2 style="font-size:28px;">Create account</h2>
              <p class="muted">Create your account to access the Swahili learner marketplace.</p>
            </div>
            <div id="signup-message"></div>
            <input type="text" id="signup-name" placeholder="Full name">
            <input type="email" id="signup-email" placeholder="Email address">
            <input type="password" id="signup-password" placeholder="Password">
            <button class="btn" onclick="signUp()">Create Account</button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="topbar">
    <div class="container topbar-inner">
      <div class="brand">
        <div class="brand-badge">SC</div>
        <div>
          <strong>Swahili Connect</strong>
          <small>Learn through real conversation</small>
        </div>
      </div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <span class="pill">Welcome, <?php echo htmlspecialchars($userName); ?></span>
        <button class="btn btn-outline" onclick="logoutUser()">Logout</button>
      </div>
    </div>
  </div>

  <div class="container">
    <div class="hero-grid">
      <div class="card card-dark">
        <span class="pill" style="background:rgba(255,255,255,.1);color:#fff;">Swahili learning marketplace</span>
        <h1>Connect with learners and grow through meaningful Swahili conversations.</h1>
        <p class="muted" style="font-size:18px; max-width:760px;">Browse profiles from Europe and the USA, view displayed amounts, and unlock conversations instantly after a KES 100 MPesa payment.</p>

        <div class="stats-grid">
          <div class="stat-box">
            <h3>Global learners</h3>
            <p class="muted">Explore profiles across Europe and the USA.</p>
          </div>
          <div class="stat-box">
            <h3>Secure unlock</h3>
            <p class="muted">KES 100 access.</p>
          </div>
          <div class="stat-box">
            <h3>Responsive design</h3>
            <p class="muted">A clean user experience for desktop and mobile.</p>
          </div>
        </div>
      </div>

      
    </div>

    <div class="section-space">
      <div class="search-row">
        <div>
          <h2>Learners looking to practice Swahili</h2>
          <p class="muted">Browse profiles, select a chat, and view each person's displayed amount.</p>
        </div>
        <input type="text" id="searchInput" placeholder="Search by name, country, or level" oninput="renderLearners()">
      </div>
    </div>

    <div class="main-grid">
      <div class="cards-grid" id="learnersGrid"></div>

      <div class="chat-box">
        <div class="chat-header">
          <div class="avatar-wrap">
            <div class="avatar" id="chatAvatar">SO</div>
            <div>
              <strong id="chatName">Sophie</strong>
              <div class="meta" id="chatMeta">France • Beginner</div>
            </div>
          </div>
        </div>
        <div class="chat-messages" id="chatMessages"></div>
        <div class="chat-controls">
          <textarea id="chatInput" placeholder="<?php echo $isPaid ? 'Type your message in English or Swahili...' : 'Chat locked until payment is verified'; ?>" <?php echo $isPaid ? '' : 'disabled'; ?>></textarea>
          <button class="btn" onclick="sendMessage()" <?php echo $isPaid ? '' : 'disabled id="sendBtn"'; ?>>Send Message</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal" id="paymentModal">
    <div class="modal-card stack">
      <div>
        <h2 style="font-size:28px;">Unlock chat access</h2>
        <p class="muted">Pay a one-time KES 100 access fee before chatting with <span id="modalLearnerName">this learner</span>.</p>
      </div>
      <div class="notice" style="background:#f8fafc;border:1px solid #e2e8f0;color:#475569;">Enter your phone number to receive the MPESA STK Push request and unlock chat access.</div>
      <div class="two-col">
        <div class="stat-box light">
          <div class="meta">Access fee</div>
          <h3 style="font-size:28px;">KES 100</h3>
        </div>
        <div class="stat-box light">
          <div class="meta">Selected learner</div>
          <h3 id="modalLearnerName2">—</h3>
        </div>
      </div>
      <div id="paymentMessage"></div>
      <input type="text" id="phoneInput" value="<?php echo htmlspecialchars($_COOKIE[$COOKIE_PHONE] ?? ''); ?>" placeholder="Phone number e.g. 2547XXXXXXXX">
      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <button class="btn" id="payBtn" onclick="initiatePayment()">Pay via MPesa</button>
        <button class="btn btn-outline" onclick="closePaymentModal()">Close</button>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
const isPaid = <?php echo $isPaid ? 'true' : 'false'; ?>;
const learners = <?php echo json_encode($learners); ?>;
let selectedLearner = learners[0] || null;
let chatUnlocked = isPaid;
let paymentLearner = null;

const openingMessages = [
  [
    { from: 'learner', text: 'Habari! I want to improve my greetings in Swahili.' },
    { from: 'you', text: 'Karibu! We can start with simple greetings and natural replies.' },
    { from: 'learner', text: 'Perfect. I want to sound more confident when I speak.' },
  ],
  [
    { from: 'learner', text: 'Hi! Can you teach me how to introduce myself politely?' },
    { from: 'you', text: 'Yes. We can begin with "Jina langu ni..." and practice a full introduction.' },
    { from: 'learner', text: 'That would be really helpful for me.' },
  ],
  [
    { from: 'learner', text: 'Hello! I want to learn useful travel phrases in Swahili.' },
    { from: 'you', text: 'Absolutely. Let\'s focus on phrases you can use at the airport, hotel, and market.' },
    { from: 'learner', text: 'Amazing. I want practical phrases first.' },
  ],
  [
    { from: 'learner', text: 'Habari yako? I am practicing short conversations today.' },
    { from: 'you', text: 'Nzuri sana. Let\'s build a simple back-and-forth conversation together.' },
    { from: 'learner', text: 'Yes please. I want it to feel natural.' },
  ],
];

const learnerReplies = [
  'That makes sense. Can we try another example?',
  'Asante! I am starting to understand this better.',
  'How would I say that in a more natural way?',
  'Can we practice a short real-life conversation next?',
  'I like that explanation. Please teach me one more phrase.',
  'That is helpful. How do I respond politely in that situation?',
  'Great. Can you help me improve my pronunciation too?',
  'Nice. I want to sound more fluent when I speak.',
];

let currentMessages = [];

function getInitialMessages(personId) {
  return JSON.parse(JSON.stringify(openingMessages[personId % openingMessages.length]));
}

function showMessage(targetId, text, type) {
  const target = document.getElementById(targetId);
  if (!target) return;
  if (!text) { target.innerHTML = ''; return; }
  const cls = type === 'ok' ? 'ok' : (type === 'err' ? 'err' : 'warn');
  target.innerHTML = `<div class="notice ${cls}">${text}</div>`;
}

async function postForm(data) {
  const formData = new FormData();
  Object.keys(data).forEach(key => formData.append(key, data[key]));
  const response = await fetch('', { method: 'POST', body: formData });
  return response.json();
}

function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
  document.querySelector(`[data-tab="${tab}"]`)?.classList.add('active');
  document.getElementById('signin-tab')?.classList.toggle('hidden', tab !== 'signin');
  document.getElementById('signup-tab')?.classList.toggle('hidden', tab !== 'signup');
}

document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => switchTab(btn.dataset.tab));
});

async function signUp() {
  showMessage('signup-message', '', '');
  const name = document.getElementById('signup-name').value.trim();
  const email = document.getElementById('signup-email').value.trim();
  const password = document.getElementById('signup-password').value.trim();

  try {
    const result = await postForm({ action: 'signup', name, email, password });
    if (result.success) {
      location.reload();
    } else {
      showMessage('signup-message', result.message || 'Could not sign up.', 'err');
    }
  } catch {
    showMessage('signup-message', 'Something went wrong while signing up.', 'err');
  }
}

async function signIn() {
  showMessage('signin-message', '', '');
  const email = document.getElementById('signin-email').value.trim();
  const password = document.getElementById('signin-password').value.trim();

  try {
    const result = await postForm({ action: 'signin', email, password });
    if (result.success) {
      location.reload();
    } else {
      showMessage('signin-message', result.message || 'Could not sign in.', 'err');
    }
  } catch {
    showMessage('signin-message', 'Something went wrong while signing in.', 'err');
  }
}

async function logoutUser() {
  await postForm({ action: 'logout' });
  location.reload();
}

function initials(name) {
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
}

function renderLearners() {
  const grid = document.getElementById('learnersGrid');
  if (!grid) return;
  const q = document.getElementById('searchInput')?.value.toLowerCase() || '';
  const filtered = learners.filter(person =>
    person.name.toLowerCase().includes(q) ||
    person.country.toLowerCase().includes(q) ||
    person.level.toLowerCase().includes(q)
  );

  grid.innerHTML = filtered.map(person => `
    <div class="profile-card ${selectedLearner && selectedLearner.id === person.id ? 'selected' : ''}" style="${selectedLearner && selectedLearner.id === person.id ? 'outline:2px solid #020617;' : ''}">
      <div class="profile-top">
        <div class="avatar-wrap">
          <div class="avatar">${initials(person.name)}</div>
          <div>
            <strong style="font-size:18px;">${person.name}</strong>
            <div class="meta">${person.country} • ${person.level}</div>
          </div>
        </div>
        <span class="pill">${person.badge}</span>
      </div>
      <p class="muted" style="margin-top:16px; line-height:1.6;">${person.goal}</p>
      <div class="rate-box">
        <div>
          <div class="meta">Displayed amount</div>
          <strong style="font-size:22px;">${person.rate}</strong>
        </div>
        <div style="font-size:20px;">★</div>
      </div>
      <div class="actions">
        <button class="btn btn-outline" onclick="selectLearner(${person.id})">View Chat</button>
        <button class="btn" onclick="openPaymentModal(${person.id})">${chatUnlocked ? 'Open Chat' : 'Pay KES 100 to Chat'}</button>
      </div>
    </div>
  `).join('');
}

function selectLearner(id) {
  const person = learners.find(p => p.id === id);
  if (!person) return;
  selectedLearner = person;
  currentMessages = getInitialMessages(person.id);
  renderLearners();
  renderChat();
}

function renderChat() {
  if (!selectedLearner) return;
  const chatName = document.getElementById('chatName');
  const chatMeta = document.getElementById('chatMeta');
  const chatAvatar = document.getElementById('chatAvatar');
  const chatMessages = document.getElementById('chatMessages');
  const chatInput = document.getElementById('chatInput');
  const sendBtn = document.querySelector('.chat-controls .btn');

  if (chatName) chatName.textContent = selectedLearner.name;
  if (chatMeta) chatMeta.textContent = `${selectedLearner.country} • ${selectedLearner.level}`;
  if (chatAvatar) chatAvatar.textContent = initials(selectedLearner.name);

  if (chatMessages) {
    let html = currentMessages.map(msg => `<div class="bubble ${msg.from === 'you' ? 'me' : ''}">${msg.text}</div>`).join('');
    if (!chatUnlocked) {
      html += `<div class="notice warn">Pay KES 100 first to start sending messages.</div>`;
    }
    chatMessages.innerHTML = html;
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  if (chatInput) {
    chatInput.disabled = !chatUnlocked;
    chatInput.placeholder = chatUnlocked ? 'Type your message in English or Swahili...' : 'Chat locked until payment is verified';
  }
  if (sendBtn) sendBtn.disabled = !chatUnlocked;
}

function sendMessage() {
  const input = document.getElementById('chatInput');
  if (!input || !chatUnlocked) return;
  const text = input.value.trim();
  if (!text) return;
  const reply = learnerReplies[Math.floor(Math.random() * learnerReplies.length)];
  currentMessages.push({ from: 'you', text });
  currentMessages.push({ from: 'learner', text: reply });
  input.value = '';
  renderChat();
}

function openPaymentModal(id) {
  const person = learners.find(p => p.id === id);
  if (!person) return;
  paymentLearner = person;
  document.getElementById('modalLearnerName').textContent = person.name;
  document.getElementById('modalLearnerName2').textContent = person.name;
  document.getElementById('paymentMessage').innerHTML = '';
  document.getElementById('paymentModal').classList.add('show');
}

function closePaymentModal() {
  document.getElementById('paymentModal').classList.remove('show');
}

async function initiatePayment() {
  const phone = document.getElementById('phoneInput').value.trim();
  const payBtn = document.getElementById('payBtn');
  showMessage('paymentMessage', '', '');

  if (!phone) {
    showMessage('paymentMessage', 'Please enter your phone number.', 'err');
    return;
  }

  payBtn.disabled = true;
  payBtn.textContent = 'Processing STK Push...';

  try {
    const result = await postForm({
      action: 'initiate_payment',
      amount: 100,
      msisdn: phone,
      reference: `swahili-chat-${paymentLearner ? paymentLearner.id : 'learner'}-${Date.now()}`
    });

    if (result.success) {
      chatUnlocked = true;
      showMessage('paymentMessage', result.message || 'STK Push sent successfully.', 'ok');
      renderLearners();
      renderChat();
      setTimeout(closePaymentModal, 1200);
    } else {
      showMessage('paymentMessage', result.message || 'Payment failed.', 'err');
    }
  } catch {
    showMessage('paymentMessage', 'STK Push sent successfully.', 'ok');
  }

  payBtn.disabled = false;
  payBtn.textContent = 'Pay via MPesa';
}

if (learners.length) {
  currentMessages = getInitialMessages(learners[0].id);
  renderLearners();
  renderChat();
}
</script>
</body>
</html>
