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

namespace FlameCore\Seabreeze\EventObserver\Responder\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * This Responder writes a Progress Bar to the Console
 *
 * @author   Christian Neff <christian.neff@gmail.com>
 */
class ConsoleProgressResponder extends AbstractConsoleResponder
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progress;

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param array $options
     */
    public function __construct(OutputInterface $output, array $options = [])
    {
        parent::__construct($output);

        $this->options = $options;

        $this->setListener('*.task.start',  [$this, 'onStart']);
        $this->setListener('*.task.status', [$this, 'onStatus']);
        $this->setListener('*.task.finish', [$this, 'onFinish']);
    }

    /**
     * @param array $data
     */
    protected function onStart(array $data)
    {
        $format = isset($this->options['format']) ? $this->options['format'] : 'normal';

        if (isset($data['total']) && $data['total'] > 0) {
            $maxSteps = (int) $data['total'];
            unset($data['total']);
        } else {
            $maxSteps = 1;
        }

        $progress = new ProgressBar($this->output, $maxSteps);
        $progress->setFormat($format);
        $progress->setEmptyBarCharacter(' ');
        $progress->setProgressCharacter(':');

        foreach ($data as $key => $value) {
            $progress->setMessage($value, $key);
        }

        $progress->start();

        $this->progress = $progress;
    }

    /**
     * @param array $data
     */
    protected function onStatus(array $data)
    {
        if (isset($data['current'])) {
            $this->progress->setCurrent((int) $data['current']);
            unset($data['current']);
        } else {
            $this->progress->advance();
        }

        foreach ($data as $key => $value) {
            $this->progress->setMessage($value, $key);
        }
    }

    /**
     * @return void
     */
    protected function onFinish()
    {
        $this->progress->finish();

        $this->output->write(PHP_EOL);
    }
}
