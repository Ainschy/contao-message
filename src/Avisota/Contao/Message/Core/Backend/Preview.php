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

namespace Avisota\Contao\Message\Core\Backend;

use Avisota\Contao\Core\Message\Renderer;
use Contao\Doctrine\ORM\EntityHelper;
use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\RedirectEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LoadLanguageFileEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\IdSerializer;
use ContaoCommunityAlliance\DcGeneral\DC_General;
use ContaoCommunityAlliance\DcGeneral\DcGeneralEvents;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use ContaoCommunityAlliance\DcGeneral\Event\ActionEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Preview implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return array(
			DcGeneralEvents::ACTION => 'handleAction',
		);
	}

	public function handleAction(ActionEvent $event)
	{
		if (
			!$event->getResponse() &&
			$event->getEnvironment()->getDataDefinition()->getName() == 'orm_avisota_message' &&
			$event->getAction()->getName() == 'preview'
		) {
			$event->setResponse($this->renderPreviewView($event->getEnvironment()));
		}
	}

	/**
	 * @param DC_General $dc
	 */
	public function renderPreviewView(EnvironmentInterface $environment)
	{
		/** @var EventDispatcher $eventDispatcher */
		$eventDispatcher = $GLOBALS['container']['event-dispatcher'];

		$eventDispatcher->dispatch(
			ContaoEvents::SYSTEM_LOAD_LANGUAGE_FILE,
			new LoadLanguageFileEvent('avisota_message_preview')
		);
		$eventDispatcher->dispatch(
			ContaoEvents::SYSTEM_LOAD_LANGUAGE_FILE,
			new LoadLanguageFileEvent('orm_avisota_message')
		);

		$input             = \Input::getInstance();
		$messageRepository = EntityHelper::getRepository('Avisota\Contao:Message');

		$messageId = IdSerializer::fromSerialized($input->get('id') ? $input->get('id') : $input->get('pid'));
		$message   = $messageRepository->find($messageId->getId());

		if (!$message) {
			$environment = \Environment::getInstance();

			$eventDispatcher->dispatch(
				ContaoEvents::CONTROLLER_REDIRECT,
				new RedirectEvent(
					preg_replace(
						'#&(act=preview|id=[a-f0-9\-]+)#',
						'',
						$environment->request
					)
				)
			);
		}

		$modules = new \StringBuilder();
		/** @var \Avisota\Contao\Message\Core\Send\SendModuleInterface $module */
		foreach ($GLOBALS['AVISOTA_SEND_MODULE'] as $className) {
			$class = new \ReflectionClass($className);
			$module = $class->newInstance();
			$modules->append($module->run($message));
		}

		$context = array(
			'message' => $message,
			'modules' => $modules,
		);

		$template = new \TwigTemplate('avisota/backend/preview', 'html5');
		return $template->parse($context);
	}
}
