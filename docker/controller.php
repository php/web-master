<?php

if (\str_starts_with($_SERVER['REQUEST_URI'], '/shared/')) {
    header('Location: http://localhost:8002' . \substr($_SERVER['REQUEST_URI'], \strlen('/shared')));
    exit;
}

return false;