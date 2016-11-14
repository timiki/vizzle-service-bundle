<?php

namespace Vizzle\ServiceBundle\Mapper;

use Vizzle\VizzleBundle\Mapper\Exceptions\InvalidMappingException;
use Vizzle\VizzleBundle\Mapper\AbstractMapper;
use Vizzle\ServiceBundle\Mapping;

/**
 * Service mapper.
 */
class ServiceMapper extends AbstractMapper
{
    /**
     * Process class.
     *
     * @param \ReflectionClass $reflectionClass Class
     *
     * @return array|null
     * @throws InvalidMappingException
     */
    public function processReflectionClass($reflectionClass)
    {
        if ($process = $this->reader->getClassAnnotation($reflectionClass, Mapping\Process::class)) {

            $metadata = [];

            if (empty($process->name)) {
                throw new InvalidMappingException(
                    sprintf(
                        '@Process annotation must have name in class "%s".',
                        $reflectionClass->getName()
                    )
                );
            }

            $metadata['class']       = $reflectionClass->getName();
            $metadata['file']        = $reflectionClass->getFileName();
            $metadata['name']        = strtolower($process->name);
            $metadata['description'] = $process->description;
            $metadata['lifetime']    = (integer)$process->lifetime;
            $metadata['mode']        = strtoupper($process->mode);
            $metadata['timer']       = (integer)$process->timer;

            // Find execute methods

            $metadata['execute'] = [];

            foreach ($reflectionClass->getMethods() as $reflectionMethod) {

                if ($this->reader->getMethodAnnotation($reflectionMethod, Mapping\Execute::class)) {
                    $metadata['execute'][] = $reflectionMethod->name;;
                }

            }

            // Events

            $metadata['onStart'] = [];

            foreach ($reflectionClass->getMethods() as $reflectionMethod) {

                if ($this->reader->getMethodAnnotation($reflectionMethod, Mapping\OnStart::class)) {
                    $metadata['onStart'][] = $reflectionMethod->name;;
                }

            }

            $metadata['onStop'] = [];

            foreach ($reflectionClass->getMethods() as $reflectionMethod) {

                if ($this->reader->getMethodAnnotation($reflectionMethod, Mapping\OnStop::class)) {
                    $metadata['onStop'][] = $reflectionMethod->name;
                }

            }

            $metadata['onError'] = [];

            foreach ($reflectionClass->getMethods() as $reflectionMethod) {

                if ($this->reader->getMethodAnnotation($reflectionMethod, Mapping\OnError::class)) {
                    $metadata['onError'][] = $reflectionMethod->name;
                }

            }

            return $metadata;
        }

        return null;
    }

}
