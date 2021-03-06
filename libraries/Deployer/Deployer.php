<?php
/**
 * Seabreeze
 * Copyright (C) 2015 IceFlame.net
 *
 * Permission to use, copy, modify, and/or distribute this software for
 * any purpose with or without fee is hereby granted, provided that the
 * above copyright notice and this permission notice appear in all copies.
 *
 * @package  FlameCore\Seabreeze
 * @version  0.1-dev
 * @link     http://www.flamecore.org
 * @license  ISC License <http://opensource.org/licenses/ISC>
 */

namespace FlameCore\Seabreeze\Deployer;

use FlameCore\Seabreeze\Manifest\Environment;
use FlameCore\Synchronizer\SynchronizerFactoryInterface;
use FlameCore\EventObserver\ObserverInterface;

/**
 * The Deployer class
 *
 * @author   Christian Neff <christian.neff@gmail.com>
 */
class Deployer
{
    /**
     * @var \FlameCore\Synchronizer\SynchronizerFactoryInterface[]
     */
    protected $engines = array();

    /**
     * @var \FlameCore\EventObserver\ObserverInterface
     */
    protected $observer;

    /**
     * @var array
     */
    protected $failed = array();

    /**
     * @param \FlameCore\Seabreeze\Manifest\Environment $environment
     * @param bool $preserve
     * @return bool
     */
    public function deploy(Environment $environment, $preserve = true)
    {
        if ($this->observer) {
            $this->observer->notify('deploy.start');
        }

        foreach ($environment->getSyncJobs() as $job) {
            $mode = $job->getMode();

            if (!$this->supports($mode)) {
                continue;
            }

            $factory = $this->getFactory($mode);
            $sourceSettings = $job->getSource();

            foreach ($job->getTargets() as $targetSettings) {
                $synchronizer = $factory->create($sourceSettings, $targetSettings);

                $excludes = $job->getExcludes();
                $synchronizer->setExcludes($excludes);

                if ($this->observer) {
                    $this->observer->setData('sync', 'engine', $mode);
                    $synchronizer->observe($this->observer);
                }

                $result = $synchronizer->synchronize($preserve);

                if ($result !== null && !$result) {
                    $this->failed[] = $mode;
                }
            }
        }

        if ($this->observer) {
            $this->observer->notify('deploy.finish');
        }

        return empty($this->failed);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function supports($name)
    {
        return isset($this->engines[$name]);
    }

    /**
     * @param string $name
     * @param \FlameCore\Synchronizer\SynchronizerFactoryInterface $factory
     */
    public function register($name, SynchronizerFactoryInterface $factory)
    {
        $this->engines[$name] = $factory;
    }

    /**
     * @param \FlameCore\EventObserver\ObserverInterface $observer
     */
    public function observe(ObserverInterface $observer)
    {
        $this->observer = $observer;
    }

    /**
     * @return array
     */
    public function getFailed()
    {
        return $this->failed;
    }

    /**
     * @param string $name
     * @return \FlameCore\Synchronizer\SynchronizerFactoryInterface
     */
    protected function getFactory($name)
    {
        if (!isset($this->engines[$name])) {
            throw new \DomainException(sprintf('The engine "%s" does not exist.', $name));
        }

        return $this->engines[$name];
    }
}
