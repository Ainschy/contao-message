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

use Avisota\Contao\Entity\Message;
use Avisota\Contao\Entity\MessageCategory;
use Avisota\Contao\Entity\MessageContent;
use BackendTemplate;
use Contao\Doctrine\ORM\EntityHelper;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\IdSerializer;

class CustomMenu extends \BackendModule
{
	static public function hookGetUserNavigation(array $navigation, $showAll)
	{
		if (TL_MODE == 'BE') {
			try {
				if (!$showAll) {
					$database = \Database::getInstance();

					if ($database->tableExists('orm_avisota_message_category')) {

						$category = Helper::resolveCategoryFromInput();

						if ($category) {
							$foundCustomEntry = false;

							$menu = & $navigation['avisota'];
							foreach ($menu['modules'] as $name => &$module) {
								if ($name == 'avisota_category_' . $category->getId()) {
									$module['class'] .= ' active';
									$foundCustomEntry = true;
								}
							}

							if ($foundCustomEntry) {
								$classes = explode(' ', $menu['modules']['avisota_newsletter']['class']);
								$classes = array_map('trim', $classes);
								$pos     = array_search('active', $classes);
								if ($pos !== false) {
									unset($classes[$pos]);
								}
								$menu['modules']['avisota_newsletter']['class'] = implode(' ', $classes);
							}
						}
					}
				}
			}
			catch (\Exception $exception) {
				// silently ignore
			}
		}
		return $navigation;
	}

	public function injectMenu()
	{
		global $container;

		// initialize the entity manager and class loaders
		$container['doctrine.orm.entityManager'];

		$beModules = array();

		if (class_exists('Avisota\Contao\Entity\MessageCategory')) {
			try {
				$messageCategoryRepository = EntityHelper::getRepository('Avisota\Contao:MessageCategory');
				$queryBuilder              = $messageCategoryRepository->createQueryBuilder('mc');
				$queryBuilder
					->select('mc')
					->where('mc.showInMenu=:showInMenu')
					->setParameter('showInMenu', true)
					->orderBy('mc.title');
				$query = $queryBuilder->getQuery();
				/** @var MessageCategory[] $messageCategories */
				$messageCategories = $query->getResult();

				foreach ($messageCategories as $messageCategory) {
					$id    = $messageCategory->getId();
					$icon  = $messageCategory->getUseCustomMenuIcon()
						? $messageCategory->getMenuIcon()
						: 'assets/avisota/message/images/newsletter.png';
					$title = $messageCategory->getTitle();

					$beModules['avisota_category_' . $id] = array(
						'callback' => 'Avisota\Contao\Message\Core\Backend\CustomMenu',
						'icon'     => $icon,
					);

					$GLOBALS['TL_LANG']['MOD']['avisota_category_' . $id] = array($title);
				}
			}
			catch (\Exception $e) {
				// silently ignore
			}
		}

		if (count($beModules)) {
			$GLOBALS['BE_MOD']['avisota'] = array_merge(
				$beModules,
				$GLOBALS['BE_MOD']['avisota']
			);
		}
	}

	public function generate()
	{
		$do = \Input::getInstance()
			->get('do');
		$id = preg_replace('#^avisota_category_(.*)$#', '$1', $do);

		$serializer = new IdSerializer();
		$serializer->setDataProviderName('orm_avisota_message_category');
		$serializer->setId($id);

		$this->redirect('contao/main.php?do=avisota_newsletter&table=orm_avisota_message&pid=' . $serializer->getSerialized());
	}

	/**
	 * Compile the current element
	 */
	protected function compile()
	{
	}
}
