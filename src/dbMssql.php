<?php

/**
* Classe MsSQL pour attaquer SQLserver avec la classe dataBaseAbstractLayer
* Elle crée le lien entre la classe d'abstraction et l'API réelle de SQLserver
*
* 1.0 - [Theo] - 28 mai 2002
* 1.1 - [Theo] - 29 mai 2002 - Modification de escape_value
*							- Modification de unescape_value
*                           - Ajout de la gestion du message supplémentaire dans la méthode query
*
*
* @package caliObject
* @subpackage caliSQLObject
*
*/

namespace eXtensia\dataBaseAbstractLayer;

class Mssql extends \eXtensia\errorManager\errorManager {

	var $db_ges_erreur = false;
	var $db_connexion = '';
	var $db_ip = '';
	var $db_name = '';
	var $db_login = '';
	var $db_password = '';
	var $db_id_requete = '';
	var $bool_dbal_erreur = false;


	/**
	* Constructeur de la classe MsSQL
	* Il construit un nouvel objet MsSQL
	*
	* @param
	* @access private
	*
	*/
	function Mssql($str_ip, $str_login, $str_password, $str_name, $bool_ges_erreur = false){
		$this -> errorManager();

		$this -> db_login = $str_login;
		$this -> db_password = $str_password;
		$this -> db_ip = $str_ip;
		$this -> db_name = $str_name;
		$this -> db_ges_erreur = $bool_ges_erreur;

		if (! @function_exists('mssql_connect')) {
			if (! $bool_ges_erreur) $this -> ErrorTracker(5, 'La librairie SQLserver n\'est pas disponible sur ce serveur.');
			//else $parent -> bool_dbal_erreur = true;
		}
	}

	/**
	*
	* @access private
	*
	*/
	function db_connect(){
		if (! $db = @mssql_connect($this -> db_ip, $this -> db_login, $this -> db_password)) {
			if (! $this -> db_ges_erreur) $this -> ErrorTracker(5, 'Connexion au serveur MsSQL impossible - Message MsSQL : <span style="color:red">'.mssql_get_last_message().'</span>.');
			else return false;
		}
		if (! $sel = @mssql_select_db($this -> db_name, $db)) {
			if (! $this -> db_ges_erreur) $this -> ErrorTracker(5, 'Connexion au serveur MsSQL impossible - Message MsSQL : <span style="color:red">'.mssql_get_last_message().'</span>.');
			else return false;
		}
		//date & datetime field :: AA[AA]?M[M]?J[J] for insert, update, delete, where clauses
		mssql_query('SET DATEFORMAT ymd');
		mssql_query('SET CONCAT_NULL_YIELDS_NULL OFF');
		$this -> db_connexion = $db;
		return $db;
	}

	/**
	*
	* @access private
	*
	*/
	function db_close(){
		@mssql_close();
	}

	function db_select_db($str_db){
		return @mssql_select_db($str_db, $this -> db_connexion);
	}

	function db_version_info() {
		$id = @mssql_query('SELECT @@VERSION');
		$tab_info = @mssql_fetch_row($id);
		return $tab_info[0];
	}

	function db_test($str_query){
		if (! $id = @mssql_query($str_query, $this -> db_connexion))  return mssql_get_last_message();
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
		if ($str_message != '') $str_message = 'Message de service :'.$str_message.'<br />';

		if (! $id = @mssql_query($str_query, $this -> db_connexion))  $this -> ErrorTracker(5, $str_message.'Erreur sur la requête : <b>'.$str_query.'</b><br /> - Message MsSQL : <span style="color:red">'.mssql_get_last_message().'</span>.');

		$this -> db_id_requete = $id;

		return $id;
	}

