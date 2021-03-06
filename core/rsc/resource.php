<?php

$app->get(    '/resource/:resource/',             		 			   '_resource_list');         	  	  	// affiche une liste de ressource du depot
$app->get(    '/resource/:resource/o/:p_index_first/:p_index_last',    '_resource_list_offset');         	// affiche une liste de ressource du depot
$app->get(    '/resource/:resource/key/:key/',       	 			   '_resource_list_by_key');            // affiche une liste de ressource sur le depot via une clé utilisateur
$app->get(    '/resource/:resource/id/:id/',             			   '_resource_view');          		    // affiche une resource sur le depot
$app->get(    '/resource/:resource/history/id/:id',             	   '_resource_history_view');           // affiche une resource sur le depot AVEC l'historique d'édition
$app->get(    '/resource/:resource/search/:search/:value',             '_resource_list_by_search');         // affiche une liste de resource via une recherche sur le depot
$app->post(   '/resource/:resource/',             		 			   '_resource_add');         	  	  	// ajouter une ressource dans le depot
$app->put(    '/resource/:resource/id/:id',             		 	   '_resource_edit');         	  	  	// editer une ressource dans le depot
$app->delete( '/resource/:resource/id/:id',             		 	   '_resource_delete');         	  	// supprimer une ressource dans le depot


function _resource_list($resource){
	_resource_list_offset($resource, '1', '20');
}

function _resource_list_offset($resource, $p_index_first, $p_index_last){
	$app = \Slim\Slim::getInstance();
	$depot_array = parse_ini_file('depot/depot.ini', true);
	if($depot_array['DEPOT']['local'] == "") $app->response->redirect($app->urlFor('install'), 303);
	// initialisation des variables et fonctions
	$system = new System();
	$i = 0;
	$more = false;
	// Analyse avec sections de depot.ini .
	$ini_array = parse_ini_file('depot/depot.ini', true);
	if($p_index_last-$p_index_first >20) $app->halt(416); // Si l'écart dans offset est plus grand que 20 alors on fait un 416
	// On verifie si le dossier/ressource existe puis on affiche les informations
	if(file_exists("depot/$resource")){
		if($dir = opendir("depot/$resource")){
			$list_rsc = glob("depot/$resource/*.json"); // On va chercher toutes les ressources dans le fichier ressource désigné par $ressource
			
			// on trie la liste de ressource par date plus récente
			usort($list_rsc, function($a, $b) {
			    return filemtime($a) < filemtime($b);
			});
			$list_rsc_total1 = $list_rsc;
			
				$list_rsc = str_replace("depot/$resource/", "", $list_rsc);
				$list_rsc = str_replace(".json", "", $list_rsc);
			
			$list_rsc_total1 = $list_rsc;
			$list_rsc_total = $list_rsc;

			foreach ($list_rsc as $key) { // On ne garde que les offset demandé
				if($key < $p_index_first or $key > $p_index_last){
					unset($list_rsc[$key]);
					$more = true;
				} 
			}

			$i = 0;
			foreach($list_rsc as $key => $value) { // on  utilise les list d'id de ressource pour contacter l'api et lister les détails de chaque ressource
				$depot = $ini_array['DEPOT']['local'];
				$id = $value;
				if($system->_get_http_response_code($depot.'resource/'.$resource.'/id/'.$id) != "404"){
					$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
				    $result1 = file_get_contents($depot.'resource/'.$resource.'/id/'.$id,false,$context);
				    $result1 = json_decode($result1, true);
			    	$result[$i] = $result1;
			    	$i++;
				}
				
			}
		}
		closedir($dir);
		if($more == true){
			$app = \Slim\Slim::getInstance();
			$app->response->headers->set('Content-Range', "$p_index_first - $p_index_last / ".count($list_rsc_total));
			$app->response->headers->set('Accept-Range', "$resource 20");
		    
		    if(isset($result)) $app->halt(206, $system->_filter_json(json_encode($result))); // Envoi de la réponse
			else {
				$app = \Slim\Slim::getInstance();
			    $app->halt(404);
			}
		}else{
			if(isset($result)) echo $system->_filter_json(json_encode($result)); // Envoi de la réponse
			else {
				$app = \Slim\Slim::getInstance();
			    $app->halt(404);
			}
		}
		

	}
	else {
	    $app = \Slim\Slim::getInstance();
	    $app->halt(404);
	}
	exit(0);

}


