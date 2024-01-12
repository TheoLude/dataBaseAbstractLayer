<?php

/**
*Classe ODBC pour attaquer un serveur sous ODBC avec la classe dataBaseAbstractLayer
*Elle crée le lien entre la classe d'abstraction et l'API réelle ODBC
*Attention, cette classe ne peut fonctionner que si le serveur web tourne sous Windows et
*dispose d'un driver ODBC correctement configuré
*
*1.0 - [Theo] - 28 mai 2002
*1.1 - [Theo] - 29 mai 2002 - Modification de escape_value
*							- Modification de unescape_value
*                           - Ajout de la gestion du message supplémentaire dans la méthode query
*1.2 - [Jojo] - 03/03/2003	- modifiée pour que ca marche (!)
*							- les fetch fonctionnent (avant NON)
*							- simulation dans tout les cas du num_rows sur un select (parcours force), reprise db_query
*							- rajout des methodes manquantes db_date_from_db, db_date_to_db
*							- db_num_rows simulée, fonctionne
*							- db_seek simulée, fonctionne
*
* @package caliObject
* @subpackage caliSQLObject
*
*/

namespace eXtensia\dataBaseAbstractLayer;

class Odbc extends errorManager {

	var $db_connexion = '';
	var $db_dsn = '';
	var $db_name = '';
	var $db_login = '';
	var $db_password = '';
	var $db_id_requete = '';
	var $db_id_prepare = '';
	var $db_backup_query = '';
	var $db_ges_erreur = false;
	var	$db_int_fakie_row = 0;
	var $bool_dbal_erreur = false;


	/**
	* Constructeur de la classe ODBC
	* Il construit un nouvel objet ODBC
	*
	* @param
	* @access private
	* @return
	*/
	function Odbc($str_dsn, $str_login, $str_password, $str_name = '', $bool_ges_erreur = false){
		$this -> errorManager();

		$this -> db_login = $str_login;
		$this -> db_password = $str_password;
		$this -> db_dsn = $str_dsn;
		$this -> db_name = $str_name;
		$this -> db_ges_erreur = $bool_ges_erreur;

		if (! function_exists('odbc_connect')) {
			if (! $bool_ges_erreur) $this -> ErrorTracker(5, "La librairie ODBC n'est pas disponible sur ce serveur - Message PHP : <span style='color:red'>".$php_errormsg."</span>");
			else $parent -> bool_dbal_erreur = true;
		}
	}

	/**
	* @access private
	*
	*
	*
	*/
	function db_connect(){
		if (! $db = @odbc_connect($this -> db_dsn, $this -> db_login, $this -> db_password)) {
			if (! $this -> db_ges_erreur) $this -> ErrorTracker(5, 'Connexion via ODBC impossible - Message ODBC : <span style="color:red">'.odbc_errormsg().'</span>.');
			else return false;
		}
		$this -> db_connexion = $db;
		return $db;
	}

	/**
	* @acces private
	* Permet d'éxecuter une requête sur la base de donnée de type $db_type, elle crée alors un identifiant sur la requete qui vient
	* d'être effectuée.
	*
	* @param $str_query string - Chaine contenant la requête à effectuer
	*
	*[Jojo] 03/03/2003	-> modifiée pour que ca marche (!)
	* @return void
	*/
	function db_query($str_query, $str_message = ''){

		if ($str_message != '') $str_message = 'Message de service :'.$str_message.'<br />';
		/*[DEL Théo 08/12/2014]
		if (! $id = odbc_prepare($this -> db_connexion, $str_query)) return $this -> ErrorTracker(5, 'Erreur sur la preparation de requête : <b>'.$str_query.'</b><br /> - Message ODBC : <span style="color:red">'.odbc_errormsg().'</span>.');
		$this -> db_id_prepare = $id;
		if (! $id = odbc_execute($this -> db_id_prepare)) return $this -> ErrorTracker(5, 'Erreur sur l\'execution de requête : <b>'.$str_query.'</b><br /> - Message ODBC : <span style="color:red">'.odbc_errormsg().'</span>.');
		$this -> db_id_requete = $id;*/

		if (! $id = @odbc_exec($this -> db_connexion, $str_query)) return $this -> ErrorTracker(5, 'Erreur sur l\'execution de requête : <b>'.$str_query.'</b><br /> - Message ODBC : <span style="color:red">'.odbc_errormsg().'</span>.');
		$this -> db_id_requete = $id;

		// ssi select, on parcourt ( oblig. ) pour simuler le num rows
		$this -> db_int_fakie_row = 0;
		$this -> db_backup_query = '';

		/*if (preg_match("/SELECT/",$str_query)) {
		$this -> db_backup_query = $str_query;
		$this -> db_fetch($tb_tmp);
		if ($tb_tmp) $this -> db_int_fakie_row = 1;
		while ($tb_tmp) {
		$this -> db_fetch($tb_tmp);
		if ($tb_tmp) $this -> db_int_fakie_row++;
		}

		// [COM Théo 18/03/2010] Double exécution incompréhensible, corrections de Jojo du 03/03/2003, mais sans ça ça ne marche pas...
		if (! $id = @odbc_prepare($this -> db_connexion, $str_query)) return $this -> ErrorTracker(5, 'Erreur sur la preparation de requête : <b>'.$str_query.'</b><br /> - Message ODBC : <span style="color:red">'.odbc_errormsg().'</span>.');
		$this -> db_id_prepare = $id;
		if (! $id = @odbc_execute($this -> db_id_prepare)) return $this -> ErrorTracker(5, 'Erreur sur l\'execution de requête : <b>'.$str_query.'</b><br /> - Message ODBC : <span style="color:red">'.odbc_errormsg().'</span>.');
		$this -> db_id_requete = $id;
		}*/

		return $id;
	}


