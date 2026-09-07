<?php

return [
    'ftp_root' => env('FTP_ROOT', '/'),
    'global_admin_email' => env('GLOBAL_ADMIN_EMAIL', 'admin@master.com'),
    'global_admin_password' => env('GLOBAL_ADMIN_PASSWORD', 'admin123'),
    'anonymous_login_secret' => env('ANONYMOUS_LOGIN_SECRET', 'master_auto_login_secret'),
    'ssh_key_name' => env('SSH_KEY_NAME', 'HOTASH'),
];
