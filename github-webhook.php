<?php
function verify_signature($requestBody) {
    if (isset($_SERVER['HTTP_X_HUB_SIGNATURE'])){
        $sig = 'sha1=' . hash_hmac('sha1', $requestBody, getenv('GITHUB_SECRET'));
        return $sig === $_SERVER['HTTP_X_HUB_SIGNATURE'];
    }
    return false;
}

function get_repo_email($repos, $repoName) {
    // if we somehow end up receiving a PR for a repo not matching anything send it to systems so that we can fix it
    $to = 'systems@php.net';
    foreach ($repos as $repoPrefix => $email) {
        if (strpos($repoName, $repoPrefix) === 0) {
            $to = $email;
        }
    }

    return $to;
}

function prep_title($issue, $repoName) {
    $issueNumber = $issue->number;
    $title = $issue->title;
    $type = strpos($issue->html_url, '/pull/') !== false ? 'PR' : 'Issue';

    $subject = sprintf('[%s][%s #%s] - %s', $repoName, $type, $issueNumber, $title);

    return $subject;
}


$CONFIG = array(
    'repos' => array(
        'php-langspec' => 'standards@lists.php.net',
        'php-src' => 'git-pulls@lists.php.net',
        'web-' => 'php-webmaster@lists.php.net',
        'pecl-' => 'pecl-dev@lists.php.net',
    ),
);

$body = file_get_contents("php://input");

if (!verify_signature($body)) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$payload = json_decode($body);
$action = $payload->action;
$repoName = $payload->repository->name;

$event = $_SERVER['HTTP_X_GITHUB_EVENT'];
switch ($event) {
    case 'ping':
        break;
    case 'pull_request':
    case 'issues':
        $issue = $event == 'issues' ? $payload->issue : $payload->pull_request;
        $htmlUrl = $issue->html_url;

        $description = $issue->body;
        $username = $issue->user->login;

        $to = get_repo_email($CONFIG["repos"], $repoName);
        $subject = prep_title($issue, $repoName);

        $message = sprintf("You can view the Pull Request on github:\r\n%s", $htmlUrl);
        switch ($action) {
            case 'opened':
                $message .= sprintf(
                    "\r\n\r\nOpened By: %s\r\nPull Request Description:\r\n%s",
                    $username, $description);
                break;
            case 'closed':
                $message .= "\r\n\r\nClosed.";
                break;
            case 'reopened':
                $message .= "\r\n\r\nReopened.";
                break;
            case 'assigned':
            case 'unassigned':
            case 'labeled':
            case 'unlabeled':
            case 'edited':
            case 'synchronize':
            case 'milestoned':
            case 'demilestoned':
                // Ignore these actions
                break 2;
        }

        $headers = "From: noreply@php.net\r\nContent-Type: text/plain; charset=utf-8\r\n";
        mail($to, '=?utf-8?B?'.base64_encode($subject).'?=', $message, $headers, "-fnoreply@php.net");
        break;

    case 'pull_request_review_comment':
    case 'issue_comment':
        $issue = $event == 'issue_comment' ? $payload->issue : $payload->pull_request;
        $htmlUrl = $issue->html_url;

        $username = $payload->comment->user->login;
        $comment = $payload->comment->body;

        $to = get_repo_email($CONFIG["repos"], $repoName);
        $subject = prep_title($issue, $repoName);
        $message = sprintf("You can view the Pull Request on github:\r\n%s", $htmlUrl);
        switch ($action) {
            case 'created':
                $message .= sprintf("\r\n\r\nComment by %s:\r\n%s", $username, $comment);
                break;
            case 'edited':
            case 'deleted':
                // Ignore these actions
                break 2;
        }

        $headers = "From: noreply@php.net\r\nContent-Type: text/plain; charset=utf-8\r\n";
        mail($to, '=?utf-8?B?'.base64_encode($subject).'?=', $message, $headers, "-fnoreply@php.net");
        break;

    default:
        header('HTTP/1.1 501 Not Implemented');
}

