<?php

const DRY_RUN = false;

require __DIR__ . '/include/mailer.php';

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

function is_pr($issue) {
    return strpos($issue->html_url, '/pull/') !== false;
}

function prep_title($issue, $repoName) {
    $issueNumber = $issue->number;
    $title = $issue->title;
    $type = is_pr($issue) ? 'PR' : 'Issue';

    $subject = sprintf('[%s][%s #%s] - %s', $repoName, $type, $issueNumber, $title);

    return $subject;
}

function send_mail($to, $subject, $message) {
    printf("Sending mail...\nTo: %s\nSubject: %s\nMessage:\n%s",
        $to, $subject, $message);

    if (!DRY_RUN) {
        $subject = '=?utf-8?B?'.base64_encode($subject).'?=';
        mailer($to, $subject, $message);
    }
}

function handle_commit($commit) {
    $message = $commit->message;
    $author = $commit->author->username;
    $committer = $commit->committer->username;
    $url = $commit->url;

    if (!preg_match_all('/^Fix(?:ed)? (?:bug )?\#([0-9]+)/m', $message, $matches)) {
        return;
    }
    $bugIds = $matches[1];

    if ($author === $committer) {
        $blame = $author;
    } else {
        $blame = "$author (author) and $committer (committer)";
    }

    $newLinePos = strpos($message, "\n");
    $firstLine = $newLinePos !== false ? substr($message, 0, $newLinePos) : $message;

    $comment = <<<MSG
Automatic comment on behalf of $blame
Revision: $url
Log: $firstLine
MSG;

    foreach ($bugIds as $bugId) {
        $postData = [
            'user' => 'git',
            'id' => (int) $bugId,
            'ncomment' => $comment,
            'status' => 'Closed',
            'MAGIC_COOKIE' => getenv('BUGS_MAGIC_COOKIE'),
        ];
        $postData = http_build_query($postData, '', '&');

        echo "Closing bug #$bugId\n";
        if (!DRY_RUN) {
            $context = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData,
                'timeout' => 5,
            ]]);
            $result = file_get_contents('https://bugs.php.net/rpc.php', false, $context);
            echo "Response: $result\n";
        }
    }
}

$CONFIG = [
    'repos' => [
        'php-langspec' => 'standards@lists.php.net',
        'php-src' => 'git-pulls@lists.php.net',
        'web-' => 'php-webmaster@lists.php.net',
        'pecl-' => 'pecl-dev@lists.php.net',
    ],
];

if (DRY_RUN) {
    $body = file_get_contents("php://stdin");
    $event = $argv[1];
} else {
    $body = file_get_contents("php://input");
    $event = $_SERVER['HTTP_X_GITHUB_EVENT'];

    if (!verify_signature($body)) {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }
}

$payload = json_decode($body);
$repoName = $payload->repository->name;

switch ($event) {
    case 'ping':
        break;
    case 'pull_request':
    case 'issues':
        $action = $payload->action;
        $issue = $event == 'issues' ? $payload->issue : $payload->pull_request;
        $htmlUrl = $issue->html_url;

        $description = $issue->body;
        $username = $issue->user->login;

        $to = get_repo_email($CONFIG["repos"], $repoName);
        $subject = prep_title($issue, $repoName);
        $type = is_pr($issue) ? 'Pull Request' : 'Issue';

        $message = sprintf("You can view the %s on github:\r\n%s", $type, $htmlUrl);
        switch ($action) {
            case 'opened':
                $message .= sprintf(
                    "\r\n\r\nOpened By: %s\r\n%s Description:\r\n%s",
                    $username, $type, $description);
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

        send_mail($to, $subject, $message);
        break;

    case 'pull_request_review_comment':
    case 'issue_comment':
        $action = $payload->action;
        $issue = $event == 'issue_comment' ? $payload->issue : $payload->pull_request;
        $htmlUrl = $issue->html_url;

        $username = $payload->comment->user->login;
        $comment = $payload->comment->body;

        $to = get_repo_email($CONFIG["repos"], $repoName);
        $subject = prep_title($issue, $repoName);
        $type = is_pr($issue) ? 'Pull Request' : 'Issue';

        $message = sprintf("You can view the %s on github:\r\n%s", $type, $htmlUrl);
        switch ($action) {
            case 'created':
                $message .= sprintf("\r\n\r\nComment by %s:\r\n%s", $username, $comment);
                break;
            case 'edited':
            case 'deleted':
                // Ignore these actions
                break 2;
        }

        send_mail($to, $subject, $message);
        break;

    case 'push':
        if ($payload->ref !== 'refs/heads/master') {
            echo "Skipping push to non-master";
            break;
        }
        foreach ($payload->commits as $commit) {
            handle_commit($commit);
        }
        break;

    default:
        header('HTTP/1.1 501 Not Implemented');
}

