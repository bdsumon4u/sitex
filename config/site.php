<?php

return [
    'ftp_root' => env('FTP_ROOT', '/'),
    'anonymous_login_secret' => env('ANONYMOUS_LOGIN_SECRET', 'hotash_secret_access'),
    'ssh_key_name' => env('SSH_KEY_NAME', 'HOTASH'),
    'repository_url' => env('SITE_REPOSITORY_URL', env('GIT_REPOSITORY', 'https://github.com/bdsumon4u/HotashKom.git')),
];