function _resource_list_by_key($resource, $key){
	$app = \Slim\Slim::getInstance();
	$depot_array = parse_ini_file('depot/depot.ini', true);
	if($depot_array['DEPOT']['local'] == "") $app->response->redirect($app->urlFor('install'), 303);
	// initialisation des variables et fonctions
	$system = new System();
	$i = 0;

	// Analyse avec sections de depot.ini .
	$ini_array = parse_ini_file('depot/depot.ini', true);

	// On verifie si le dossier/ressource existe puis on affiche les informations
	if(file_exists("depot/$resource")){
		if($dir = opendir("depot/$resource")){
			$list_rsc = glob("depot/$resource/*.json"); // On va chercher toutes les ressources dans le fichier ressource désigné par $ressource
			// on trie la liste de ressource par date plus récente
			usort($list_rsc, function($a, $b) {
			    return filemtime($a) < filemtime($b);
			});
			foreach($list_rsc as &$value) { // On ne garde que l'id de la ressource.
				$value = str_replace("depot/$resource/", "", $value);
				$value = str_replace(".json", "", $value);
			}
			foreach($list_rsc as &$value) { // on  utilise les list d'id de ressource pour contacter l'api et lister les détails de chaque ressource
				$depot = $ini_array['DEPOT']['local'];
				$id = $value;
				if($system->_get_http_response_code($depot.'resource/'.$resource.'/id/'.$id) != "404"){
					$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
				    $result1 = file_get_contents($depot.'resource/'.$resource.'/id/'.$id,false,$context);
				    $result1 = json_decode($result1, true);
				    if($result1["_api_key_user"] == $key){
				    	$result[$i] = $result1;
				    	$i++;
				    }
				}
			}
		}
		closedir($dir);
		if(isset($result)) echo $system->_filter_json(json_encode($result)); // Envoi de la réponse
		else {
			$app = \Slim\Slim::getInstance();
		    $app->halt(404);
		}
	}
	else {
	    $app = \Slim\Slim::getInstance();
	    $app->halt(404);
	}
	exit(0);

}

function _resource_view($resource, $id){
	$app = \Slim\Slim::getInstance();
	$depot_array = parse_ini_file('depot/depot.ini', true);
	if($depot_array['DEPOT']['local'] == "") $app->response->redirect($app->urlFor('install'), 303);
	$ini_array = parse_ini_file('depot/depot.ini', true);
	if(file_exists('depot/'.$resource.'/'.$id.'.json')){

		// Traitement resource
		$system = new System();
		$json = file_get_contents("depot/$resource/$id.json");
		$result1 = $system->_wiki(json_decode($json, true));

		// Identifiant resource
		$result['_api_rsc']['_name'] = $resource;
		$result['_api_rsc']['_id'] = $id;
		$result['_api_rsc']['_depot'] = $ini_array['DEPOT']['local'];
		// $result['_api_rsc']['_edited_on'] = date ("m/d/Y H:i:s", filemtime("depot/$resource/$id.json"));

		// Lien api
		$result['_api_link']['_view']['_href'] = $ini_array['DEPOT']['local'].'resource/'.$resource.'/id/'.$result['_api_rsc']['_id'];
		$result['_api_link']['_view']['_method'] = 'GET';
		$result['_api_link']['_view_history']['_href'] = $ini_array['DEPOT']['local'].'resource/'.$resource.'/history/id/'.$result['_api_rsc']['_id'];
		$result['_api_link']['_view_history']['_method'] = 'GET';
		$result['_api_link']['_edit']['_href'] = $ini_array['DEPOT']['local'].'resource/'.$resource.'/id/'.$result['_api_rsc']['_id'];
		$result['_api_link']['_edit']['_method'] = 'PUT';
		if($result1['_api_key_user'] <> "false") $result['_api_link']['_edit']['_require'] = 'authentication';
		$result['_api_link']['_delete']['_href'] = $ini_array['DEPOT']['local'].'resource/'.$resource.'/id/'.$result['_api_rsc']['_id'];
		$result['_api_link']['_delete']['_method'] = 'DELETE';
		if($result1['_api_key_user'] <> "false") $result['_api_link']['_delete']['_require'] = 'authentication';

		$count = count($result1['_api_data']);
		$data = $result1['_api_data'][$count];
		unset($result1['_api_data']);
		//suppression _api_key_password
		unset($result1['_api_key_password']);
		$result1['_api_data'] = $data;

		// fusion et affichage
		$result = array_merge($result1, $result);
		echo $system->_filter_json(json_encode($result)); // Envoi de la réponse
	} else {
	    $app = \Slim\Slim::getInstance();
	    $app->halt(404);
	}
	exit(0);

	
}

