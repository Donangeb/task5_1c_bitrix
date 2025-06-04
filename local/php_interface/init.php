<?php

use Bitrix\Main\EventManager;

use Only\Site\Agents;



defined('B_PROLOG_INCLUDED') || die;

// автозагрузчик классов
require_once $_SERVER['DOCUMENT_ROOT'].'/local/modules/dev.site/include.php';

$eventManager = EventManager::getInstance();

// Обработчики события на логирование добавления инфоблока
$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementAdd',
    ['Only\Site\Handlers\Iblock', 'addLog']
);

// Обработчики события на логирование изменения инфоблока
$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementUpdate',
    ['Only\Site\Handlers\Iblock', 'addLog']
);
