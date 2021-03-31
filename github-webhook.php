<?php

const DRY_RUN = false;

if (!DRY_RUN) {
    require __DIR__ . '/include/mailer.php';
}

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

function send_mail($to, $subject, $message, $from = "noreply@php.net", $from_name = "") {
    printf("Sending mail...\nTo: %s\nFrom: %s <%s>\nSubject: %s\nMessage:\n%s",
        $to, $from_name, $from, $subject, $message);

    if (!DRY_RUN) {
        $subject = '=?utf-8?B?'.base64_encode($subject).'?=';
        mailer($to, $subject, $message, $from, $from_name);
    }
}

function handle_bug_close($commit) {
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

function get_commit_mailing_list($repoName) {
    if ($repoName === 'playground') {
        return 'nikic@php.net';
    }
    // Only enable playground for now.
    return null;

    if ($repoName === 'php-src' || $repoName === 'karma') {
        return 'php-cvs@lists.php.net';
    } else if ($repoName === 'php-langspec') {
        return 'standards-vcs@lists.php.net';
    } else if ($repoName === 'phpruntests' || $repoName === 'pftt2' || $repoName === 'web-qa') {
        return 'php-qa@lists.php.net';
    } else if ($repoName === 'systems') {
        return 'systems@php.net';
    } else if ($repoName === 'php-gtk-src') {
        return 'php-gtk-cvs@lists.php.net';
    } else if ($repoName === 'presentations' || $repoName === 'web-pres2') {
        return 'pres@lists.php.net';
    } else if ($repoName === 'doc-base' || $repoName === 'doc-en'
        || $repoName === 'phd' || $repoName === 'web-doc-editor') {
        return 'doc-cvs@lists.php.net';
    } else if ($repoName === 'web-doc') {
        return 'doc-web@lists.php.net';
    } else if ($repoName === 'web-pecl') {
        return 'pecl-cvs@lists.php.net';
    } else if (strpos($repoName, 'web-') === 0) {
        return 'php-webmaster@lists.php.net';
    } else if (strpos($repoName, 'pecl-') === 0) {
        return 'pecl-cvs@lists.php.net';
    } else if (strpos($repoName, 'doc-') === 0 && $repoName !== 'doc-gtk') {
        return str_replace('-', '_', $repoName) . '@lists.php.net';
    } else {
        return null;
    }
}

function handle_ref_change_mail($mailingList, $payload) {
    $repoName = $payload->repository->name;
    $ref = $payload->ref;
    $before = $payload->before;
    $after = $payload->after;
    $compare = $payload->compare;
    $pusherName = $payload->pusher->name;

    if (!preg_match('/^refs\/(.+)\/(.+)$/', $ref, $matches)) {
        echo "Unexpected ref format: $ref";
        return;
    }

    $refKind = $matches[1];
    $refName = $matches[2];

    if ($refKind === 'heads') {
        $what = "branch $refName";
    } else if ($refKind === 'tags') {
        $what = "tag $refName";
    } else {
        $what = "unknown ref $ref";
    }

    if ($payload->created) {
        $action = "created";
    } else if ($payload->deleted) {
        $action = "deleted";
    } else if ($payload->forced) {
        $action = "force pushed";
    } else {
        $action = "performed unknown action on";
    }

    $subject = "[$repoName] $action $what";

    $message = ucfirst($action) . " $what in repository $repoName.\n\n";
    $message .= "Pusher: $pusherName\n";
    if ($action !== 'created') {
        $message .= "Before: https://github.com/php/$repoName/commit/$before\n";
    }
    if ($action !== 'deleted') {
        $message .= "After: https://github.com/php/$repoName/commit/$after\n";
    }
    $message .= "Compare: $compare\n";
    $message .= "Tree: https://github.com/php/$repoName/tree/$refName\n";
    if ($refKind === 'tags') {
        $message .= "Tag: https://github.com/php/$repoName/releases/tag/$refName\n";
    }

    send_mail($mailingList, $subject, $message, 'noreply@php.net', $pusherName);
}

function handle_push_mail($payload) {
    $repoName = $payload->repository->name;
    $ref = $payload->ref;
    $mailingList = get_commit_mailing_list($repoName);
    if ($mailingList === null) {
        echo "Not sending mail for $repoName (no mailing list)";
        return;
    }

    if ($payload->created || $payload->deleted || $payload->forced) {
        handle_ref_change_mail($mailingList, $payload);
    }

    // Ignore commits to PHP-x.y branches for now, to avoid duplicate commits on upwards merges.
    // TODO: Find a better solution to this problem...
    if ($repoName === 'php-src' && preg_match('/^refs/heads/PHP-\d\.\d$/', $ref)) {
        echo "Skipping commit mails for push to $payload->ref of $repoName";
        return;
    }

    /*foreach ($payload->commits as $commit) {

    }*/
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
        if ($payload->ref === 'refs/heads/master') {
            // Only close bugs for pushes to master.
            foreach ($payload->commits as $commit) {
                handle_bug_close($commit);
            }
        }

        handle_push_mail($payload);
        break;

    default:
        header('HTTP/1.1 501 Not Implemented');
}

