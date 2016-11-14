<?php

namespace Vizzle\ServiceBundle\Mapping;

/**
 * Mark class as vizzle service process.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Process
{
    /**
     * Service name.
     *
     * @var string
     */
    public $name;

    /**
     * Service description.
     *
     * @var string
     */
    public $description;

    /**
     * Max service lifetime (in seconds) for terminate.
     *
     * @var integer
     */
    public $lifetime = 3600;

    /**
     * Service startup mode.
     *
     * @var string
     *
     * @Enum({"AUTO", "MANUAL"})
     */
    public $mode = 'AUTO';

    /**
     * Time between execute call (in ms).
     *
     * @var integer
     */
    public $timer = 1000;

}
