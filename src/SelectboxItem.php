<?php

declare(strict_types=1);

namespace Baraja\SelectboxTree;


final class SelectboxItem
{
	public function __construct(
		private int|string $id,
		private string $name,
		private int|string |null $parentId = null
	) {
	}


	/**
	 * @return array{id: int|string, name: string, parent: int|string|null}
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
