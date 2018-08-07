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

        $attributes = [];
        foreach (self::$implementedTraits as $trait) {
            $sync['traits'] = array_merge($sync['traits'], call_user_func('DeviceTrait' . $trait . '::supportedTraits'));
            $attributes = array_merge($attributes, call_user_func('DeviceTrait' . $trait . '::getAttributes'));
        }

        if (count($attributes) > 0) {
            $sync['attributes'] = $attributes;
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

trait HelperDeviceTypeGetVariables
{
    public static function getVariableIDs($configuration)
    {
        $result = [];
        foreach (self::$implementedTraits as $trait) {
            $result = array_unique(array_merge($result, call_user_func('DeviceTrait' . $trait . '::getVariableIDs', $configuration)));
        }

        return $result;
    }
}

trait HelperDeviceType
{
    use HelperDeviceTypeGetVariables;
    use HelperDeviceTypeColumns;
    use HelperDeviceTypeStatus;
    use HelperDeviceTypeSync;
    use HelperDeviceTypeQuery;
    use HelperDeviceTypeExecute;
}
