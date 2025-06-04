<?php

namespace Only\Site\Agents;


class Iblock
{
    public static function clearOldLogs()
    {
        // Проверяем, что модуль iblock загружен
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
        }

        try {
            // Получаем ID инфоблока
            $iblockId = \Only\Site\Helpers\IBlock::getIblockID('LOG', 'services');
        } catch (\Exception $e) {
            // Логируем ошибку, если инфоблок не найден
            \CEventLog::Add([
                "SEVERITY" => "ERROR",
                "AUDIT_TYPE_ID" => "IBLOCK_ERROR",
                "MODULE_ID" => "iblock",
                "DESCRIPTION" => "Не удалось получить ID инфоблока: " . $e->getMessage(),
            ]);
            return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
        }

        // Получаем общее количество элементов (надежный способ)
        $totalCount = 0;
        $rsElements = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId],
            false,
            false,
            ['ID']
        );

        if (!is_object($rsElements)) {
            \CEventLog::Add([
                "SEVERITY" => "ERROR",
                "AUDIT_TYPE_ID" => "IBLOCK_ERROR",
                "MODULE_ID" => "iblock",
                "DESCRIPTION" => "GetList вернул не объект",
            ]);
            return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
        }

        while ($rsElements->Fetch()) {
            $totalCount++;
        }

        // Если элементов 10 или меньше - ничего не делаем
        if ($totalCount <= 10) {
            return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
        }

        // Получаем ID 10 самых новых элементов
        $rsNewest = \CIBlockElement::GetList(
            ['TIMESTAMP_X' => 'DESC'],
            ['IBLOCK_ID' => $iblockId],
            false,
            ['nTopCount' => 10],
            ['ID']
        );

        if (!is_object($rsNewest)) {
            \CEventLog::Add([
                "SEVERITY" => "ERROR",
                "AUDIT_TYPE_ID" => "IBLOCK_ERROR",
                "MODULE_ID" => "iblock",
                "DESCRIPTION" => "GetList для новых элементов вернул не объект",
            ]);
            return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
        }

        $excludeIds = [];
        while ($arItem = $rsNewest->Fetch()) {
            $excludeIds[] = $arItem['ID'];
        }

        // Удаляем все элементы, кроме 10 самых новых
        $rsToDelete = \CIBlockElement::GetList(
            ['TIMESTAMP_X' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                '!ID' => $excludeIds
            ],
            false,
            false,
            ['ID']
        );

        if (!is_object($rsToDelete)) {
            \CEventLog::Add([
                "SEVERITY" => "ERROR",
                "AUDIT_TYPE_ID" => "IBLOCK_ERROR",
                "MODULE_ID" => "iblock",
                "DESCRIPTION" => "GetList для удаления вернул не объект",
            ]);
            return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
        }

        $deletedCount = 0;
        while ($arItem = $rsToDelete->Fetch()) {
            if (\CIBlockElement::Delete($arItem['ID'])) {
                $deletedCount++;
            }
        }

        // Логируем результат работы
        \CEventLog::Add([
            "SEVERITY" => "INFO",
            "AUDIT_TYPE_ID" => "IBLOCK_CLEANUP",
            "MODULE_ID" => "iblock",
            "DESCRIPTION" => "Удалено старых элементов: $deletedCount",
        ]);

        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }
    public static function example()
    {
        global $DB;
        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            $iblockId = \Only\Site\Helpers\IBlock::getIblockID('QUARRIES_SEARCH', 'SYSTEM');
            $format = $DB->DateFormatToPHP(\CLang::GetDateFormat('SHORT'));
            $rsLogs = \CIBlockElement::GetList(['TIMESTAMP_X' => 'ASC'], [
                'IBLOCK_ID' => $iblockId,
                '<TIMESTAMP_X' => date($format, strtotime('-1 months')),
            ], false, false, ['ID', 'IBLOCK_ID']);
            while ($arLog = $rsLogs->Fetch()) {
                \CIBlockElement::Delete($arLog['ID']);
            }
        }
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }
}