	/**
	* @acces private
	* Renvoie un enregsitrement sur la requête en cours
	*
	* @param void
	*
	* @return array - L'enregistrement en cours de lecture (tableau associatif)
	* libere le résultat si plus de resultat
	*/
	function db_fetch(){
		$tab_local_result = mssql_fetch_array($this -> db_id_requete, MSSQL_ASSOC);

		if($tab_local_result) return $tab_local_result;
		//@mssql_free_result($this -> db_id_requete);
		return false;
	}

	/**
	*
	* @access private
	*
	*/
	function db_row(){
		$tab_local_result = @mssql_fetch_row($this -> db_id_requete);
		if($tab_local_result) return $tab_local_result;
		//@mssql_free_result($this -> db_id_requete);
		return false;
	}

	/**
	*
	* @access private
	*
	*/
	function db_seek($int_row_number){
		if (! $int_row_number) return false;
		if (! mssql_data_seek($this -> db_id_requete, $int_row_number)) return false;
		return true;
	}

	/**
	*
	* @access private
	*
	*/
	function db_escape_value($str_value){
		if (is_string($str_value)) {
			//$str_value = str_replace("'", "''", $str_value);
			return $str_value = str_replace('"', '""', $str_value);
			//return $str_value = str_replace('"', '&quot;', $str_value);
		}
		else return $str_value;
	}

	/**
	*
	* @access private
	*
	*/
	function db_unescape_value($str_value){
		return str_replace('""', '&quot;', $str_value);
	}

	/**
	* @acces private
	* Renvoie le nombre d'enregistrement affectés par la dernière requête réalisée
	*
	* @param void
	*
	* @return $int_num integer - Le nombre d'enregistrements concernés par la dernière requête
	*/
	function db_num_rows(){
		return @mssql_num_rows($this -> db_id_requete);
	}

