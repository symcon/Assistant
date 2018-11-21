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
        if ($configuration['Name'] == '') {
            return 'No name';
        }
        foreach (self::$implementedTraits as $trait) {
            $status = call_user_func('DeviceTrait' . $trait . '::getStatus', $configuration);
            if ($status != 'OK') {
                if (self::$displayStatusPrefix) {
                    return call_user_func('DeviceTrait' . $trait . '::getStatusPrefix') . $status;
                } else {
                    return $status;
                }
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
            $traits = call_user_func('DeviceTrait' . $trait . '::supportedTraits', $configuration);
            if (count($traits) > 0) {
                $sync['traits'] = array_merge($sync['traits'], call_user_func('DeviceTrait' . $trait . '::supportedTraits', $configuration));
                $attributes = array_merge($attributes, call_user_func('DeviceTrait' . $trait . '::getAttributes'));
            }
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
    public static function doExecute($configuration, $command, $data, $emulateStatus)
    {
        foreach (self::$implementedTraits as $trait) {
            if (in_array($command, call_user_func('DeviceTrait' . $trait . '::supportedCommands'))) {
                return call_user_func('DeviceTrait' . $trait . '::doExecute', $configuration, $command, $data, $emulateStatus);
            }
        }

        return [
            'ids'       => [$configuration['ID']],
            'status'    => 'ERROR',
            'errorCode' => 'notSupported'
        ];
    }
}

trait HelperDeviceTypeGetVariables
{
    public static function getObjectIDs($configuration)
    {
        $result = [];
        foreach (self::$implementedTraits as $trait) {
            $result = array_unique(array_merge($result, call_user_func('DeviceTrait' . $trait . '::getObjectIDs', $configuration)));
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
