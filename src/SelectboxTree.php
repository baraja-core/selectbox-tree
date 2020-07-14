<?php

declare(strict_types=1);

namespace Baraja\SelectboxTree;


final class SelectboxTree
{
	private const NBSP = "\xC2\xA0\xC2\xA0\xC2\xA0";

	/** @var int */
	private $maxDepth = 32;


	/**
	 * Build tree of categories or items to simple-plain selectbox.
	 *
	 * Main category
	 * Phone
	 * |  iPhone
	 * Computer
	 * |  Mac
	 * |  |  MacBook
	 * |  |  iMac
	 * |  Windows
	 *
	 * @param mixed[][] $data raw database result in format [{"id", "name", "parent_id"}]
	 * @param callable(mixed $name): string|null $nameFormatter
	 * @return string[] (id => user haystack)
	 */
	public function process(array $data, ?callable $nameFormatter = null): array
	{
		$categories = [];
		foreach ($data as $item) {
			$categories[] = [
				'id' => $item['id'],
				'name' => (string) ($nameFormatter === null ? $item['name'] : $nameFormatter($item['name'])),
				'parent' => $item['parent_id'],
			];
		}

		$return = [];
		foreach ($this->serializeCategoriesToSelectbox($categories) as $key => $category) {
			$return[$key] = str_repeat('|' . self::NBSP, $category['level']) . $category['name'];
		}

		return $return;
	}


	/**
	 * @param string $table
	 * @param string $primaryCol
	 * @param string $parentCol
	 * @param string[] $wheres
	 * @return string
	 */
	public function sqlBuilder(string $table, string $primaryCol = 'name', string $parentCol = 'parent_id', array $wheres = []): string
	{
		return 'SELECT `id`, `' . $primaryCol . '`, `' . $parentCol . '` '
			. 'FROM `' . $table . '` '
			. ($wheres !== [] ? 'WHERE (' . implode(') AND (', $wheres) . ') ' : '')
			. 'ORDER BY `' . $primaryCol . '` ASC';
	}


	/**
	 * @param int $maxDepth
	 */
	public function setMaxDepth(int $maxDepth): void
	{
		if ($maxDepth < 1) {
			$maxDepth = 1;
		}
		$this->maxDepth = $maxDepth;
	}


	/**
	 * Build category tree to simple selectbox array.
	 *
	 * @param string[][]|null[][]|null $categories
	 * @param int $level
	 * @param string|null $parent
	 * @return mixed[][]
	 */
	private function serializeCategoriesToSelectbox(?array $categories, int $level = 0, ?string $parent = null): array
	{
		static $usedIds = [];

		if ($level === 0) {
			$usedIds = [];
		}

		if ($categories === null || $categories === [] || $level > $this->maxDepth) { // empty or recursion
			return [];
		}

		$return = [];
		foreach ($categories as $catKey => $category) {
			if (isset($category['id'], $category['parent'], $category['name']) === false) {
				throw new \InvalidArgumentException('Category "' . $catKey . '" must contain keys "id", "parent" and "name".');
			}

			if ($category['parent'] === $parent) {
				if (isset($usedIds[$category['id']]) === false) {
					$return[$category['id']] = [
						'name' => (string) $category['name'],
						'level' => $level,
					];
					unset($categories[$catKey]);
					$usedIds[$category['id']] = true;
				}

				if (($sub = $this->serializeCategoriesToSelectbox($categories, $level + 1, $category['id'])) !== []) {
					foreach ($sub as $key => $value) {
						$return[$key] = $value;
					}
				}
			}
		}

		return $return;
	}
}
