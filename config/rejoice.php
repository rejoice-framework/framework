<?php

use Rejoice\Foundation\Path;

$frameworkRoot = Path::toFramework();

return [
    'paths' => [
        'project_root' => $projectRoot,
        'default_env_file' => $projectRoot.'.env',
        'app_root_dir' => $projectRoot.'app/',
        'app_menu_class_dir' => $projectRoot.'app/Menus/',
        'app_model_dir' => $projectRoot.'app/Models/',
        'app_config_dir' => $projectRoot.'config/',
        'app_config_file' => $projectRoot.'config/app.php',
        'app_database_config_file' => $projectRoot.'config/database.php',
        'app_session_config_file' => $projectRoot.'config/session.php',
        'public_root_dir' => $projectRoot.'public/',
        'resource_root_dir' => $projectRoot.'resources/',
        'menu_resource_dir' => $projectRoot.'resources/menus/',
        'storage_root_dir' => $projectRoot.'storage/',
        'cache_root_dir' => $projectRoot.'storage/cache/',
        'app_default_cache_file' => $projectRoot.'storage/cache/rejoice.cache',
        'app_default_log_count_file' => $projectRoot.'storage/cache/.log-count.cache',
        'log_root_dir' => $projectRoot.'storage/logs/',
        'app_default_log_file' => $projectRoot.'storage/logs/rejoice.log',
        'session_root_dir' => $projectRoot.'storage/sessions/',
        'test_root_dir' => $projectRoot.'tests/',
        'app_command_dir' => $projectRoot.'app/Console/Commands/',
        'app_command_file' => $projectRoot.'app/Console/commands.php',

        'framework_root' => $frameworkRoot,
        'framework_command_dir' => $frameworkRoot.'src/Console/Commands/',
        'framework_command_file' => $frameworkRoot.'src/Console/commands.php',
        'framework_stub_dir' => $frameworkRoot.'src/Stubs/',
    ],
];