	function db_date_to_db($StrDate, $str_hour = false){
		if (! $StrDate) return $StrDate;
		/* [DEL Théo 05/10/2010] Remplacé par le paramètre mssql.datetimeconvert à Off dans le php.ini, gestion comme pour MySQL */


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

	/**
	* @access private
	*
	* Renvoie le nombre de lignes affectées par la dernière requête
	*/
	function db_affected_rows(){
		return @mssql_rows_affected($this -> db_connexion);
	}

	function db_date_from_db($StrDate, $bool_include_hour = true){
		if ($StrDate == '0000-00-00') return '';
		if ($StrDate == '0000-00-00 00:00:00') return '';
		if (! $StrDate) return $StrDate;

		//[ADD Théo 12/01/2015]
		if (preg_match('/^[a-z]{3} [0-9]{2} [0-9]{4}/i', $StrDate)){
			$objDate = date_create_from_format('M j Y H:i:s:uA',$StrDate);
			if ($objDate) {
				if ($bool_include_hour) return date_format($objDate, 'd/m/Y H:i:s');
				else return date_format($objDate, 'd/m/Y');
			}
			return '';
		}

		$str_hour = '';
		if ($bool_include_hour){
			list($null, $str_hour) = explode(' ', $StrDate);
		}

		switch (constant('DB_DATE_FORMAT')) {
			case 'AAAA-MM-JJ' :
			return $StrDate.(($bool_include_hour && $str_hour)?' '.$str_hour:'');
			break;
			case 'AAAA/MM/JJ' :
			return $StrDate{0}.$StrDate{1}.$StrDate{2}.$StrDate{3}.'/'.$StrDate{5}.$StrDate{6}.'/'.$StrDate{8}.$StrDate{9}.(($bool_include_hour && $str_hour)?' '.$str_hour:'');
			break;
			case 'JJ/MM/AAAA' :
			return $StrDate{8}.$StrDate{9}.'/'.$StrDate{5}.$StrDate{6}.'/'.$StrDate{0}.$StrDate{1}.$StrDate{2}.$StrDate{3}.(($bool_include_hour && $str_hour)?' '.$str_hour:'');
			break;
			case 'MM/JJ/AAAA' :
			return $StrDate{5}.$StrDate{6}.'/'.$StrDate{8}.$StrDate{9}.'/'.$StrDate{0}.$StrDate{1}.$StrDate{2}.$StrDate{3}.(($bool_include_hour && $str_hour)?' '.$str_hour:'');
			break;
		}

		/* [DEL Théo 05/10/2010] Remplacé par le paramètre mssql.datetimeconvert à Off dans le php.ini, gestion comme pour MySQL

		function db_date_from_db($Str_Date, $bool_include_hour = false){

		if (! $Str_Date) return $Str_Date;

		switch (constant('CST_MSSQL_LANGUAGE')) {
		case 'french':
		$jour = 0;
		$mois = 1;
		$an = 2;
		break;
		case 'us_english':
		$jour = 1;
		$mois = 0;
		$an = 2;
		break;
		default :
		$jour = 0;
		$mois = 1;
		$an = 2;
		break;
		}

		$str_hour = '';
		$tab_data = explode(' ',$Str_Date);

		$tab_data[$jour] = str_pad($tab_data[$jour], 2, '0', STR_PAD_RIGHT);
		if ($tab_data[3]) {
		list($int_hour, $int_min, $int_sec, $str_mix) = explode(':', $tab_data[3]); // Gestion des date time
		if (preg_match('/PM/', $str_mix)) $int_hour += 12;
		$int_msec = preg_replace('/[^0-9]/', '', $str_mix);
		$str_hour = ' '.$int_hour.':'.$int_min.':'.$int_sec;
		}

		// _french janv,févr,mars,avr,mai,juin,juil,août,sept,oct,nov,déc
		// us_english Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec
		switch (strtolower($tab_data[$mois])) {
		case 'janv.' :
		case 'jan.' :
		case 'jan' :
		$tab_data[$mois] = '01';
		break;
		case 'févr.' :
		case 'feb.' :
		case 'feb' :
		$tab_data[$mois] = '02';
		break;
		case 'mars' :
		case 'mar.' :
		case 'mar' :
		$tab_data[$mois] = '03';
		break;
		case 'avr.' :
		case 'apr.' :
		case 'apr' :
		$tab_data[$mois] = '04';
		break;
		case 'mai' :
		case 'may' :
		$tab_data[$mois] = '05';
		break;
		case 'juin' :
		case 'jun.' :
		case 'jun' :
		case 'june' :
		$tab_data[$mois] = '06';
		break;
		case 'juil.' :
		case 'jul.' :
		case 'jul' :
		case 'july' :
		$tab_data[$mois] = '07';
		break;
		case 'août' :
		case 'aug.' :
		case 'aug' :
		$tab_data[$mois] = '08';
		break;
		case 'sept.' :
		case 'sep.' :
		case 'sep' :
		$tab_data[$mois] = '09';
		break;
		case 'oct.' :
		case 'oct' :
		$tab_data[$mois] = '10';
		break;
		case 'nov.' :
		case 'nov' :
		$tab_data[$mois] = '11';
		break;
		case 'déc.' :
		case 'dec.' :
		case 'dec' :
		$tab_data[$mois] = '12';
		break;
		}

		if ($tab_data[$mois] == '01' && $tab_data[$jour] == '01' && $tab_data[$an] == '1900' ) return '';
		switch (constant('DB_DATE_FORMAT')) {
		case 'AAAA-MM-JJ' :
		return $tab_data[$an].'-'.$tab_data[$mois].'-'.$tab_data[$jour].$str_hour;
		break;
		case 'AAAA/MM/JJ' :
		return $tab_data[$an].'/'.$tab_data[$mois].'/'.$tab_data[$jour].$str_hour;
		break;
		case 'JJ/MM/AAAA' :
		return $tab_data[$jour].'/'.$tab_data[$mois].'/'.$tab_data[$an].$str_hour;
		break;
		case 'MM/JJ/AAAA' :
		return $tab_data[$mois].'/'.$tab_data[$jour].'/'.$tab_data[$an].$str_hour;
		break;
		}*/
	}


}

?>