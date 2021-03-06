<?php

/*
 * pmt.mcpe.me
 *
 * Copyright (C) 2015 PEMapModder
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

namespace pg\lib\exe;

use pg\lib\exe\resource\PluginResource;
use pg\lib\exe\resource\Resource;

class Context{
	/** @var int */
	private $contextId;
	/** @var Resource[] */
	private $resources = [];
	/** @var Context[] */
	private $children = [];
	/** @var string */
	private $mainRef;

	public function __construct($mainRef){
		$this->contextId = getNextGLobalId();
		foreach(self::defaultResources($mainRef) as $res){
			$this->addResource($res);
		}
		$_SESSION["contexts"][$this->contextId] = $this;
		$this->mainRef = $mainRef;
	}
	/**
	 * @param $mainRef
	 * @return resource\Resource[]
	 */
	private static function defaultResources($mainRef){
		return [
			new PluginResource($mainRef, "this plugin"),
		];
	}

	/**
	 * @return resource\Resource[]
	 */
	public function getResources(){
		return $this->resources;
	}

	public function addResource(Resource $resource){
		$this->resources[$resource->resId] = $resource;
		foreach($resource->getChildResources() as $res){
			$res->parent = $resource;
			$this->addResource($res);
		}
	}
	/**
	 * @return Context[]
	 */
	public function getChildren(){
		return $this->children;
	}
	public function addChild(Context $context){
		$this->children[$context->contextId] = $context;
	}
	public function deleteTree(){
		foreach($this->children as $child){
			$child->deleteTree();
		}
		unset($_SESSION["contexts"][$this->contextId]);
	}

	/**
	 * @return int
	 */
	public function getContextId(){
		return $this->contextId;
	}
	public function getMainRef(){
		return $this->mainRef;
	}
}