function _resource_history_view($resource, $id){
	$app = \Slim\Slim::getInstance();
	$depot_array = parse_ini_file('depot/depot.ini', true);
	if($depot_array['DEPOT']['local'] == "") $app->response->redirect($app->urlFor('install'), 303);
	if($resource == "attachment") _attachment_view($id);
	else{
		$ini_array = parse_ini_file('depot/depot.ini', true);
		if(file_exists('depot/'.$resource.'/'.$id.'.json')){

			// Traitement resource
			$system = new System();
			$json = file_get_contents("depot/$resource/$id.json");
			$result1 = $system->_wiki(json_decode($json, true));

			// Identifiant resource
			$result['_api_rsc']['_name'] = $resource;
			$result['_api_rsc']['_id'] = $id;
			$result['_api_rsc']['_depot'] = $ini_array['DEPOT']['local'];
			// $result['_api_rsc']['_edited_on'] = date ("m/d/Y H:i:s", filemtime("depot/$resource/$id.json"));

			// Lien api
			$result['_api_link']['_view']['_href'] = $ini_array['DEPOT']['local'].'resource/'.$resource.'/id/'.$result['_api_rsc']['_id'];
			$result['_api_link']['_view']['_method'] = 'GET';
			$result['_api_link']['_view_history']['_href'] = $ini_array['DEPOT']['local'].'resource/'.$resource.'/history/id/'.$result['_api_rsc']['_id'];
			$result['_api_link']['_view_history']['_method'] = 'GET';
			$result['_api_link']['_edit']['_href'] = $ini_array['DEPOT']['local'].'resource/'.$resource.'/id/'.$result['_api_rsc']['_id'];
			$result['_api_link']['_edit']['_method'] = 'PUT';
			if($result1['_api_key_user'] <> "false") $result['_api_link']['_edit']['_require'] = '_api_key_password';
			$result['_api_link']['_delete']['_href'] = $ini_array['DEPOT']['local'].'resource/'.$resource.'/id/'.$result['_api_rsc']['_id'];
			$result['_api_link']['_delete']['_method'] = 'DELETE';
			if($result1['_api_key_user'] <> "false") $result['_api_link']['_delete']['_require'] = '_api_key_password';

			//suppression _api_key_password
			unset($result1['_api_key_password']);

			// fusion et affichage
			$result = array_merge($result1, $result);
			echo $system->_filter_json(json_encode($result)); // Envoi de la réponse
		} else {
		    $app = \Slim\Slim::getInstance();
		    $app->halt(404);
		}
		exit(0);
	}
	
}

