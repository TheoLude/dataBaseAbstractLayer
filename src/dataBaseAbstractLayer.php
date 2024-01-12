<?php

/**
* Classe generale d'abstraction de la base de donnee
* Elle definit des prototypes de methodes (~classes abstraites) qui sont linkes dynamiquement avec les methodes reelles
*
* @author [Theo] - 28 mai 2002
* @version 1.3
*
*/

namespace eXtensia\dataBaseAbstractLayer;

class dataBaseAbstractLayer extends eXtensia\errorManager\errorManager {

	var $db_type = '';
	var $db_recordset = Array();
	var $db_object = '';
	var $db_current_query = '';
	var $db_init_db_name = '';
	var $bool_dbal_erreur = false;
	var $id_connect;

	/**
	* @access public
	* Constructeur de la classe dataBaseAbstractLayer
	* Il construit un nouvel objet dataBaseAbstractLayer et cree une instance
	* d'un objet dataBase du type specifie qu'il renvoie
	*
	* @param string $str_connection - information sur la connexion a la base.
	*
	* @return object $obj_dabaBase - objet connection a la base renvoye
	*/
	function dataBaseAbstractLayer($str_connexion = '', $bool_ges_erreur = false, $bool_force_connect = false){

		global $str_url_connexion_global;

		$this -> errorManager((($bool_ges_erreur == true)?'off':'on'));  //[MOD Théo 21/08/2019]  Il faudrait revoir complétement la gestion des erreurs et la classe toute entière ...
		$this -> ipTables = explode(',', constant('DB_IP_LIST'));
		if ($str_connexion == '') {
			if (defined('DB_CONNEXION')) $str_connexion = constant('DB_CONNEXION');
			else {
				if (! $bool_force_connect)	$str_connexion = $str_url_connexion_global;
			}
		}
		if ($str_connexion == '') $this -> errorTracker(5 , 'La chaine d\'information de connexion &agrave; la base de donn&eacute;e attaqu&eacute;e n\'est pas d&eacute;finie, la classe dataBaseAbstractLayer ne peut pas cr&eacute;er une instance de l\objet dataBaseAbstractLayer.');

		//$this -> dbal_log('<br> current connect string :: '.$str_connexion."\r\n");

		// Decomposition et analyse de la chaine de connexion pour recuperer le type
		if (($int_pos = strpos($str_connexion, '://')) !== false) {
			$str = substr($str_connexion, 0, $int_pos);
			$str_connexion = substr($str_connexion, $int_pos + 3);
			$str_type = ucfirst(strtolower($str));
		}

		if ($str_type == '') $this -> errorTracker(5, 'Le type de la base de donn&eacute;e attaqu&eacute;e n\'est pas d&eacute;fini, la classe dataBaseAbstractLayer ne peut pas cr&eacute;er une instance de l\objet dataBaseAbstractLayer.');
		else {
			// Decomposition et analyse de la chaine de connexion
			$this -> db_type = $str_type;
			if (($int_pos = strpos($str_connexion, '@')) !== false) {
				$str_login = substr($str_connexion, 0, $int_pos);
				$str_database = substr($str_connexion, $int_pos + 1);
			}

			if (($int_pos = strpos($str_login, ':')) !== false) {
				$str_user = substr($str_login, 0, $int_pos);
				$str_password = substr($str_login, $int_pos + 1);
			}

			if (($int_pos = strpos($str_database, '/')) !== false) {
				$str_ip = substr($str_database, 0, $int_pos);
				$str_name = substr($str_database, $int_pos + 1);
				$this -> db_init_db_name = $str_name;
			}
		}

		// Recherche du fichier de classe PHP correspondant au type de la classe db souhaitee
		if (! constant('DB_SUBCLASS_PATH')) $this -> errorTracker(5, 'DB_SUBCLASS_PATH n\'est pas d&eacute;fini, la classe dataBaseAbstractLayer ne sait pas situer les fichiers de configurations.');
		if (! @is_file(DB_SUBCLASS_PATH.'db'.$str_type.'.php')) $this -> errorTracker(5, 'Le fichier de d&eacute;finition des m&eacute;thodes de la base '.$str_type.' n\'est pas &agrave; l\'emplacement '.DB_SUBCLASS_PATH.'db'.$str_type.'.php.');
		else include_once(DB_SUBCLASS_PATH.'db'.$str_type.'.php');

		// Instanciation de l'objet db
		if (! class_exists($str_type)) $this -> errorTracker(5, 'La classe '.$str_type.' n\'est &agrave; priori pas d&eacute;finie dans le fichier '.DB_SUBCLASS_PATH.'db'.$str_type.'.php, la classe dataBaseAbstractLayer ne sait pas situer les fichiers de configurations.');
		else {
			$obj_dataBase = new $str_type($str_ip, $str_user, $str_password, $str_name, $bool_ges_erreur);
			$this -> db_object =& $obj_dataBase;

			$this -> id_connect = $this -> dbal_connect();
			if ($this -> id_connect) return $obj_dataBase;
		}

		$this -> bool_dbal_erreur = true;
		$this -> errorChecker();
	}

