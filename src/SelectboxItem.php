<?php

declare(strict_types=1);

namespace Baraja\SelectboxTree;


final class SelectboxItem
{
	private int|string $id;

	private string $name;

	private int|string|null $parentId;


	public function __construct(int|string $id, string $name, int|string|null $parentId = null)
	{
		$this->id = $id;
		$this->name = $name;
		$this->parentId = $parentId;
	}


	/**
	 * @return string[]|int[]|null[]
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'parent' => $this->parentId,
		];
	}
}
