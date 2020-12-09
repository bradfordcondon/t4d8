<?php

namespace Drupal\tripal\Entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Entity\EntityBundleListenerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * A content entity database storage implementation for TripalEntity.
 *
 * This class provides a way for fields to store linking information in the
 * standard Drupal way but store the main content of a field somewhere else.
 *
 * NOTE: We still use the default SQL storage because Drupal 8/9 requires all
 * fields on an entity to use the same field storage. However, we only use the
 * default storage to keep track of any linking information (i.e. primary key).
 *
 * DO NOT OVERRIDE THIS CLASS! Instead, create a tripal storage plugin instance.
 */
class TripalContentEntityStorage extends SQLContentEntityStorage implements SqlEntityStorageInterface, DynamicallyFieldableEntityStorageSchemaInterface, EntityBundleListenerInterface {

	/**
	 * @{inheritdoc}
	 */
	public function doLoadMultiple(array $ids = NULL) {
		$entities = parent::doLoadMultiple($ids);

		// @debug dpm($entities, 'TripalContentEntityStorage::doLoadMultiple()');
		$pluginManager = \Drupal::service('plugin.manager.tripalstorage');
    $plugin_definitions = $pluginManager->getDefinitions();
		foreach ($plugin_definitions as $tsid => $tsdefn) {
			$plugin = $pluginManager->createInstance($tsid);
			$plugin->loadMultipleEntities($ids, $entities);
		}

		return $entities;
	}

	/**
	 * @{inheritdoc}
	 */
	protected function postLoad(array &$entities) {
		parent::postLoad($entities);

		// @debug dpm($entities, 'TripalContentEntityStorage::postLoad()');
		$pluginManager = \Drupal::service('plugin.manager.tripalstorage');
		$plugin_definitions = $pluginManager->getDefinitions();
		foreach ($plugin_definitions as $tsid => $tsdefn) {
			$plugin = $pluginManager->createInstance($tsid);
			$plugin->postEntityLoad($entities);
		}
	}

	/**
	 * @{inheritdoc}
	 */
	protected function doPreSave(EntityInterface $entity) {

		// @debug dpm(['id' => $id, 'entity' => $entity], 'TripalContentEntityStorage::doPreSave()');
		$pluginManager = \Drupal::service('plugin.manager.tripalstorage');
		$plugin_definitions = $pluginManager->getDefinitions();
		foreach ($plugin_definitions as $tsid => $tsdefn) {
			$plugin = $pluginManager->createInstance($tsid);
			$plugin->preSaveEntity($entity);
		}

		$id = parent::doPreSave($entity);

		return $id;
	}

	/**
	 * @{inheritdoc}
	 */
	protected function doSave($id, EntityInterface $entity) {

		// @debug dpm(['success' => $success, 'id' => $id, 'entity' => $entity], 'TripalContentEntityStorage::doSave()');
		$pluginManager = \Drupal::service('plugin.manager.tripalstorage');
		$plugin_definitions = $pluginManager->getDefinitions();
		foreach ($plugin_definitions as $tsid => $tsdefn) {
			$plugin = $pluginManager->createInstance($tsid);
			$plugin->saveEntity($id, $entity);
		}

		$success = parent::doSave($id, $entity);

		return $success;
	}

	/**
	 * @{inheritdoc}
	 */
	protected function doPostSave(EntityInterface $entity, $update) {
		parent::doPostSave($entity, $update);

		// @debug dpm(['update' => $update, 'entity' => $entity], 'TripalContentEntityStorage::doPostSave()');
		$pluginManager = \Drupal::service('plugin.manager.tripalstorage');
		$plugin_definitions = $pluginManager->getDefinitions();
		foreach ($plugin_definitions as $tsid => $tsdefn) {
			$plugin = $pluginManager->createInstance($tsid);
			$plugin->postSaveEntity($entity, $update);
		}

	}

}
