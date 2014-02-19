<?php

/**
 * Avisota newsletter and mailing system
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @package    avisota/contao-core
 * @license    LGPL-3.0+
 * @filesource
 */

namespace Avisota\Contao\Message\Core\Event;

use Avisota\Contao\Message\Core\Message\Renderer\MessageContentPreRendererInterface;
use Symfony\Component\EventDispatcher\Event;

class InitializeMessageContentRendererEvent extends Event
{
	const NAME = 'Avisota\Contao\Message\Core\Event\InitializeMessageContentRenderer';

	/**
	 * @var MessageContentRendererInterface
	 */
	protected $renderer;

	function __construct(MessageContentPreRendererInterface $renderer)
	{
		$this->renderer = $renderer;
	}

	/**
	 * @return MessageContentRendererInterface
	 */
	public function getRenderer()
	{
		return $this->renderer;
	}
}