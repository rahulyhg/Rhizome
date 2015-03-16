<?php

$app->get(    '/resolver/depot/',                     '_resolver_depot');              			// affiche tous les depots du resolver
$app->get(    '/resolver/movies/',                    '_resolver_movies_all');         			// affiche tous les films du resolver
$app->get(    '/resolver/peoples/',                   '_resolver_peoples_all');         		// affiche toutes les personnes du resolver




function _resolver_depot(){
	// Analyse avec sections de depot.ini .
	$ini_array = parse_ini_file('depot.ini', true);
	echo json_encode($ini_array['RESOLVER HOST']); // Envoi de la réponse
}

function _resolver_movies_all(){
	// Analyse avec sections de depot.ini .
	$result = [];
	$ini_array = parse_ini_file('depot.ini', true);
	foreach($ini_array['RESOLVER HOST'] as $row) {
		$adress_resolver = $row;
		$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
	    $result1 = file_get_contents($adress_resolver.'movies',false,$context);
	    $result1 = json_decode($result1, true);
	    $result = array_merge($result, $result1);
	}
	echo json_encode($result); // Envoi de la réponse
}

function _resolver_peoples_all(){
	// Analyse avec sections de depot.ini .
	$result = [];
	$ini_array = parse_ini_file('depot.ini', true);
	foreach($ini_array['RESOLVER HOST'] as $row) {
		$adress_resolver = $row;
		$context = stream_context_create(array('http' => array('header'=>'Connection: close\r\n')));
	    $result1 = file_get_contents($adress_resolver.'peoples',false,$context);
	    $result1 = json_decode($result1, true);
	    $result = array_merge($result, $result1);
	}
	echo json_encode($result); // Envoi de la réponse
}