<?php

declare(strict_types=1);

namespace Baraja\SelectboxTree;


use Baraja\Localization\Translation;

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
	 * @param array<int, array{id: int|string, name: string, parent_id: int|string|null}>|SelectboxItem[] $data
	 * @return array<int|string, string> (id => user haystack)
	 */
	public function process(array $data): array
	{
		$categories = [];
		foreach ($data as $item) {
			if ($item instanceof SelectboxItem) {
				$categoryItem = $item->toArray();
			} else {
				$categoryItem = [
					'id' => $item['id'],
					'name' => $this->normalizeName($item['name']),
					'parent' => $item['parent_id'],
				];
			}
			if ($this->nameFormatter !== null) {
				$categoryItem['name'] = $this->nameFormatter->format($categoryItem['name']);
			}
			$categories[] = $categoryItem;
		}

		$return = [];
		foreach ($this->serializeCategoriesToSelectbox($categories) as $key => $category) {
			$return[$key] = str_repeat('|' . self::NBSP, $category['level']) . $category['name'];
		}

		return $return;
	}


	/**
	 * @param array<int, string> $wheres
	 */
	public function sqlBuilder(
		string $table,
		string $primaryCol = 'name',
		string $parentCol = 'parent_id',
		array $wheres = [],
		?string $orderCol = null,
	): string {
		return sprintf(
			'SELECT `id`, `%s`, `%s` FROM `%s` %s ORDER BY `%s` ASC',
			$primaryCol,
			$parentCol,
			$table,
			$wheres !== [] ? 'WHERE (' . implode(') AND (', $wheres) . ') ' : '',
			$orderCol ?? $primaryCol,
		);
	}


	public function setMaxDepth(int $maxDepth): void
	{
		if ($maxDepth < 1) {
			$maxDepth = 1;
		} elseif ($maxDepth > 1_000) {
			throw new \InvalidArgumentException(sprintf('Max depth "%d" is too big. Maximum value is 1000.', $maxDepth));
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
	 * @param array<int, array{id: int|string, name: string, parent: int|string|null}>|null $categories
	 * @return array<int|string, array{name: string, level: int}>
	 */
	private function serializeCategoriesToSelectbox(
		?array $categories,
		int $level = 0,
		int|string |null $parent = null,
	): array {
		static $usedIds = [];

		if ($level === 0) {
			$usedIds = [];
		}
		if ($categories === null || $categories === [] || $level > $this->maxDepth) { // empty or infinity recursion
			return [];
		}

		$return = [];
		foreach ($categories as $catKey => $category) {
			if ($category['parent'] === $parent) {
				if (isset($usedIds[$category['id']]) === false) {
					$return[$category['id']] = [
						'name' => $this->normalizeName($category['name']),
						'level' => $level,
					];
					unset($categories[$catKey]);
					$usedIds[$category['id']] = true;
				}
				$sub = $this->serializeCategoriesToSelectbox($categories, $level + 1, $category['id']);
				foreach ($sub as $key => $value) {
					$return[$key] = $value;
				}
			}
		}

		return $return;
	}


	private function normalizeName(string $name): string
	{
		if (class_exists('Baraja\Localization\Translation') && str_starts_with($name, 'T:{')) {
			return (string) (new Translation($name));
		}

		return $name;
	}
}
