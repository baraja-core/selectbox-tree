<?php

declare(strict_types=1);

namespace Baraja\SelectboxTree;


final class SelectboxTree
{
	private const NBSP = "\xC2\xA0\xC2\xA0\xC2\xA0";

	private int $maxDepth = 32;

	private ?NameFormatter $nameFormatter = null;


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
	 * @return string[] (id => user haystack)
	 */
	public function process(array $data): array
	{
		$categories = [];
		foreach ($data as $item) {
			$categories[] = [
				'id' => $item['id'],
				'name' => $this->nameFormatter === null ? (string) $item['name'] : $this->nameFormatter->format($item['name']),
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


	public function setMaxDepth(int $maxDepth): void
	{
		if ($maxDepth < 1) {
			$maxDepth = 1;
		} elseif ($maxDepth > 1000) {
			throw new \InvalidArgumentException('Max depth "' . $maxDepth . '" is too big. Maximum value is 1000.');
		}
		$this->maxDepth = $maxDepth;
	}


	public function setNameFormatter(NameFormatter $nameFormatter): void
	{
		$this->nameFormatter = $nameFormatter;
	}


	/**
	 * Build category tree to simple selectbox array.
	 *
	 * @param string[][]|null[][]|null $categories
	 * @param int|string|null $parent
	 * @return mixed[][]
	 */
	private function serializeCategoriesToSelectbox(?array $categories, int $level = 0, $parent = null): array
	{
		static $usedIds = [];

		if ($level === 0) {
			$usedIds = [];
		}
		if ($categories === null || $categories === [] || $level > $this->maxDepth) { // empty or infinity recursion
			return [];
		}

		$return = [];
		foreach ($categories as $catKey => $category) {
			if (array_key_exists('id', $category) === false || array_key_exists('parent', $category) === false || array_key_exists('name', $category) === false) {
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
