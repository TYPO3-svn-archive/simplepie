<?php
public static function registerPlugin (
	$extensionName, $pluginName, $pluginTitle );
	
public static function configurePlugin (
	$extensionName,
	$pluginName,
	array $controllerActions,
	array $nonCachableControllerActions = array() );
?>