	/**
	* @access private
	* Permet de creer la connexion a la base de type $db_type, c'est une methode abstraite qui se contente d'appeler la methode de la
	* classe propre a la base de donnee
	*
	* @param object $obj_db - Objet base de donnee cree avec dataBaseAbstractLayer, qui permet de faire une relation a la base selectionnee
	*
	* @return void
	*/
	function dbal_connect(){
		return $this -> db_object -> db_connect();
	}

	function dbal_version_info(){
		return $this -> db_object -> db_version_info();
	}

	function dbal_get_dbtype(){
		return $this -> db_type;
	}

	function dbal_close(){
		$this -> db_object -> db_close();
	}

	function dbal_date_to_db($str_date, $bool_hour = false){

		if ($bool_hour) list($str_date, $str_hour) = explode(' ', $str_date);
		if ($str_hour && ! preg_match('/^[0-9]{1,2}:[0-9]{2}:[0-9]{2}$/', substr($str_hour, 0, 8))) $str_hour = false;
		return $this -> db_object -> db_date_to_db($str_date, $str_hour);
	}

	function dbal_date_from_db($str_date, $bool = false){
		return $this -> db_object -> db_date_from_db($str_date, $bool);
	}

	function dbal_get_last_id(){
		return $this -> db_object -> db_last_insert_id();
	}

	function dbal_affected_rows(){
		return $this -> db_object -> db_affected_rows();
	}

	/**
	* @access public
	* Permet d'executer une requête sur la base de donnee de type $db_type c'est une methode abstraite qui se contente d'appeler la methode
	* de la classe propre a la base de donnee
	*
	* @param string$ str_requete  - Chaine contenant la requête a effectuer
	* @param array $tab_filter - Si rempli, entraine automatiquement la detection d'eventuel filtres. Les tables a filtrer sont le contenu du tableau.
	*
	* @return boolean $bool_result - Renvoie la reussite ou l'echec de la requête
	*/
	function dbal_query($str_requete = '', $tab_filter = '', $bool_cesure = true){
		unset($this -> db_recordset);
		if (! is_string($str_requete) && $str_requete) return $this -> errorTracker(5, 'La requête "<b><i>'.$str_requete.'</i></b>" n\'est pas une chaine, contenu de la variable '.print_r($str_requete, true));

		if ($str_requete == '') $str_requete = $this -> db_current_query."\n";
		else $this -> queryName = $str_requete;

		if ( $bool_cesure ) $str_requete = $this -> _cesure($str_requete, 180);

		if (constant('DB_LOG') != '') $this -> dbal_log($str_requete);

		return $this -> db_object -> db_query($str_requete, $this -> queryName);
	}

