<?php

/**
 * Avisota newsletter and mailing system
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @package    avisota/contao-message
 * @license    LGPL-3.0+
 * @filesource
 */

namespace Avisota\Contao\Message\Core\Entity;

use Avisota\Contao\Message\Core\Event\ResolveStylesheetEvent;
use Contao\Doctrine\ORM\Entity;
use Contao\Doctrine\ORM\EntityInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Contao\Doctrine\ORM\Annotation\Accessor;

abstract class AbstractLayout implements EntityInterface
{
	/**
	 * @Accessor(ignore=true)
	 *
	 * @return array
	 */
	public function getStylesheetPaths()
	{
		/** @var EventDispatcher $eventDispatcher */
		$eventDispatcher = $GLOBALS['container']['event-dispatcher'];

		$paths = array();
		$stylesheets = $this->getStylesheets();
		if ($stylesheets) {
			foreach ($stylesheets as $stylesheet) {
				$event = new ResolveStylesheetEvent($stylesheet);
				$eventDispatcher->dispatch(ResolveStylesheetEvent::NAME, $event);
				$stylesheet = $event->getStylesheet();

				if (!file_exists(TL_ROOT . '/' . $stylesheet)) {
					throw new \RuntimeException('Missing stylesheet ' . $stylesheet);
				}

				$paths[] = $stylesheet;
			}
		}
		
		return $paths;
	}
}
