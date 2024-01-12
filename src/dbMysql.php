<?php

/**
*Classe MySQL pour attaquer MySQL avec la classe dataBaseAbstractLayer
*Elle crée le lien entre la classe d'abstraction et l'API réelle de MySQL
*
*1.0 - [Theo] - 28 mai 2002
*
*
* @package caliObject
* @subpackage caliSQLObject
*
*/

namespace eXtensia\dataBaseAbstractLayer;

class Mysql extends \eXtensia\errorManager\errorManager {

	var $db_connexion = '';
	var $db_ip = '';
	var $db_name = '';
	var $db_login = '';
	var $db_password = '';
	var $db_id_requete = '';
	var $db_ges_erreur = false;
	var $bool_dbal_erreur = false;


	/**
	* Constructeur de la classe MySQL
	* Il construit un nouvel objet MySQL
	*
	* @param
	*
	* @return
	*/
	function Mysql($str_ip, $str_login, $str_password, $str_name, $bool_ges_erreur = false){
		$this -> errorManager((($bool_ges_erreur == true)?'off':'on')); //[MOD Théo 21/08/2019] Il faudrait revoir complétement la gestion des erreurs et la classe toute entière ...

		$this -> db_login = $str_login;
		$this -> db_password = $str_password;
		$this -> db_ip = $str_ip;
		$this -> db_name = $str_name;
		$this -> db_ges_erreur = $bool_ges_erreur;
	}

	/**
	*
	*
	* @access private
	*/
	function db_connect(){

		if (! $db = @mysql_connect($this -> db_ip, $this -> db_login, $this -> db_password)) {
			if (! $this -> db_ges_erreur) $this -> ErrorTracker(5, 'Connexion au serveur MySQL impossible - Message MySQL : <span style="color:red">'.mysql_error().'</span>.');
			else return false;
		}
		//[ADD Théo 09/05/2017]
		if (preg_match('/;/', $this -> db_name)){
			$tabDb = explode(';', $this -> db_name);
			foreach($tabDb as $i => $strDb) {
				if (! $sel = @mysql_select_db($strDb, $db)) {
					if (! $this -> db_ges_erreur) $this -> ErrorTracker(5, 'Sélection de la DB '.$db.' impossible - Message MySQL : <span style="color:red">'.mysql_error().'</span>.');
					else return false;
				}
			}
		}
		elseif (! $sel = @mysql_select_db($this -> db_name, $db)) {
			if (! $this -> db_ges_erreur) $this -> ErrorTracker(5, 'Connexion au serveur MySQL impossible - Message MySQL : <span style="color:red">'.mysql_error().'</span>.');
			else return false;
		}

		$this -> ErrorChecker();
		$this -> db_connexion = $db;

		return $db;
	}

	/**
	*
	*
	* @access private
	*/
	function db_close(){
		@mysql_close();
	}

	function db_version_info(){
		return mysql_get_server_info();
	}

	function db_test($str_query){
		if (!@mysql_query($str_query)) return mysql_error();
		return '';
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
		if (! $this -> db_connexion) {
			$this -> ErrorTracker(5, 'Connexion à la base de données non réalisée.<br /> - Message MySQL : <span style="color:red">'.mysql_error().'</span>.');
			return false;
		}


		if ($str_message != '') $str_message = '"'.$str_message.'"<br />';
		if (! $id = @mysql_query($str_query)) $this -> ErrorTracker(5, $str_message.'Erreur sur la requête : <b>'.$str_query.'</b><br /> - Message MySQL : <span style="color:red">'.mysql_error().'</span>.');
		$this -> db_id_requete = $id;

		return $id;
	}

	/**
	* @access private
	*
	* Permet de se positionner sur un enregistrement à la ligne $int_row_number
	*
	* @param integer $int_row_number - Le rang de la ligne sur laquelle on veut se positionner
	* @param array $tab_fields_name - Un tableau contenant la liste des champs des lignes qu'on veut récupérer
	*
	*/
	function db_seek($int_row_number){
		if (! $int_row_number) return false;
		if (! mysql_data_seek($this -> db_id_requete, $int_row_number)) return false;
		return true;
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
		return @mysql_fetch_array($this -> db_id_requete, MYSQL_ASSOC);
	}

	/**
	* @access private
	*
	*/
	function db_row(){
		return @mysql_fetch_row($this -> db_id_requete);
	}

	/**
	* @access private
	*
	*/
	function db_escape_value($str_value){
		if (is_string($str_value) && ! get_magic_quotes_gpc()) return addSlashes($str_value);
		else return $str_value;
	}

	/**
	* @access private
	*
	*/
	function db_unescape_value($str_value){
		return stripslashes($str_value);
	}

	/**
	* @access private
	* Renvoie le dernier id d'un champ autoincrément fait sur cette connexion
	*
	*/
	function db_last_insert_id(){
		return @mysql_insert_id($this -> db_connexion);
	}

	/**
	* @access private
	*
	* Renvoie le nombre de lignes affectées par la dernière requête
	*/
	function db_affected_rows(){
		return @mysql_affected_rows($this -> db_connexion);
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
		return @mysql_num_rows($this -> db_id_requete);
	}