	function dbal_rs_query($str_requete = '', $str_message = ''){
		if ($str_requete == '') $str_requete = $this -> db_current_query;
		else $this -> queryName = $str_requete;

		if (constant('DB_LOG') != '') $this -> dbal_log($str_requete);

		$bool_result = $this -> db_object -> db_query($str_requete, $this -> queryName);
		if ($bool_result && preg_match('/SELECT/i', $str_requete)) $this -> dbal_recordset();

		return $bool_result;
	}


	function dbal_select_db($str_db){
		return $this -> db_object -> db_select_db($str_db);
	}

	function dbal_test_query($str_query){
		return $this -> db_object -> db_test($str_query);
	}

	/**
	* @access public
	*
	* Permet de se positionner sur un enregistrement a la ligne $int_row_number
	*
	* @param integer $int_row_number - Le rang de la ligne sur laquelle on veut se positionner
	* @param array $tab_fields_name - Un tableau contenant la liste des champs des lignes qu'on veut recuperer
	*
	*/
	function dbal_seek($int_row_number){
		$this -> db_object -> db_seek($int_row_number);
	}


	function _cesure($str_chaine, $int_size = 100){

		$int_pos = 0;
		$str_requete = '';

		if (strlen($str_chaine) < $int_size) return $str_chaine;

		$backup = $str_chaine;
		$bool_in_single = false;
		$bool_in_double = false;
		$int_taille_morceau = $int_size;
		for ($i = 0; $i < strlen($str_chaine); $i++) {
			if (substr($str_chaine, $i, 1) == "'" ) {
				if ($bool_in_single) {
					$bool_in_single = false;
				}else {
					$bool_in_single = true;
				}
			}
			if (substr($str_chaine, $i, 1) == '"' ) {
				if ($bool_in_double) {
					$bool_in_double = false;
				}else {
					$bool_in_double = true;
				}
			}
			if ($i >= $int_taille_morceau && ! $bool_in_single && ! $bool_in_double && substr($str_chaine, $i, 1) == ' ') {
				$str_chaine = substr($str_chaine, 0, $i+1)."\n".substr($str_chaine, $i);
				$int_taille_morceau += $int_size;
				$i++;
			}
		}

		$str_requete = $str_chaine;
		return $str_requete;
	}


	/**
	* @access private
	* Permet de remplacer de logger les requêtes effectuees dans un fichier de log specifique
	*
	* @param string $str_requete- La requête effectuee
	*
	* @return void
	*/
	function dbal_log($str_requete){
		/*if (in_array(getenv('REMOTE_ADDR'), $this -> ipTables)){
		list($usec, $sec) = explode(' ', microtime());
		if ($this -> db_current_query) $str_pre = ' - "'.$this -> queryName.'"';
		else $str_pre = '';

		$str_message = date('d/m/Y H:i:s').':'.substr($usec, 2).$str_pre.' - '.$str_requete."\r\n";

		$ouv = @fopen(DB_LOG, 'a');
		@fputs($ouv, $str_message);
		@fclose($ouv);
		}*/
	}

