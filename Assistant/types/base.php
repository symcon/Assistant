<?php

// For the common implementation, we use the word 'Capability' for device properties, based on the Amazon Alexa wording.
// For Google Assistant, those are called 'Traits', so those two words can be mixed up sometimes

declare(strict_types=1);

abstract class DeviceType extends CommonType
{
    protected $implementedType = '';

    public function __construct(int $instanceID)
    {
        parent::__construct($instanceID, 'DeviceTrait');
    }

    public function doSync($configuration)
    {
        $sync = [
            'id'     => strval($configuration['ID']),
            'type'   => 'action.devices.types.' . $this->implementedType,
            'traits' => [
            ],
            'name' => [
                'name' => $configuration['Name']
            ],
            'willReportState' => false
        ];

        $attributes = [];
        foreach ($this->implementedCapabilities as $capability) {
            $traitObject = $this->generateCapabilityObject($capability);
            $traits = $traitObject->supportedTraits($configuration);
            if (count($traits) > 0) {
                $sync['traits'] = array_merge($sync['traits'], $traits);
                $attributes = array_merge($attributes, $traitObject->getAttributes($configuration));
            }
        }

        if (count($attributes) > 0) {
            $sync['attributes'] = $attributes;
        }

        return $sync;
    }

    public function doQuery($configuration)
    {
        $query = [];

        foreach ($this->implementedCapabilities as $capability) {
            $query = array_merge($query, $this->generateCapabilityObject($capability)->doQuery($configuration));
        }

        $query['online'] = count($query) > 0;

        return $query;
    }

    public function doExecute($configuration, $command, $data, $emulateStatus)
    {
        foreach ($this->implementedCapabilities as $capability) {
            $traitObject = $this->generateCapabilityObject($capability);
            if (in_array($command, $traitObject->supportedCommands())) {
                return $traitObject->doExecute($configuration, $command, $data, $emulateStatus);
            }
        }

        return [
            'ids'       => [$configuration['ID']],
            'status'    => 'ERROR',
            'errorCode' => 'notSupported'
        ];
    }
}
