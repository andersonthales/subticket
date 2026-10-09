<?php

use Glpi\Plugin\Hooks;

define('PLUGIN_SUBTICKET_VERSION', '0.5.0');
define('PLUGIN_SUBTICKET_MIN_GLPI', '10.0.0');
define('PLUGIN_SUBTICKET_MAX_GLPI', '10.1.0');

function plugin_init_subticket(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['subticket'] = true;

    if (Session::getLoginUserID()) {
        Plugin::registerClass('PluginSubticketCloner', [
            'addtabon' => 'Ticket',
        ]);
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['subticket'] = ['js/subticket.js'];
        $PLUGIN_HOOKS[Hooks::ADD_CSS]['subticket']        = ['css/subticket.css'];
    }
}

function plugin_version_subticket(): array
{
    return [
        'name'         => 'SubChamado',
        'version'      => PLUGIN_SUBTICKET_VERSION,
        'author'       => 'Anderson Thales',
        'license'      => 'GPLv2+',
        'homepage'     => 'https://github.com/andersonthales/subticket',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_SUBTICKET_MIN_GLPI,
                'max' => PLUGIN_SUBTICKET_MAX_GLPI,
            ],
            'php'  => ['min' => '8.1'],
        ],
    ];
}

function plugin_subticket_check_prerequisites(): bool
{
    return true;
}

function plugin_subticket_check_config($verbose = false): bool
{
    return true;
}
