<?php

include("db.php");
/*use the item selected from the dropdown to populate the next dropdown
 *need to know which dropdown was selected, what value was selected,
 *and need to "return" the next dropdown

 *when retrieveing files, instead of doing a dropdown, show all the files
 */

function get_dropdown($uni=null, $dep=null, $prof=null, $cor=null, $sec=null) {
    $dropdown_result = '';

    
    if (!$uni) {
        //populate university
        $dropdown_result .= "<select id='university'>";

        $query_result = @mysql_query("SELECT Name FROM university",$mydb);
        $dropdown_result.=read_query_result($query_result, 'Name');
        $dropdown_result.="</select>";
    }

    elseif (!$dep) {
        //populate department using university
        $dropdown_result .= "<select id='department'>";
        $query_result = @mysql_query("SELECT Name FROM department WHERE U_ID = $uni",$mydb);
        $dropdown_result.=read_query_result($query_result, 'Name');
        $dropdown_result.="</select>";
    }

    elseif (!$prof && !$cor) {
        //populate prof and cor using university
        $dropdown_result .= "<select id='professor'>";
        $query_result = @mysql_query("SELECT Name FROM professor WHERE D_ID = $dep",$mydb);
        $dropdown_result.=read_query_result($query_result, 'Name');
        $dropdown_result.="</select>";

        $dropdown_result.="<br>";

        $dropdown_result .= "<select id='course'>";
        $query_result = @mysql_query("SELECT Name FROM course WHERE D_ID = $dep",$mydb);
        $dropdown_result.=read_query_result($query_result, 'Name');
        $dropdown_result.="</select>";
    }

    elseif (!$sec) {
        if (!$prof) {
            $query_result = @mysql_query("SELECT Year, Semester FROM section WHERE C_ID = $cor",$mydb);
        }

        elseif (!$cor) {            
            $query_result = @mysql_query("SELECT Year, Semester FROM section WHERE P_ID = $prof",$mydb);
        }

        else {
            $query_result = @mysql_query("SELECT Year, Semester FROM section WHERE C_ID = $cor or P_ID = $prof",$mydb);
        }

        $dropdown_result .= "<select id='section'>";
        $dropdown_result.=read_query_result($query_result, 'Name');
        $dropdown_result.="</select>";
    }

    else {
        //get the files
    }

    //return $dropdown_result;
    echo $dropdown_result;
}

/*
 * Retrieves input from the query by getting the values
 * from one attribute.  The output is done so the items 
 * from the query can be made into a dropdown menu
 */
function read_query_result($query_result, $row_attribute) {
    $query_output = '';

    while ($row = mysql_fetch_array($query_result)) {
        $query_output.="<option role='presentation'>{$row[$row_attribute]}</option>";
    }

    return $query_output;
}

/**
 * Echoes a dropdown that has a given css id (which is also used as the
 * name of its first option). The dropdown is populated by the result of a
 * SQL query that needs to contain integer 'ID's and string 'Name's as values.
 * @param $id = the string css id to give to the dropdown
 * @param $queryResult = the result array of a mySQL query containing rows with
        values for the attributes 'ID' and 'Name'
 * Usage: output_named_dropdown_with_id('university', mysql_query('select ID, Name from university'));
 * Result: <select id="university">
                <option value="-1">university[choose one]</option>
                <option value="1">Appalachian State University</option>
                <option value="2">Arizona State University</option>
            </select>
*/
function output_named_dropdown_with_id($id, $queryResult) {
    echo '<select id="'.$id.'"><option value="-1">'.$id.' [choose one]</option>';
    
    while ($row = mysql_fetch_array($queryResult)) {
        echo '<option value="'.$row['ID'].'">'.$row['Name'].'</option>';
    }
    echo '</select>';
}

/*
    Checks whether the variable currently holds the string value 'undefined'
    and sets any variables with that value to be null. The 'undefined' variable
    is introduced by AJAX when sending GET requests with undefined javascript
    variables.
    Usage:
    $something = handle_undefined($something);
*/
function handle_undefined($var) {
	if (is_int($var)) {
		return intval($var);
	}
    return ($var == 'undefined' || $var == 'null') ? null : $var;
}

/*
 * helper function taht determines whether a new value has changed from null
 */
function changed_from_null($newVal, $oldVal) {
	return $oldVal == null && $newVal != $oldVal;
}

function changed_from_val($newVal, $oldVal) {
	return $oldVal != null && $newVal != $oldVal;
}

function unchanged($newVal, $oldVal) {
	return $newVal == $oldVal;
}
/*
 * This function accepts all of the possible values from the site front-end and requests the appropriate
 * back-end response.
 */
function dispatcher($newUni, $oldUni, $newDep, $oldDep, $newProf, $oldProf, $newCor, $oldCor, $newSec, $oldSec) {
    if ($newUni == null) { //if nothing has been selected. Presumably, nothing has been served to the user
		output_named_dropdown_with_id('university', mysql_query('select ID, Name from university'));
		return;
    }	
    if (changed_from_null($newUni, $oldUni)/* || changed_from_val($newUni, $oldU)*/) {
    	$sql = "select ID, Name from department where U_ID = $newUni;";
    	output_named_dropdown_with_id('department', mysql_query($sql)); 
    	echo "<pre>$sql</pre>";
    }
}
//generate a dropdown whenever this script is run from AJAX
/*get_dropdown(
    handle_undefined($_GET['uni']),
    handle_undefined($_GET['dep']),
    handle_undefined($_GET['prof']),
    handle_undefined($_GET['cor']),
    handle_undefined($_GET['sec'])
);*/
//echo "{$_POST['uni']}/{$_POST['oldUni']}<br>{$_POST['dep']}/{$_POST['oldDep']}<br>{$_POST['prof']}/{$_POST['oldProf']}<br>{$_POST['cor']}/{$_POST['oldCor']}<br>{$_POST['sec']}/{$_POST['oldSec']}<br>";
dispatcher(handle_undefined($_GET['uni']),
		handle_undefined($_GET['oldUni']),
		handle_undefined($_GET['dep']),
		handle_undefined($_GET['oldDep']),
		handle_undefined($_GET['prof']),
		handle_undefined($_GET['oldProf']),
		handle_undefined($_GET['cor']),
		handle_undefined($_GET['oldCor']),
		handle_undefined($_GET['sec']),
		handle_undefined($_GET['oldSec'])
		);