	/**
	* @access public
	* Permet de remplacer dans une requête les champs par leurs valeurs
	*
	* @param string $str_requete_name - Le nom de la requête, cad son indice dans le tableau $tab_requetes
	* @param array $tab_values - Un tableau associatif contenant la liste des valeurs a remplacer associee a leurs marqueurs dans la requête
	*
	* @return string $str_query - Renvoie la requête formatee
	*/
	function dbal_parse_query($str_requete_name, $tab_values = '', $bool_escape = true){

		global $tab_requetes;

		$this -> queryName = $str_requete_name;
		$str_query = $tab_requetes[$str_requete_name];

		if ($str_query == '') $this -> errorTracker(5, 'La requête "<b><i>'.$str_requete_name.'</i></b>" est vide ou non d&eacute;finie.');
		preg_match_all("/#([^ \"',\)]+)/", $str_query, $tab_result);

		for($i = 0; $i < count($tab_result[0]) && is_array($tab_values); $i ++){
			if ( $bool_escape ) {
				$str_query = str_replace($tab_result[0][$i], $this -> dbal_escape_value($tab_values[$tab_result[1][$i]]), $str_query);
			} else {
				$str_query = str_replace($tab_result[0][$i], $tab_values[$tab_result[1][$i]], $str_query);
			}
		}

		//si MetaObject est include dans l'application, tenir compte des quotes magiques de MetaObject
		if (defined('MO_MAGIC_DYNAMIC_QUOTE_CODE')) $str_query = str_replace(constant('MO_MAGIC_DYNAMIC_QUOTE_CODE'),'"',$str_query);

		$this -> db_current_query = $str_query;
		return $str_query;
	}

	/**
	* Retourne la derniere requete effectuee
	*
	*
	*/
	function dbal_get_last_query(){
		return $this -> db_current_query;
	}

	/**
	* @access public
	* Permet de recuperer un enregistrement en base de donnee c'est une methode abstraite qui se contente d'appeler la methode
	* de la classe propre a la base de donnee
	*
	* @param array $tab_resultatZ - Paramètre optionnel rendu obligatoire a cause d'ODBC. Ce tableau contiendra les resultats du fetch.
	*
	* @return void
	*/
	function dbal_fetch(&$tab_return_data){
		if ($this -> db_type == 'Odbc')  $this -> db_object -> db_fetch($tab_return_data);
		else $tab_return_data = $this -> db_object -> db_fetch();

		return $tab_return_data;
	}

	/**
	* @access public
	* Permet de recuperer un enregistrement en base de donnee avec un fetch row, c'est une methode abstraite qui se contente d'appeler la methode
	* de la classe propre a la base de donnee
	*
	* @param array $tab_resultatZ - Paramètre optionnel rendu obligatoire a cause d'ODBC. Ce tableau contiendra les resultats du fetch.
	*
	* @return void
	*/
	function dbal_row(&$tab_resultatZ){
		if ($this -> db_type == 'Odbc') $tab_resultatZ = $this -> db_object -> db_row($tab_resultatZ);
		else $tab_resultatZ = $this -> db_object -> db_row();

		if ($tab_resultatZ) {
			foreach ( $tab_resultatZ as $key => $value ) {
				$value = str_replace("\n",'',$value);
				$value = str_replace("\r",'',$value);
				$tab_resultatZ[$key] = $value;
			}
			return $tab_resultatZ;
		}
	}

	/**
	* @access private
	* Permet de creer un recordset automatiquement sur un select
	*
	* @param void
	*
	* @return void
	*/
	function dbal_recordset(){
		$i = 1;
		while($this -> dbal_fetch($tab_tmpZ)){
			while (list($key, $value) = each($tab_tmpZ)){
				// suppression des indices numeriques dans le cas de MySQL par exemple
				if (is_string($key)){
					$this -> db_recordset[$i][$key] = $this -> dbal_unescape_value($value);
				}
			}
			$i ++;
		}
		if (is_array($this -> db_recordset)) reset($this -> db_recordset);
		$this -> db_recordset_index = 1;

		return $this -> db_recordset;
	}

	/**
	* @access public
	* Permet de se placer au debut d'un recordset
	*
	* @param void
	*
	* @return void
	*/
	function dbal_rs_start(){
		return $this -> db_recordset[1];
	}

	/**
	* @access public
	* Permet de se placer a la fin d'un recordset
	*
	* @param void
	*
	* @return void
	*/
	function dbal_rs_end(){
		return $this -> db_recordset[count($this -> db_recordset)];
	}

