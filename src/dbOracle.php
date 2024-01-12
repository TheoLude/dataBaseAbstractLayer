<?php

/**
*Classe Oracle pour attaquer Oracle avec la classe dataBaseAbstractLayer
*Elle crée le lien entre la classe d'abstraction et l'API réelle d'Oracle
*
*1.0 - [Theo] - 28 mai 2002
*
* @package caliObject
* @subpackage caliSQLObject
*
*/

namespace eXtensia\dataBaseAbstractLayer;

class Oracle extends \eXtensia\errorManager\errorManager {

	var $db_ges_erreur = false;
	var $db_connexion = '';
	var $db_ip = '';
	var $db_name = '';
	var $db_login = '';
	var $db_password = '';
	var $db_id_requete = '';
	var $bool_dbal_erreur = false;

	/**
	*Constructeur de la classe MsSQL
	*Il construit un nouvel objet MsSQL
	*
	*@param
	*
	*@return
	*/
	function Oracle($str_ip, $str_login, $str_password, $str_name, $bool_ges_erreur = false){
		$this -> errorManager();

		$this -> db_login = $str_login;
		$this -> db_password = $str_password;
		$this -> db_ip = $str_ip;
		$this -> db_name = $str_name;
		$this -> db_ges_erreur = $bool_ges_erreur;
	}

	function db_connect(){
		return $db;
	}
}
?>