	//	* [Jojo] 03/03/2003	-> dummy
	function db_version_info() {
		return 'ODBC :: no version info available for now';
	}

	//	* [Jojo] 03/03/2003	-> modifiée pour que ca marche (!)
	function db_seek($int_row_number){
		if ( $this -> db_backup_query ) {
			$this -> db_query($this -> db_backup_query);
			$i = 0;
			while ( $i < $int_row_number ){
				@odbc_fetch_into($this -> db_id_prepare,$tab_tmp);
				$i++;
			}
		}
	}

	/**
	* @access private
	* Renvoie un enregsitrement sur la requête en cours
	*
	* @param void
	*
	* @return array - L'enregistrement en cours de lecture (tableau associatif)
	* [Jojo] 03/03/2003	-> modifiée pour que ca marche (!)
	*/
	function db_fetch(&$tab_resultat){
		// piege : fetch_into renvoie un tableau dont les indices commence à 0 ( zéro )
		//         field_name référence les champs par N° de champs, donc commence à 1
		$tab_resultat = false;
		if (!@odbc_fetch_into($this -> db_id_prepare, $tab_resultat)) return false;
		$tab_work = $tab_resultat;
		foreach ( $tab_work as $int_key => $str_value ) {
			$tab_resultat[@odbc_field_name($this -> db_id_prepare,$int_key + 1)] = $str_value;
		}
	}

	/**
	* @access private
	*
	*
	*
	*/
	function db_row(&$tab_resultat){
		$this -> db_fetch($tab_resultat);
	}

	/**
	* @access private
	*
	*
	*
	*/
	function db_escape_value($str_value){
		if (is_string($str_value) && ! get_magic_quotes_gpc()) return $str_value = addSlashes($str_value);
		else return $str_value;
	}

	/**
	* @access private
	*
	*
	*
	*/
	function db_unescape_value($str_value){
		return stripslashes($str_value);
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
		return $this -> db_int_fakie_row;
	}


	// assuming the odbc driver complies with odbc date specification, e.g. AAAA-MM-JJ
	//	* [Jojo] 03/03/2003	-> ajoutées pour que ca marche (!)
	function db_date_to_db($StrDate){
		if (!$StrDate) return $StrDate;

		switch (constant('DB_DATE_FORMAT')) {
			case 'AAAA-MM-JJ' :
			return $StrDate;
			break;
			case 'AAAA/MM/JJ' :
			$StrDate = ereg_replace('-','/',$StrDate);
			return $StrDate;
			break;
			case 'JJ/MM/AAAA' :
			return $StrDate{6}.$StrDate{7}.$StrDate{8}.$StrDate{9}.'-'.$StrDate{3}.$StrDate{4}.'-'.$StrDate{0}.$StrDate{1};
			break;
		}
	}

	function db_date_from_db($StrDate){
		if ($StrDate == '0000-00-00') return ' '; //mysql odbc date bug
		if (!$StrDate) return $StrDate;
		switch (constant('DB_DATE_FORMAT')) {
			case 'AAAA-MM-JJ' :
			return $Strdate;
			break;
			case 'AAAA-MM-JJ' :
			return $Strdate{0}.$Strdate{1}.$Strdate{2}.$Strdate{3}.'/'.$Strdate{5}.$Strdate{6}.'/'.$Strdate{8}.$Strdate{9};
			break;
			case 'JJ/MM/AAAA' :
			return $StrDate{8}.$StrDate{9}.'/'.$StrDate{5}.$StrDate{6}.'/'.$StrDate{0}.$StrDate{1}.$StrDate{2}.$StrDate{3};
			break;
			case 'MM/JJ/AAAA' :
			return $StrDate{5}.$StrDate{6}.'/'.$StrDate{8}.$StrDate{9}.'/'.$StrDate{0}.$StrDate{1}.$StrDate{2}.$StrDate{3};
			break;
		}
	}
}

?>