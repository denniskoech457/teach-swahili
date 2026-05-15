<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

/*
=========================================================
SWAHILI CONNECT - FLOWCASH VERSION
=========================================================
*/

/*
=========================================================
FLOWCASH API CREDENTIALS
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
AJAX: INITIATE FLOWCASH PAYMENT
=========================================================
*/
if (isset($_POST['action']) && $_POST['action'] === 'initiate_payment') {
    if (!isset($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'You must sign in first.'], 401);
    }

    $amount = trim($_POST['amount'] ?? '100');
    $msisdn = trim($_POST['msisdn'] ?? '');
    $reference =