function _resource_list_by_search($resource, $search, $value){
	$app = \Slim\Slim::getInstance();
	$depot_array = parse_ini_file('depot/depot.ini', true);
	if($depot_array['DEPOT']['local'] == "") $app->response->redirect($app->urlFor('install'), 303);
	// initialisation des variables et fonctions
	$value_search = $value;
	$system = new System();
	$i = 0;

	// Analyse avec sections de depot.ini .
	$ini_array = parse_ini_file('depot/depot.ini', true);

	// On verifie si le dossier/ressource existe puis on affiche les informations
	if(file_exists("depot/$resource")){
		
		$list_rsc = glob("depot/$resource/*.json"); // On va chercher toutes les ressources dans le fichier ressource désigné par $ressource
		// on trie la liste de ressource par date plus récente
		usort($list_rsc, function($a, $b) {
		    return filemtime($a) < filemtime($b);
		});
		foreach($list_rsc as &$value) { // On ne garde que l'id de la ressource.
			$value = str_replace("depot/$resource/", "", $value);
			$value = str_replace(".json", "", $value);
		}
		foreach($list_rsc as &$value) { // on  utilise les list d'id de ressource pour contacter l'api et lister les détails de chaque ressource
			$depot = $ini_array['DEPOT']['local'];
			$id = $value;
			if($system->_get_http_response_code($depot.'resource/'.$resource.'/id/'.$id) != "404"){
				$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
			    $result1 = file_get_contents($depot.'resource/'.$resource.'/id/'.$id,false,$context);
			    $result1 = json_decode($result1, true);
			    $count = count($result1['_api_data']);
			    $find = false;
			    // on trie par rapport à search/value
			    if(isset($result1[$search]) and $result1[$search] == $value_search and $find == false){
			    	$result[$i] = $result1;
			    	$i++;
			    	$find = true;
			    }
			    if(isset($result1[$search]) and $result1[$search] == strtoupper($value_search) and $find == false){
			    	$result[$i] = $result1;
			    	$i++;
			    	$find = true;
			    }
			    if(isset($result1[$search]) and $result1[$search] == strtolower($value_search) and $find == false){
			    	$result[$i] = $result1;
			    	$i++;
			    	$find = true;
			    }
			    if(isset($result1[$search]) and $result1[$search] == ucfirst($value_search) and $find == false){
			    	$result[$i] = $result1;
			    	$i++;
			    	$find = true;
			    }
			    if(isset($result1['_api_data'][$search]) and $result1['_api_data'][$search] == $value_search and $find == false){
			    	$result[$i] = $result1;
			    	$i++;
			    	$find = true;
			    }
			    if(isset($result1['_api_data'][$search]) and $result1['_api_data'][$search] == strtoupper($value_search) and $find == false){
			    	$result[$i] = $result1;
			    	$i++;
			    	$find = true;
			    }
			    if(isset($result1['_api_data'][$search]) and $result1['_api_data'][$search] == strtolower($value_search) and $find == false){
			    	$result[$i] = $result1;
			    	$i++;
			    	$find = true;
			    }
			    if(isset($result1['_api_data'][$search]) and $result1['_api_data'][$search] == ucfirst($value_search) and $find == false){
			    	$result[$i] = $result1;
			    	$i++;
			    	$find = true;
			    }
			}
		}
		if(isset($result)) echo $system->_filter_json(json_encode($result)); // Envoi de la réponse
		else {
			$app = \Slim\Slim::getInstance();
		    $app->halt(404);
		}
	}
	else {
	    $app = \Slim\Slim::getInstance();
	    $app->halt(404);
	}
	exit(0);

}

function _resource_add($resource){

	$app = \Slim\Slim::getInstance();
	$data = $app->request()->post();
	$depot_array = parse_ini_file('depot/depot.ini', true);
	// initialisation des variables et fonctions
	$system = new System();
	$id = uniqid('',true);
	// On verifie si le dossier/ressource existe puis on affiche les informations
	if(file_exists("depot/$resource")){
		$data = $app->request()->getBody();
		$data = json_decode($data, true);
		if(empty($data['_api_key_user']) or empty($data['_api_key_password']) or empty($data['_api_data'])){
			$app = \Slim\Slim::getInstance();
	    	$app->halt(400, json_encode($data));
	    	exit(0);
		}
		if($depot_array['OPTION']['open'] == "0"){
			$access_array = parse_ini_file('depot/access.ini', true);
			if(isset($data['_api_key_access'])){
				if(!isset($access_array['ACCESS'][$data['_api_key_user']]) or $access_array['ACCESS'][$data['_api_key_user']] <> $data['_api_key_access']){
					$app = \Slim\Slim::getInstance();
			    	$app->halt(401);
			    	exit(0);
				}
				else{
					unset($data['_api_key_access']);
				}
			}
			else{
				$app = \Slim\Slim::getInstance();
		    	$app->halt(401);
		    	exit(0);
			}
		}
		$data_cache = $data['_api_data'];
		unset($data['_api_data']);
		$data['_api_data']['1'] = $data_cache;
		$data['_api_data']['1']['_edited_on'] = date("m/d/Y H:i:s");
		$data['_api_data']['1']['_edited_by'] = $data['_api_key_user'];
		
		$data = $system->_filter_json_post(json_encode($data));
		$system->_write_json_file($data, "depot/$resource/$id.json");
		_resource_view($resource,$id);
	}
	else {
	    $app = \Slim\Slim::getInstance();
	    $app->halt(404);
	}
	exit(0);

}

