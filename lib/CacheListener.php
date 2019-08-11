<?php declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FilesAutomatedTagging;

use OCP\Files\Cache\CacheInsertEvent;
use OCP\Files\Cache\CacheUpdateEvent;
use OCP\Files\Cache\ICacheEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use OCP\SystemTag\MapperEvent;
use OC\Files\Filesystem;

class CacheListener {
	private $eventDispatcher;
	private $operation;
	private $filesystem;

	public function __construct(EventDispatcher $eventDispatcher, Operation $operation, Filesystem $filesystem) {
		$this->eventDispatcher = $eventDispatcher;
		$this->operation = $operation;
		$this->filesystem = $filesystem;
	}

	public function listen() {
		$this->eventDispatcher->addListener(CacheInsertEvent::class, [$this, 'onCacheEvent']);
		$this->eventDispatcher->addListener(CacheUpdateEvent::class, [$this, 'onCacheEvent']);
		$this->eventDispatcher->addListener(MapperEvent::EVENT_ASSIGN, [$this, 'onMapperEvent']);
		$this->eventDispatcher->addListener(MapperEvent::EVENT_UNASSIGN, [$this, 'onMapperEvent']);
	}

	public function onMapperEvent(MapperEvent $event) {
	    $path = $this->filesystem->getPath($event->getObjectId());
	    $info = $this->filesystem->getFileInfo($path, false);
	    $storage = $info->getStorage();

	    if ($this->operation->isTaggingPath($storage, $event->getObjectType() . $path)) {
	        $this->operation->checkOperations($storage, $event->getObjectId(), $event->getObjectType() . $path);
	    }
	}

	public function onCacheEvent(ICacheEvent $event) {
		if ($this->operation->isTaggingPath($event->getStorage(), $event->getPath())) {
			$this->operation->checkOperations($event->getStorage(), $event->getFileId(), $event->getPath());
		}
	}
}