	/**
	*public
	*Permet de se placer sur l'enregistrement suivant d'un recordset
	*
	*@param void
	*
	*@return void
	*/
	function dbal_rs_next(){
		if ($this -> db_recordset_index == count($this -> db_recordset)) return false;
		return $this -> db_recordset[$this -> db_recordset_index ++];
	}

	/**
	*public
	*Permet de se placer sur l'enregistrement precedent d'un recordset
	*
	*@param void
	*
	*@return void
	*/
	function dbal_rs_preview(){
		if ($this -> db_recordset_index < 1) return false;
		return $this -> db_recordset[$this -> db_recordset_index --];
	}

	/**
	*public
	*Permet de se placer sur un index particulier d'un recordset
	*
	*@param $int_index integer - Indice numerique de l'enregistrement a retourner
	*
	*@return void
	*/
	function dbal_rs_moveto($int_index){
		if ($int_index < 0 || $int_index > count($this -> db_recordset)) return false;
		return $this -> db_recordset[$int_index];
	}

	/**
	*public
	*Renvoie un champ avec les valeurs sensibles echappees
	*
	*@param $str_value string - La valeur du champ a echapper
	*
	*@return $str_escape string - La valeur echappee
	*/
	function dbal_escape_value($str_value){
		return $this -> db_object -> db_escape_value($str_value);
	}

	/**
	*public
	*Renvoie un champ avec les valeurs sensibles rendues a leur forme initiale
	*
	*@param $str_value string - La valeur du champ a desechapper
	*
	*@return $str_escape string - La valeur desechappee
	*/
	function dbal_unescape_value($str_value){
		return $this -> db_object -> db_unescape_value($str_value);
	}

	/**
	* @access public
	* Renvoie le nombre d'enregistrement affectes par la dernière requête realisee
	*
	* @param void
	*
	* @return integer $int_num - Le nombre d'enregistrements concernes par la dernière requête
	*/
	function dbal_num_rows(){
		if (count($this -> db_recordset) > 0) return count($this -> db_recordset);
		else return $this -> db_object -> db_num_rows();
	}

	/**
	* @access public
	* Permet de recuperer le dernier id insere de la table specifiee
	*
	* @param integer $int_index - Indice numerique de l'enregistrement a retourner
	*
	* @return array -
	*/
	function dbal_get_id($str_table){
		if ($str_table == '') $this -> errorTracker(2, 'Vous devez necessairement preciser le nom d\'une table.');
		$this -> dbal_rs_query('SELECT '.$str_table.' FROM gestion_index', 'Recuperation de l\'index de la table '.$str_table.'.');
		$tab_result = $this -> dbal_rs_start();
		$this -> dbal_query('UPDATE gestion_index SET '.$str_table.' = '.$str_table.' + 1');
		return $tab_result[$str_table];
	}

	/**
	* Permet de renvoyer le message d'erreur
	*
	*
	*/
	function getErrorPile($bool_comment = true){

		$str_message .= $this -> ErrorChecker('GET', $bool_comment);
		$str_message .= $this -> db_object -> ErrorChecker('GET', $bool_comment);

		return $str_message;
	}

	/**
	* Précise le nom d'une table dont on veut contrôler les entrées avant enregistrement
	*
	*
	* @return integer tableId
	*/
	public function setTableToCheck($strTable){
		return $this -> db_object -> setTableToCheck($strTable);
	}

	/**
	* Contrôle une valeur avant insertion dans un champ
	*
	*
	* @return boolean
	*/
	public function isValueCompliantWithTableField($intTableId, $strFieldName, $strValue, $boolMandatory = false){
		return $this -> db_object -> isValueCompliantWithTableField($intTableId, $strFieldName, $strValue, $boolMandatory);

	}

	/**
	* Précise le nom d'une table dont on veut contrôler les entrées avant enregistrement
	*
	*
	* @return boolean
	*/
	public function isTableChecked($intTableId, &$strErrorMessage){
		return $this -> db_object -> isTableChecked($intTableId, $strErrorMessage);
	}

}

?>