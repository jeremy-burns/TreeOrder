<?php
App::uses('ModelBehavior', 'Model');

class TreeOrderBehavior extends ModelBehavior {

	function beforeSave(Model $Model, $options = array()) {

		if (
			(!empty($Model->data[$Model->alias]['title']) || !empty($Model->data[$Model->alias]['parent_id']))
			&& (empty($Model->titleUpdated) || $Model->titleUpdated != 'in_progress')
		) {
			$Model->titleUpdated = 'start';
		}

		return true;

	}

	public function afterSave(Model $Model, $created) {

		if (!empty($Model->titleUpdated) && $Model->titleUpdated == 'start') {

			$originalId = $Model->id;

			$Model->titleUpdated = 'in_progress';

			$this->__setPath($Model, $Model->id);

			$children = $Model->find(
				'list',
				array(
					'conditions' => array(
						$Model->alias.'.lft >' => $Model->field('lft'),
						$Model->alias.'.rght <' => $Model->field('rght')
					),
					'fields' => array(
						$Model->alias.'.id',
						$Model->alias.'.parent_id'
					)
				)
			);

			if ($children) {

				foreach ($children as $id => $child) {
					$this->__setPath($Model, $id);
				}

			}

			$Model->id = $originalId;

		}

	}

	private function __setPath($Model, $id) {

		$Model->id = $id;

		$path = $this->__path($Model, $id);

		$Model->set(array(
			'path' => $path['path'],
			'tree_level' => $path['tree_level']
		));

		return $Model->save();

	}

	private function __path($Model, $id = null) {

		if (!$id) return null;

		$fullPath = $Model->getPath(
			$id,
			array('title')
		);

		if ($fullPath) {

			if (is_array($fullPath)) {
				$fullPath = Hash::extract($fullPath, '{n}.' . $Model->alias . '.title');
			}

			$path = implode($fullPath, ' > ');
			$treeLevel = (count($fullPath) - 1);

			return array(
				'path' => $path,
				'tree_level' => $treeLevel
			);
		}

		return false;

	}

	public function setAllPaths(Model $Model) {

		$Model->recover();

		$rows = $Model->find(
			'all',
			array(
				'fields' => array(
					$Model->alias . '.id'
				)
			)
		);

		if ($rows) {

			foreach ($rows as $row) {
				$this->__setPath($Model, $row[$Model->alias]['id']);
			}

		}
	}

}