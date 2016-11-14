<?php

namespace Vizzle\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Service table.
 *
 * @ORM\Entity()
 * @ORM\Table(name="VService")
 * @ORM\HasLifecycleCallbacks()
 */
class Service
{
    /**
     * @var integer
     *
     * @ORM\Id()
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Server name.
     *
     * @var string
     *
     * @ORM\Column(name="server", type="string")
     */
    private $server;

    /**
     * Service name.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * Service description.
     *
     * @var string
     *
     * @ORM\Column(name="description", type="string", nullable=true)
     */
    private $description;

    /**
     * Service class.
     *
     * @var string
     *
     * @ORM\Column(name="class", type="string")
     */
    private $class;

    /**
     * Service lifetime.
     *
     * @var string
     *
     * @ORM\Column(name="lifetime", type="integer")
     */
    private $lifetime = 0;

    /**
     * Service mode.
     *
     * @var string
     *
     * @ORM\Column(name="mode", type="string")
     */
    private $mode;

    /**
     * Service timer.
     *
     * @var string
     *
     * @ORM\Column(name="timer", type="integer")
     */
    private $timer = 1000;

    /**
     * Service pid.
     *
     * @var string
     *
     * @ORM\Column(name="pid", type="integer", nullable=true)
     */
    private $pid = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * On PrePersist
     *
     * @ORM\PreFlush()
     */
    public function onPreFlush()
    {
        $this->updatedAt = new \DateTime();
    }

    //


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set server
     *
     * @param string $server
     *
     * @return Service
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get server
     *
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Service
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Service
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set class
     *
     * @param string $class
     *
     * @return Service
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set lifetime
     *
     * @param integer $lifetime
     *
     * @return Service
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;

        return $this;
    }

    /**
     * Get lifetime
     *
     * @return integer
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * Set mode
     *
     * @param string $mode
     *
     * @return Service
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Get mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set timer
     *
     * @param integer $timer
     *
     * @return Service
     */
    public function setTimer($timer)
    {
        $this->timer = $timer;

        return $this;
    }

    /**
     * Get timer
     *
     * @return integer
     */
    public function getTimer()
    {
        return $this->timer;
    }

    /**
     * Set pid
     *
     * @param integer $pid
     *
     * @return Service
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * Get pid
     *
     * @return integer
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Service
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
