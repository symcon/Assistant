<?php

declare(strict_types=1);

class DeviceTraitOpenCloseShutter extends DeviceTrait
{
    use HelperDimDevice;
    use HelperShutterDevice;
    public const propertyPrefix = 'OpenCloseShutter';

    public function getColumns()
    {
        return [
            [
                'caption' => 'Shutter Variable',
                'name'    => self::propertyPrefix . 'ID',
                'width'   => '200px',
                'add'     => 0,
                'edit'    => [
                    'type' => 'SelectVariable'
                ]
            ]
        ];
    }

    public function getStatus($configuration)
    {
        $dimCompatibility = self::getDimCompatibility($configuration[self::propertyPrefix . 'ID']);
        $shutterCompatibility = self::getShutterCompatibility($configuration[self::propertyPrefix . 'ID']);

        if ($dimCompatibility != 'OK') {
            return $shutterCompatibility;
        } else {
            return 'OK';
        }
    }

    public function getStatusPrefix()
    {
        return 'Shutter: ';
    }

    public function doQuery($configuration)
    {
        if (IPS_VariableExists($configuration[self::propertyPrefix . 'ID'])) {
            $openPercent = 0;
            if (self::hasShutterProfile($configuration)) {
                $openPercent = self::getShutterOpen($configuration[self::propertyPrefix . 'ID']) ? 100 : 0;
            } else {
                $openPercent = 100 - self::getDimValue($configuration[self::propertyPrefix . 'ID']);
            }
            return [
                'openState' => [[
                    'openPercent'   => $openPercent,
                    'openDirection' => 'DOWN'
                ]]
            ];
        } else {
            return [];
        }
    }

    public function doExecute($configuration, $command, $data, $emulateStatus)
    {
        switch ($command) {
            case 'action.devices.commands.OpenClose':
                if (self::hasShutterProfile($configuration)) {
                    $open = ($data['openPercent'] > 50);
                    if (self::setShutterOpen($configuration[self::propertyPrefix . 'ID'], $open)) {
                        if (!$emulateStatus) {
                            $i = 0;
                            while (($open != self::getShutterOpen($configuration[self::propertyPrefix . 'ID'])) && $i < 10) {
                                $i++;
                                usleep(100000);
                            }
                            $open = self::getShutterOpen($configuration[self::propertyPrefix . 'ID']);
                        }
                        return [
                            'ids'    => [$configuration['ID']],
                            'status' => 'SUCCESS',
                            'states' => [
                                'openState' => [[
                                    'openPercent'   => $open ? 100 : 0,
                                    'openDirection' => 'DOWN'
                                ]],
                                'online' => true
                            ]
                        ];
                    } else {
                        return [
                            'ids'       => [$configuration['ID']],
                            'status'    => 'ERROR',
                            'errorCode' => 'deviceTurnedOff'
                        ];
                    }
                } else {
                    $closePercentage = 100 - $data['openPercent'];
                    if (self::dimDevice($configuration[self::propertyPrefix . 'ID'], $closePercentage)) {
                        if (!$emulateStatus) {
                            $i = 0;
                            while (($closePercentage != self::getDimValue($configuration[self::propertyPrefix . 'ID'])) && $i < 10) {
                                $i++;
                                usleep(100000);
                            }
                            $closePercentage = self::getDimValue($configuration[self::propertyPrefix . 'ID']);
                        }
                        return [
                            'ids'    => [$configuration['ID']],
                            'status' => 'SUCCESS',
                            'states' => [
                                'openState' => [[
                                    'openPercent'   => (100.0 - $closePercentage),
                                    'openDirection' => 'DOWN'
                                ]],
                                'online' => true
                            ]
                        ];
                    } else {
                        return [
                            'ids'       => [$configuration['ID']],
                            'status'    => 'ERROR',
                            'errorCode' => 'deviceTurnedOff'
                        ];
                    }
                }
                break;

            default:
                throw new Exception('Command is not supported by this trait!');
        }
    }

    public function getObjectIDs($configuration)
    {
        return [
            $configuration[self::propertyPrefix . 'ID']
        ];
    }

    public function supportedTraits($configuration)
    {
        return [
            'action.devices.traits.OpenClose'
        ];
    }

    public function supportedCommands()
    {
        return [
            'action.devices.commands.OpenClose'
        ];
    }

    protected function getSupportedProfiles()
    {
        return [
            self::propertyPrefix . 'ID' => ['~ShutterMoveStop', '~ShutterMoveStep', '~Intensity.100', '~Intensity.255', '~Intensity.1']
        ];
    }

    private function hasShutterProfile($configuration)
    {
        return self::getShutterCompatibility($configuration[self::propertyPrefix . 'ID']) == 'OK';
    }
}
