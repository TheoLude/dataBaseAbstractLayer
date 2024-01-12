<?php

/**
*Classe Postgre pour attaquer PostgreSQL avec la classe dataBaseAbstractLayer
*Elle crée le lien entre la classe d'abstraction et l'API réelle de PostgreSQL
*
*1.0 - [Theo] - 28 mai 2002
*1.1 - [Theo] - 29 mai 2002 - Modification de escape_value
*							- Modification de unescape_value
*                           - Ajout de la gestion du message supplémentaire dans la méthode query
*
*
* @package caliObject
* @subpackage caliSQLObject
*
*/

namespace eXtensia\dataBaseAbstractLayer;

class Postgre extends \eXtensia\errorManager\errorManager {

	var $db_connexion = '';
	var $db_ip = '';
	var $db_name = '';
	var $db_login = '';
	var $db_password = '';
	var $db_id_requete = '';
	var $db_fetch_index = 0;
	var $db_ges_erreur = false;
	var $bool_dbal_erreur = false;


	/**
	* Constructeur de la classe Postgre
	* Il construit un nouvel objet Postgre
	*
	* @param
	*
	* @return
	*/
	function Postgre($str_ip, $str_login, $str_password, $str_name, $bool_ges_erreur = false){
		$this -> errorManager();

		$this -> db_login = $str_login;
		$this -> db_password = $str_password;
		$this -> db_ip = $str_ip;
		$this -> db_name = $str_name;
		$this -> db_ges_erreur = $bool_ges_erreur;

		if (! function_exists('pg_connect')) {
			if (! $bool_ges_erreur) $this -> ErrorTracker(5, 'La librairie Postgre n\'est pas disponible sur ce serveur.');
			else $parent -> bool_dbal_erreur = true;
		}
	}

	/**
	*
	* @access private
	*
	*/
	function db_connect(){
		if (! $db = @pg_pconnect('host='.$this -> db_ip.' dbname='.$this -> db_name.' user='.$this -> db_login.' password='.$this -> db_password)) {
			if (! $this -> db_ges_erreur) $this -> ErrorTracker(5, 'Connexion au serveur Postgre impossible - Message Postgre : <span style="color:red">'.pg_errorMessage().'</span>.');
			else return false;
		}
		$this -> db_connexion = $db;
		return $db;
	}

	/**
	* @access private
	* Permet d'éxecuter une requête sur la base de donnée de type $db_type, elle crée alors un identifiant sur la requete qui vient
	* d'être effectuée.
	*
	* @param $str_query string - Chaine contenant la requête à effectuer
	*
	* @return void
	*/
	function db_query($str_query, $str_message = ''){
		if ($str_message != '') $str_message = 'Message de service :'.$str_message.'<br />';
		if (! $id = @pg_exec($this -> db_connexion, $str_query))  $this -> ErrorTracker(5, $str_message.'Erreur sur la requête : <b>'.$str_query.'</b><br /> - Message Postgre : <span style="color:red">'.pg_errorMessage().'</span>.');
		$this -> db_id_requete = $id;

		return $id;
	}

	/**
	* @access private
	* Renvoie un enregsitrement sur la requête en cours
	*
	* @param void
	*
	* @return array - L'enregistrement en cours de lecture (tableau associatif)
	*/
	function db_fetch(){
		return @pg_fetch_array($this -> db_id_requete, $this -> db_fetch_index ++);
	}

	/**
	*
	* @access private
	*
	*/
	function db_row(){
		return @pg_fetch_row($this -> db_id_requete, $this -> db_fetch_index ++);
	}

	/**
	*
	* @access private
	*
	*/
	function db_escape_value($str_value){
		if (is_string($str_value) && ! get_magic_quotes_gpc()) return $str_value = addSlashes($str_value);
		else return $str_value;
	}

	/**
	*
	* @access private
	*
	*/
	function db_unescape_value($str_value){
		return stripslashes($str_value); /// A Voir pour Postgre
	}

	/**
	*
	* @access private
	*
	*/
	function db_seek($int_row_number){
		if (! $int_row_number) return false;
		if (! pg_result_seek($this -> db_id_requete, $int_row_number)) return false;
		return true;
	}

	/**
	* @access private
	* Renvoie le nombre d'enregistrement affectés par la dernière requête réalisée
	*
	* @param void
	*
	* @return $int_num integer - Le nombre d'enregistrements concernés par la dernière requête
	*/
	function db_num_rows(){
		return @pg_numrows($this -> db_id_requete);
	}
}

?>