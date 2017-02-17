<?php
$scriptValues = [];
$sql = "CREATE TABLE `components` (
  `id` int(11) NOT NULL,
  `name` varchar(200) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `compents` (
  `id` int(11) NOT NULL,
  `name` varchar(200) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `coents` (
  `id` int(11) NOT NULL,
  `name` varchar(200) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

$nomTables = [];
while(preg_match('#CREATE TABLE `(.*?)`#',$sql,$matches)) {
	$nomTables[] = $matches[1];
	$sql = str_replace('CREATE TABLE `'.$matches[1].'`','',$sql);
}


foreach($nomTables as $key => $valueTable){

	while(preg_match('#`(.*?)`#',$sql,$matches)) {
		$scriptValues[] = $matches[1];
		$sql = str_replace('`'.$matches[1].'`','',$sql);
		$count++;
	}
	$file = fopen("DAO.php","w");
	$txt = "<?php";
	fwrite($file, $txt);
	$txt = "
	abstract class DAO
	{
		private static \$instance=[];
		
		private function _construct() {
		}
		
		/**
		* get an instance of Object --> create just one instance for each object (singleton)
		*/
	    public static function getInstance(){
	        \$class = get_called_class();
	        if(!isset(self::\$instance[\$class])){
	            self::\$instance[\$class] = new \$class();
	        }
	        return self::\$instance[\$class];
	    }
	    
	     /**
	    * get all the data of the current table
	    * @return array
	    */
	    public function findAll(){
	        \$file = file_get_contents('http://127.0.0.1/gsb/'.\$this->table.'/all');
	        \$result = json_decode(\$file);
	        
	        \$values = array();
	        foreach (\$result as \$row) {
	            \$values[\$row->id] = \$this->build($row);
	        }
	        return \$values;
	    }
	    
	    /**
	    * get data of one registration of current table
	    * @param int \$id
	    * @return array
	    */
	    public function findOneById(\$id){
	        \$file = file_get_contents('http://127.0.0.1/gsb/'.\$this->table.'/'.\$id);
	        \$result = json_decode(\$file);
	        
	        \$value = \$this->build(\$result[0]);
	        return \$value;
	    }
	    
	    /**
	    * remove one registration of current table
	    * @param int \$id
	    * @return boolean
	    */
	    public function deleteById(\$id){
	        \$file = file_get_contents('http://127.0.0.1/gsb/'.\$this->table.'/delete/'.\$id);
	        \$result = json_decode(\$file);
	        
	        if(\$result->message == 'Success'){
	            return true;
	        }else{
	            return false;
	        }
	    }
	
	    //All class DAO must have this method build
	    abstract function build(\$row);
	}
	?>";
	fwrite($file, $txt);
	fclose($file);

	$build = "";
	foreach($scriptValues as $key => $values) {
		if($key != $scriptValues[0]){
			$build .= "\$build->set".$values."(\$row->".$values.");";
		}
		else {
			$build .= "\$build = new ".$scriptValues[0]."(\$row->".$scriptValues[1].");";
		}
		$build .= "\n\t\t";
	}

	$file = fopen(ucfirst($valueTable)."DAO.php","w");
	$txt = "<?php
	class ".$scriptValues[0]."DAO extends DAO {
		protected \$table = '".$scriptValues[0]."';
		
		//build method
		public function build(\$row) {
			$build;
		}
	}
	
	?>";
	fwrite($file, $txt);
	fclose($file);


	$attribut = "";
	$setteurs = "";
	$getteurs = "";

	foreach($scriptValues as $key => $values) {
		if($key != $scriptValues[0]){
			$attribut .= "private $".$values.";";
			$setteurs .= "public function set".$values."($".$values.") { \n\t\t\$this->".$values." = $".$values.";\n\t}";
			$getteurs .= "public function get".$values."() { \n\t\treturn \$this->".$values.";\n\t}";
		}
		$attribut .= "\n\t";
		$setteurs .= "\n\t";
		$getteurs .= "\n\t";
	}


	$file = fopen(ucfirst($valueTable).".php","w");
	$txt = "<?php
	class ".$scriptValues[0]." {
		//Attributs
		$attribut
		
		//Setteurs
		$setteurs
		
		//Getteurs
		$getteurs
	}
	?>";
	fwrite($file, $txt);
	fclose($file);

	$update = "";
	$updateRoute = "";
	$updateFunction = "";
	$addQuery = "";
	$addQueryTable = "[";
	foreach($scriptValues as $key => $values) {
		if($updateFunction !== ""){
			$updateFunction .= ",";
			$addQuery .= " AND ";
		}
		if($key != $scriptValues[0]){
			$updateRoute .= ":".$values."/";
			$updateFunction .= "$".$values;
			$update .= "\$report->".$values." = ".$values.";";
			$addQuery .= $values." = :".$values;
			$addQueryTable .= "':".$values."' => $".$values.",";
		}
		$update .= "\n\t\t";
	}
	$addQueryTable .= "]";

	$file = fopen($valueTable."api.php","w");
	$txt =
	"<?php
	use RedBeanPHP\\R;
	require_once 'vendor/autoload.php';
	
	R::setup('mysql:host=YOUR_DATABASE_HOST; dbname=YOUR_DATABASE_NAME', 'USER', 'PASSWORD');
	
	\$app = new \\Slim\\Slim();
	
	\$app->group('/".$scriptValues[0]."', function() use(\$app) {
	
		\$app->get('/all', function() {
			\$all = R::getAll('SELECT * FROM ".$scriptValues[0]."');
			
			echo json_encode(\$all, JSON_UNESCAPED_UNICODE);
		});
		
		\$app->get('/:id', function(\$id) {
	        \$report = R::getAll('SELECT * FROM ".$scriptValues[0]." WHERE ".$scriptValues[1]." = ' . \$id);
	        echo json_encode(\$report, JSON_UNESCAPED_UNICODE);
	    });
	    
	    \$app->get('/update/".$updateRoute."', function(".$updateFunction.") {
	        \$report = R::load('".$scriptValues[0]."', \$id);
	        $update
	        R::store(\$report);
	        echo json_encode(\$report, JSON_UNESCAPED_UNICODE);
	    });
	    
	    \$app->get('/add/".$updateRoute."', function(".$updateFunction.") {
	        \$query = R::getAll('SELECT * FROM expensereport 
	                          WHERE $addQuery',
				   $addQueryTable
	        );
	        if (empty(\$query)) {
	        \$report = R::dispense('".$scriptValues[0]."');
	        $update
	        R::store(\$report);
	        echo json_encode(\$report, JSON_UNESCAPED_UNICODE);
	        } else {
	            echo NULL;
	        }
	    });
	    
	    \$app->get('/delete/:id', function(\$id) {
	        \$query = R::getAll('SELECT * FROM ".$scriptValues[0]." WHERE id = ' . \$id);
	        if (\$query) {
	            R::getAll('DELETE FROM ".$scriptValues[0]." WHERE id = ' . \$id);
	            echo json_encode(['message' => 'Success']);
	        } else {
	            echo json_encode(['message' => 'Error, this line doesn\'t exist']);
	        }
	    });
	});
	";
	fwrite($file, $txt);
	fclose($file);
}