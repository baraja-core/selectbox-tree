<?php

declare(strict_types=1);

namespace Baraja\SelectboxTree;


final class SelectboxItem
{
	/** @var int|string */
	private $id;

	private string $name;

	/** @var int|string|null */
	private $parentId;


	/**
	 * @param int|string $id
	 * @param int|string|null $parentId
	 */
	public function __construct($id, string $name, $parentId = null)
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