function _resource_edit($resource, $id){

	$app = \Slim\Slim::getInstance();
	$data = $app->request()->post();
	$depot_array = parse_ini_file('depot/depot.ini', true);
	// initialisation des variables et fonctions
	$system = new System();
	// On verifie si le dossier/ressource existe puis on affiche les informations
	if(file_exists("depot/$resource")){
		$data = $app->request()->getBody();
		$data = json_decode($data, true);
		$data_depot = json_decode(file_get_contents("depot/$resource/$id.json"), true);
		if($data_depot['_api_key_user'] <> ""){
			if(empty($data['_api_key_user']) or empty($data['_api_key_password']) or empty($data['_api_data'])){
				$app = \Slim\Slim::getInstance();
		    	$app->halt(400);
		    	exit(0);
			}
		}
		if($depot_array['OPTION']['open'] == "0"){
			$access_array = parse_ini_file('depot/access.ini', true);
			if(isset($data['_api_key_access'])){
				if(!isset($access_array['ACCESS'][$data['_api_key_user']]) or $access_array['ACCESS'][$data['_api_key_user']] <> $data['_api_key_access']){
					$app = \Slim\Slim::getInstance();
			    	$app->halt(401);
			    	exit(0);
				}
				else{
					unset($data['_api_key_access']);
				}
			}
			else{
				$app = \Slim\Slim::getInstance();
		    	$app->halt(401);
		    	exit(0);
			}
		}
		if((("".$data['_api_key_user']."" == "".$data_depot['_api_key_user']."") and ("".$data['_api_key_password']."" == "".$data_depot['_api_key_password']."") or $data_depot['_api_key_user'] == "")){
			if(isset($data['_api_wiki']) and $data['_api_wiki'] == 1) {
				$data_depot['_api_key_user'] = "";
				$data_depot['_api_key_password'] = "";
			}
			$data_cache = $data['_api_data'];
			$i = count($data_depot['_api_data']);
			$data_depot['_api_data'][$i+1] = $data_cache;
			$data_depot['_api_data'][$i+1]['_edited_on'] = date("m/d/Y H:i:s");
			$data_depot['_api_data'][$i+1]['_edited_by'] = $data['_api_key_user'];
			$data_depot = $system->_filter_json_post(json_encode($data_depot));
			unlink("depot/$resource/$id.json");
			$system->_write_json_file($data_depot, "depot/$resource/$id.json");
			_resource_view($resource,$id);
		}
		else{
			$app = \Slim\Slim::getInstance();
	    	$app->halt(401);
	    	exit(0);
		}
	}
	else {
	    $app = \Slim\Slim::getInstance();
	    $app->halt(404);
	}
	exit(0);

}

function _resource_delete($resource, $id){

	$app = \Slim\Slim::getInstance();
	$depot_array = parse_ini_file('depot/depot.ini', true);
	
	if(file_exists('depot/'.$resource.'/'.$id.'.json')){
		$data = $app->request()->getBody();
		$data = json_decode($data, true);
		$data_depot = json_decode(file_get_contents("depot/$resource/$id.json"), true);
		if(empty($data['_api_key_user']) or empty($data['_api_key_password'])){
			$app = \Slim\Slim::getInstance();
	    	$app->halt(400);
	    	exit(0);
		}
		if($depot_array['OPTION']['open'] == "0"){
			$access_array = parse_ini_file('depot/access.ini', true);
			if(isset($data['_api_key_access'])){
				if(!isset($access_array['ACCESS'][$data['_api_key_user']]) or $access_array['ACCESS'][$data['_api_key_user']] <> $data['_api_key_access']){
					$app = \Slim\Slim::getInstance();
			    	$app->halt(401);
			    	exit(0);
				}
				else{
					unset($data['_api_key_access']);
				}
			}
			else{
				$app = \Slim\Slim::getInstance();
		    	$app->halt(401);
		    	exit(0);
			}
		}
		if(("".$data['_api_key_user']."" == "".$data_depot['_api_key_user']."") and ("".$data['_api_key_password']."" == "".$data_depot['_api_key_password']."")){
			unlink("depot/$resource/$id.json");
		}
		else{
			$app = \Slim\Slim::getInstance();
	    	$app->halt(401);
	    	exit(0);
		}
		
	}
	else {
	    $app = \Slim\Slim::getInstance();
	    $app->halt(404);
	}
	exit(0);

}