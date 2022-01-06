<?php

declare(strict_types=1);

abstract class DeviceTrait extends CommonCapability
{    
    abstract public function doQuery($configuration);
    abstract public function doExecute($configuration, $command, $data, $emulateStatus);
    abstract public function supportedTraits($configuration);
    abstract public function supportedCommands();

    public function getAttributes()
    {
        return [];
    }
}
