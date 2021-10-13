<?php

namespace rollun\metrics;

trait GetServiceName
{
    /**
     * @throws \Exception
     */
    protected function getServiceName(): string
    {
        $serviceName = exec('hostname');

        if ($serviceName === false) {
            throw new \Exception("Can't get service name");
        }

        $serviceNameParts = explode('.', $serviceName);

        if (!empty($serviceNameParts)) {
            $serviceName = $serviceNameParts[0];
        }

        return str_replace('-', '_', $serviceName);
    }
}