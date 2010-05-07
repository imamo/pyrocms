<?php

class Module_import {

	private $ci;

	function Module_import()
	{
		$this->ci =& get_instance();

	}


	private function add($module)
    {
		return $this->ci->db->insert('modules', array(
    		'name'				=>	$module['name'],
    		'slug'				=>	$module['slug'],
    		'version' 			=> 	$module['version'],
    		'type' 				=> 	$module['type'],
    		'description' 		=> 	$module['description'],
    		'skip_xss'			=>	$module['skip_xss'],
    		'is_frontend'		=>	$module['is_frontend'],
    		'is_backend'		=>	$module['is_backend'],
    		'is_backend_menu' 	=>	$module['is_backend_menu'],
    		'controllers'		=>	serialize($module['controllers']),
			'enabled'			=>  $module['enabled'],
			'is_core'			=>  $module['is_core']
    	));
    }
	
	private function _format_xml($xml_file)
    {
    	$xml = simplexml_load_file($xml_file);

    	// Loop through all controllers in the XML file
    	$controllers = array();

    	foreach($xml->controllers as $controller)
    	{
    		$controller = $controller->controller;
    		$controller_array['name'] = (string) $controller->attributes()->name;

    		// Store methods from the controller
    		$controller_array['methods'] = array();

    		if($controller->method)
    		{
    			// Loop through to save methods
    			foreach($controller->method as $method)
    			{
    				$controller_array['methods'][] = (string) $method;
    			}
    		}

			// Save it all to one variable
    		$controllers[$controller_array['name']] = $controller_array;
    	}

    	return array(
    		'name'				=>	(string) $xml->name->{constant('CURRENT_LANGUAGE')},
    		'version' 			=> 	(string) $xml->attributes()->version,
    		'type' 				=> 	(string) $xml->attributes()->type,
    		'description' 		=> 	(string) $xml->description->{constant('CURRENT_LANGUAGE')},
    		'skip_xss'			=>	$xml->skip_xss == 1,
    		'is_frontend'		=>	$xml->is_frontend == 1,
    		'is_backend'		=>	$xml->is_backend == 1,
    		'is_backend_menu' 	=>	$xml->is_backend_menu == 1,
    		'controllers'		=>	$controllers
    	);
    }


	public function _import()
    {
    	$modules = array();

    	// Loop through directories that hold modules
    	foreach (array(APPPATH.'modules/', 'third_party/modules/') as $directory)
    	{
    		// Loop through modules
	        foreach(glob($directory.'*', GLOB_ONLYDIR) as $module_name)
	        {
	        	if(file_exists($xml_file = $module_name.'/details.xml'))
	        	{
	        		$module = $this->_format_xml($xml_file) + array('slug'=>basename($module_name));

	        		$module['is_core'] = basename(dirname($directory)) != 'third_party';

	        		// If we only want frontend modules, check its frontend
		        	if(!empty($params['is_frontend']) && empty($module['is_frontend'])) continue;

		        	// Looking for backend modules
		        	if(!empty($params['is_backend']))
		        	{
		        		// This module is not a backend module
		        		if(empty($module['is_backend'])) continue;

		        		// This user has no permissions for this module
		        		if(!$this->permissions_m->has_admin_access( $this->user->group_id, $module['slug']) ) continue;
		        	}

	        		// If we only want frontend modules, check its frontend
		        	if(isset($params['is_core']) && $module['is_core'] != $params['is_core']) continue;

		        	// Check a module is intended for the sidebar
					if(isset($params['is_backend_menu']) && $module['is_backend_menu'] != $params['is_backend_menu']) continue;
					$module['enabled'] = 1;

	        		echo 'Importing ' . $module['name'] . '<br />';
					
					$this->add($module);
	        	}
	        }
        }
		echo "Done";
    }

}
?>