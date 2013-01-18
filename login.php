<?php
// $Id$

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
        session_start();
        session_destroy();
        echo 'You have successfully logged out.  Redirecting....'.PHP_EOL;
        echo '<meta http-equiv="refresh" content="5;http://php.net/"/>'.PHP_EOL;
        exit(0);
}

require 'login.inc';

head();
?>
<!-- login.inc does exit() if the user isn't logged in -->
<p>You are already logged in</p>
<?php
foot();
?>
