<?php

namespace pg\lib;

class Generator{
	/** @var Project */
	private $project;
	/** @var string[] */
	public $files = [];
	public function __construct(Project $project){
		$this->project = $project;
	}
	public function generate(){
		$this->generateYaml();
		$this->generateMainClass();
		$this->generateCommands();
		if(count($this->project->events) > 0){
			$this->generateEvents();
		}
		$this->includePhpResource("CommandArgsMap");
	}
	private function generateYaml(){
		$desc = $this->project->getDesc();
		$data = [
			"name" => $desc->getName(),
			"version" => $desc->getVersion(),
			"api" => $desc->getCompatibleApis(),
			"authors" => $desc->getAuthors(),
			"main" => $desc->getMain()
		];
		$this->addFile("plugin.yml", yaml_emit($data));
	}
	private function generateMainClass(){
		$class = new ClassGenerator($this->project->namespace, "MainClass");
		$class->addImport("pocketmine\\plugin\\PluginBase");
		$class->setSuperClass("PluginBase");
		$onEnable = new GeneratedFunctionContainer;
		$onEnable->name = "onEnable";
		foreach($this->project->cmds as $cmd){
			$pluginName = strtolower(str_replace([":", " "], "-", $this->project->getDesc()->getName()));
			$onEnable->code .= '$this->getServer()->getCommandMap()->register(' . var_export($pluginName, true) . ', new cmds\\' . $cmd->getClassName() . '($this)); // this line registers the command /' . str_replace(["\r", "\n"], "<br>", $cmd->name);
		}
		if(count($this->project->events) > 0){
			$onEnable->code .= '$this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);';
		}
		$class->addFunction($onEnable);
		$this->addFile("src/" . $this->project->namespace . "/MainClass.php", $class);
	}
	private function generateCommands(){
		foreach($this->project->cmds as $cmd){
			$include = true;
			$this->addFile("src/" . $this->project->namespace . "/cmds/" . $cmd->getClassName() . ".php", $cmd->generateFile());
		}
		if(isset($include)){
			$this->includePhpResource("GeneratedPluginCommandAbstract");
		}
	}
	private function generateEvents(){
		$class = new ClassGenerator($this->project->namespace, "EventHandler");
		$class->addImport("pocketmine\\event\\Listener");
		$class->addInterface("Listener");
		$constructor = new GeneratedFunctionContainer;
		$constructor->name = "__construct";
		$constructor->params = ['MainClass $main'];
		$constructor->code = '$this->main = $main;';
		$class->addFunction($constructor);
		$class->addField(\T_PRIVATE, "main");
		$i = 0;
		foreach($this->project->events as $event){
			$fx = new GeneratedFunctionContainer;
			$fx->name = "h_$i";
			$i++;
			$class->addImport($event->eventClassName);
			$fx->params = [$event->eventName . ' $event'];
			foreach($event->eventHandler as $stmt){
				$fx->code .= $stmt->getPhpCode(0);
				$fx->code .= ClassGenerator::STANDARD_EOL;
			}
			$class->addFunction($fx);
		}
	}
	private function addFile($filename, $contents){
		if($contents instanceof ClassGenerator){
			$contents = $contents->toString();
		}
		$this->files["/" . trim(str_replace("\\", "/", $filename), "/")] = $contents;
	}
	private function includePhpResource($className){
		$namespace = $this->project->namespace . "\\resources";
		$dir = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR;
		if(is_file($filename = $dir . $className . ".php")){
			$contents = file_get_contents($filename);
			$contents = str_replace([
				0 => "___PLUGIN_GENERATOR_VARIABLE_main_namespace___",
			], [
				0 => $namespace,
			], $contents);
			$this->addFile("src/$namespace/$className.php", $contents);
		}else{
			throw new \InvalidArgumentException("Resource $filename not found");
		}
	}
}