	function db_date_to_db($StrDate, $str_hour){
		if (! $StrDate) return $StrDate;


		switch (constant('DB_DATE_FORMAT')) {
			case 'AAAA-MM-JJ' :
			return $StrDate.(($str_hour)?' '.$str_hour:'');
			break;
			case 'AAAA/MM/JJ' :
			$StrDate = str_replace('-', '/', $StrDate).(($str_hour)?' '.$str_hour:'');
			return $StrDate;
			break;
			case 'JJ/MM/AAAA' :
			return $StrDate{6}.$StrDate{7}.$StrDate{8}.$StrDate{9}.'-'.$StrDate{3}.$StrDate{4}.'-'.$StrDate{0}.$StrDate{1}.(($str_hour)?' '.$str_hour:'');
			break;
		}
	}

	function db_date_from_db($StrDate, $bool_include_hour = false){
		if ($StrDate == '0000-00-00') return '';
		if ($StrDate == '0000-00-00 00:00:00') return '';
		if (! $StrDate) return $StrDate;

		$str_hour = '';
		if ($bool_include_hour){
			list($null, $str_hour) = explode(' ',$StrDate);
		}

		switch (constant('DB_DATE_FORMAT')) {
			case 'AAAA-MM-JJ' :
			return $Strdate.(($bool_include_hour && $str_hour)?' '.$str_hour:'');
			break;
			case 'AAAA/MM/JJ' :
			return $Strdate{0}.$Strdate{1}.$Strdate{2}.$Strdate{3}.'/'.$Strdate{5}.$Strdate{6}.'/'.$Strdate{8}.$Strdate{9}.(($bool_include_hour && $str_hour)?' '.$str_hour:'');
			break;
			case 'JJ/MM/AAAA' :
			return $StrDate{8}.$StrDate{9}.'/'.$StrDate{5}.$StrDate{6}.'/'.$StrDate{0}.$StrDate{1}.$StrDate{2}.$StrDate{3}.(($bool_include_hour && $str_hour)?' '.$str_hour:'');
			break;
			case 'MM/JJ/AAAA' :
			return $StrDate{5}.$StrDate{6}.'/'.$StrDate{8}.$StrDate{9}.'/'.$StrDate{0}.$StrDate{1}.$StrDate{2}.$StrDate{3}.(($bool_include_hour && $str_hour)?' '.$str_hour:'');
			break;
		}
	}



	/**
	* Précise le nom d'une table dont on veut contrôler les entrées avant enregistrement
	*
	*
	* @return integer tableId
	*/
	public function setTableToCheck($strTable){

		$strQuery = 'SHOW FULL FIELDS FROM `'.$strTable.'`;';
		$this -> db_query($strQuery);
		while ($tabRes = $this -> db_fetch()) $tabDataStructure[strtolower($tabRes['Field'])] = $tabRes;

		$intId = count($this -> tabTableId);
		$this -> tabTableId[$intId] = $tabDataStructure;

		return $intId;
	}

	/**
	* Contrôle une valeur avant insertion dans un champ
	*
	*
	* @return boolean
	*/
	public function isValueCompliantWithTableField($intTableId, $strFieldName, $strValue, $boolMandatory = false){

		if (! is_array($this -> tabTableId[$intTableId])) return false;
		if (! isset($this -> tabTableId[$intTableId][strtolower($strFieldName)])) return false;

		if ($boolMandatory && ! isset($strValue)) $this -> strErrorMessage .= "Le champ ".$strFieldName." est un champ obligatoire.";

		switch(strtolower(preg_replace('/ \([0-9]*\)/', '', $this -> tabTableId[$intTableId][strtolower($strFieldName)]['type']))){
			case 'varchar':
			case 'text':
			case 'char':
			case 'tinytext':
			$strValue = strval($strValue);
			break;
			case 'smallint':
			case 'mediumint':
			case 'tinyint':
			case 'bigint':
			if (! preg_match('/^[0-9]{1,}$/', $strValue)) $this -> tabErrorMessage[$intTableId] .= "La valeur ".$strValue." pour le champ ".$strFieldName." n'est pas un entier.";
			else $strValue = intval($strValue);
			break;
			case 'float':
			case 'double':
			case 'decimal':
			if (! preg_match('/^[0-9]{1,}(?:\.[0-9]{1,})+$/', $strValue)) $this -> tabErrorMessage[$intTableId] .= "La valeur ".$strValue." pour le champ ".$strFieldName." n'est pas une valeur décimale.";
			else $strValue = floatval($strValue);
			break;
			case 'date':
			case 'datetime':
			if (! caliCheckDate($strValue)) $this -> tabErrorMessage[$intTableId] .= "La valeur ".$strValue." pour le champ ".$strFieldName." n'est pas une date valide.";
			break;
		}

		return $strValue;

	}

	/**
	* Précise le nom d'une table dont on veut contrôler les entrées avant enregistrement
	*
	*
	* @return boolean
	*/
	public function isTableChecked($intTableId, &$strErrorMessage){

	}
}
?>