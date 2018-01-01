<?php

declare(strict_types=1);

trait HelperDeviceTypeColumns
{
    public static function getColumns()
    {
        $columns = [];
        foreach (self::$implementedTraits as $trait) {
            $columns = array_merge($columns, call_user_func('DeviceTrait' . $trait . '::getColumns'));
        }
        return $columns;
    }
}

trait HelperDeviceTypeStatus
{
    public static function getStatus($configuration)
    {
        foreach (self::$implementedTraits as $trait) {
            $status = call_user_func('DeviceTrait' . $trait . '::getStatus', $configuration);
            if ($status != 'OK') {
                return $status;
            }
        }
        return 'OK';
    }
}

trait HelperDeviceTypeSync
{
    public static function doSync($configuration)
    {
        $sync = [
            'id'     => strval($configuration['ID']),
            'type'   => 'action.devices.types.' . self::$implementedType,
            'traits' => [
            ],
            'name' => [
                'name' => $configuration['Name']
            ],
            'willReportState' => false
        ];

        foreach (self::$implementedTraits as $trait) {
            $sync['traits'][] = 'action.devices.traits.' . $trait;
        }

        return $sync;
    }
}

trait HelperDeviceTypeQuery
{
    public static function doQuery($configuration)
    {
        $query = [];

        foreach (self::$implementedTraits as $trait) {
            $query = array_merge($query, call_user_func('DeviceTrait' . $trait . '::doQuery', $configuration));
        }

        $query['online'] = count($query) > 0;

        return $query;
    }
}

trait HelperDeviceTypeExecute
{
    public static function doExecute($configuration, $command, $data)
    {
        foreach (self::$implementedTraits as $trait) {
            if (in_array($command, call_user_func('DeviceTrait' . $trait . '::supportedCommands'))) {
                return call_user_func('DeviceTrait' . $trait . '::doExecute', $configuration, $command, $data);
            }
        }

        return [
            'id'        => $configuration['ID'],
            'status'    => 'ERROR',
            'errorCode' => 'notSupported'
        ];
    }
}
