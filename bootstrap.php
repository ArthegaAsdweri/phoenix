<?php

require_once('vendor/autoload.php');

set_include_path('src:tests');

#\PhoenixPhp\Core\Session::getInstance();

set_error_handler           (['\PhoenixPhp\Core\ErrorHandler', 'handleError']);
register_shutdown_function  (['\PhoenixPhp\Core\ErrorHandler', 'handleShutdown']);
set_exception_handler       (['\PhoenixPhp\Core\ErrorHandler', 'handleException']);