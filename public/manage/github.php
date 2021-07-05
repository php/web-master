<?php

use App\GitHub\Client;
use App\GitHub\OAuthClient;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../include/login.inc';
require __DIR__ . '/../../github-config.php';

if (!defined('GITHUB_CLIENT_ID') || !defined('GITHUB_CLIENT_SECRET')) {
    head("github administration");
    warn('GITHUB_CLIENT_ID or GITHUB_CLIENT_SECRET not defined. Please verify ./github-config.php');
    foot();
    exit;
}

$oauth = new OAuthClient(GITHUB_CLIENT_ID, GITHUB_CLIENT_SECRET);
if (!isset($_GET['code'])) {
    header('Location: ' . $oauth->getRequestCodeUrl());
    exit;
}

head("github administration");

try {
    if (isset($_GET['code'])) {
        $response = $oauth->requestAccessToken($_GET['code']);
        if (!isset($response['access_token'])) {
            throw new RuntimeException('Can not receive the access token');
        }

        $client = new Client($response['access_token']);
        $user = $client->me();

        if (!isset($user['login'])) {
            throw new RuntimeException('Can not get the user GitHub login');
        }

        $username = $_SESSION['credentials'][0];

        $db = \App\DB::connect();
        $query = $db->prepare('SELECT userid FROM users WHERE username = ?');
        $query->execute([$username]);
        if (!$query->rowCount()) {
            throw new RuntimeException('was not able to find user matching ' . $username);
        }

        $account = $query->fetch();
        $query = $db->prepare('SELECT userid FROM users WHERE github = ? AND userid != ?');
        $query->execute([$user['login'], $account['userid']]);
        if ($query->rowCount() > 0) {
            throw new RuntimeException('GitHub account ' . $user['login'] . ' is already linked');
        }

        $query = $db->prepare('UPDATE users SET github = ? WHERE userid = ?');
        $query->execute([$user['login'], $account['userid']]);

        echo '<h1>We linked your GitHub account with your profile.</h1>' .
            '<p><a href="/manage/users.php?username=' . $username . '">Back to profile</a></p>';
    }
} catch (\Exception $e) {
    warn($e->getMessage());
}

foot();
