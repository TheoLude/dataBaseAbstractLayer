<?php

/**
*Classe Sybase pour attaquer Sybase et SQL server avec la classe dataBaseAbstractLayer
*Elle cr�e le lien entre la classe d'abstraction et l'API r�elle de Sybase ou SQL server
*Si le serveur Web tourner sous UNIX et qu'il doit attaquer SQLServer cette classe doit �tre
*utilis�e en lieu et place de dbMssql. Attention � bien v�rifier que les drivers Sybase sont bien
*install�s sur le serveur Web sous UNIX.
*
*1.0 - [Theo] - 28 mai 2002
*1.1 - [Theo] - 29 mai 2002 - Modification de escape_value
*							- Modification de unescape_valu
*                           - Ajout de la gestion du message suppl�mentaire dans la m�thode query
*
* @package caliObject
* @subpackage caliSQLObject
*
*/

namespace eXtensia\dataBaseAbstractLayer;

class Sybase extends errorManager {

	var $db_connexion = '';
	var $db_ip = '';
	var $db_name = '';
	var $db_login = '';
	var $db_password = '';
	var $db_id_requete = '';
	var $db_ges_erreur = false;
	var $bool_dbal_erreur = false;

	/**
	*Constructeur de la classe Sybase
	*Il construit un nouvel objet Sybase
	*
	*@param
	*
	*@return
	*/
	function Sybase($str_ip, $str_login, $str_password, $str_name, $bool_ges_erreur = false){
		$this -> errorManager();

		$this -> db_login = $str_login;
		$this -> db_password = $str_password;
		$this -> db_ip = $str_ip;
		$this -> db_name = $str_name;
		$this -> db_ges_erreur = $bool_ges_erreur;

		if (! function_exists('sybase_pconnect')) {
			if (! $bool_ges_erreur) $this -> ErrorTracker(5, 'La librairie Sybase n\'est pas disponible sur ce serveur.');
			else $parent -> bool_dbal_erreur = true;
		}
	}

	/**
	* Fonction de connexion � la base de donn�e
	*
	* @access private
	*
	*
	*/
	function db_connect(){
		if (! $db = @sybase_pconnect($this -> db_ip, $this -> db_login, $this -> db_password)) {
			if (! $this -> db_ges_erreur) $this -> ErrorTracker(5, 'Connexion au serveur Sybase / SQL server impossible - Message Sybase / SQL server : <span style="color:red">'.sybase_get_last_message().'</span>.');
			else return false;
		}
		if (! $sel = @sybase_select_db($this -> db_name, $db)) {
			if (! $this -> db_ges_erreur) $this -> ErrorTracker(5, 'Connexion au serveur Sybase / SQL server impossible - Message Sybase / SQL server : <span style="color:red">'.sybase_get_last_message().'</span>.');
			else return false;
		}
		$this -> db_connexion = $db;
		return $db;
	}

	/**
	* @access private
	* Permet d'�xecuter une requ�te sur la base de donn�e de type $db_type, elle cr�e alors un identifiant sur la requete qui vient
	* d'�tre effectu�e.
	*
	* @param string $str_query - Chaine contenant la requ�te � effectuer
	*
	* @return void
	*/
	function db_query($str_query, $str_message = ''){
		if ($str_message != '') $str_message = 'Message de service :'.$str_message.'<br />';
		if (! $id = @sybase_query($str_query, $this -> db_connexion))  $this -> ErrorTracker(5, $str_message.'Erreur sur la requ�te : <b>'.$str_query.'</b><br /> - Message Sybase / SQL server : <span style="color:red">'.sybase_get_last_message().'</span>.');
		$this -> db_id_requete = $id;

		return $id;
	}

	/**
	* @access private
	* Renvoie un enregsitrement sur la requ�te en cours
	*
	* @param void
	*
	* @return array - L'enregistrement en cours de lecture (tableau associatif)
	*/
	function db_fetch(){
		return @sybase_fetch_array($this -> db_id_requete);
	}

	/**
	*
	*
	* @access private
	*/
	function db_row(){
		return @sybase_fetch_row($this -> db_id_requete);
	}

	/**
	*
	*
	* @access private
	*/
	function db_escape_value($str_value){
		if (is_string($str_value)) return $str_value = str_replace("'", "''", $str_value);
		else return $str_value;
	}

	/**
	*
	*
	* @access private
	*/
	function db_unescape_value($str_value){
		return str_replace("''", "'", $str_value);
	}

	/**
	*
	*
	* @access private
	*/
	function db_seek($int_row_number){
		if (! $int_row_number) return false;
		if (! sybase_data_seek($this -> db_id_requete, $int_row_number)) return false;
		return true;
	}

	/**
	* @access private
	* Renvoie le nombre d'enregistrement affect�s par la derni�re requ�te r�alis�e
	*
	* @param void
	*
	* @return $int_num integer - Le nombre d'enregistrements concern�s par la derni�re requ�te
	*/
	function db_num_rows(){
		return @sybase_num_rows($this -> db_id_requete);
	}
}

?>