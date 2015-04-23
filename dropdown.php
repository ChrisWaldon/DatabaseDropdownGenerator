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
    echo '<li class="dropdown"><select class="form-control" id="'.$id.'"><option value="-1">'.$id.' [choose one]</option>';
    if ($id == 'section') { //handle the more complex entries for the section dropdown
    	while ($row = mysql_fetch_array($queryResult)) {
    		echo "<option value='{$row['ID']}'>{$row['Abbreviation']}{$row['CNum']}-{$row['SNum']} {$row['Name']} {$row['Semester']} {$row['Year']}</option>";
//     		echo '<pre>'.var_export($row, true).'</pre>';
    	}
    }
    else { //handle the normal ID/Name option output
            while ($row = mysql_fetch_array($queryResult)) {
                echo '<option value="'.$row['ID'].'">'.($id == 'course' ? "{$row['Abbreviation']}{$row['Number']} ": "").$row['Name'].'</option>';
            }
    }
    echo '</select></li>';
}

/*
    Checks whether the variable currently holds the string value 'undefined'
    and sets any variables with that value to be null. The 'undefined' variable
    is introduced by AJAX when sending GET requests with undefined javascript
    variables.
    Usage:
    $something = handle_undefined($something);
*/
function clean_input($var) {
	if (is_int($var)) {
		return intval($var);
	}
    return ($var == 'undefined' || $var == 'null') ? null : $var;
}

/*
 * helper function taht determines whether a new value has changed from null
 */
function changed_from_null($newVal, $oldVal) {
	return $oldVal == null && $newVal != $oldVal && $newVal != -1;
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
// 	echo '<pre>'.var_export($_GET,true).'</pre>';
	if ($newUni == -1) {//if invalid selection, do nothing.
		return; 
	}
    elseif ($newUni == null) { //if nothing has been selected. Presumably, nothing has been served to the user
		output_named_dropdown_with_id('university', mysql_query('select ID, Name from university'));
		return;
    }	
    elseif (changed_from_null($newUni, $oldUni) || changed_from_val($newUni, $oldUni)) {//if we have a new university, re-serve department
    	$sql = "select ID, Name from department where U_ID = $newUni;";
    	output_named_dropdown_with_id('department', mysql_query($sql)); 
//     	echo "<pre>$sql</pre>";
    }
    elseif (changed_from_null($newDep, $oldDep) || changed_from_val($newDep, $oldDep)) {
    	$sql = "select ID, Name from professor where D_ID = $newDep;";
    	output_named_dropdown_with_id('professor', mysql_query($sql)); 
//     	echo "<pre>$sql</pre>";
    	$sql = "select C.ID, C.Name, D.Abbreviation, C.Number from course as C, department as D where D_ID = D.ID and D_ID = $newDep;";
    	output_named_dropdown_with_id('course', mysql_query($sql)); 
//     	echo "<pre>$sql</pre>";
    }
    elseif (changed_from_null($newProf, $oldProf) || changed_from_val($newProf, $oldProf)) {
//         echo '<pre>'.var_export($_GET,true).'</pre>';
    	$sql = "select S.ID, D.Abbreviation, C.Number as CNum, S.Number as SNum, P.Name, S.Semester, S.Year from section as S, professor as P, course as C, department as D where C.D_ID = D.ID and P_ID = P.ID and C_ID = C.ID and P_ID = $newProf".($newCor != -1 && $newCor != null ? " and C_ID = $newCor;": ';');
    	output_named_dropdown_with_id('section', mysql_query($sql));
//     	echo "<pre>$sql</pre>";
    }
    elseif (changed_from_null($newCor, $oldCor) || changed_from_val($newCor, $oldCor)) {
// 	    echo '<pre>'.var_export($_GET,true).'</pre>';
    	$sql = "select S.ID, D.Abbreviation, C.Number as CNum, S.Number as SNum, P.Name, S.Semester, S.Year from section as S, professor as P, course as C, department as D where C.D_ID = D.ID and P_ID = P.ID and C_ID = C.ID and C_ID = $newCor".($newProf != -1 && $newProf != null ? " and P_ID = $newProf;": ';');
    	output_named_dropdown_with_id('section', mysql_query($sql));
//     	echo "<pre>$sql</pre>";
    }
    else {
    	echo 'Dispatcher did not recognize valid case for input: '.var_export($_GET, true);
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
dispatcher(clean_input($_GET['uni']),
		clean_input($_GET['oldUni']),
		clean_input($_GET['dep']),
		clean_input($_GET['oldDep']),
		clean_input($_GET['prof']),
		clean_input($_GET['oldProf']),
		clean_input($_GET['cor']),
		clean_input($_GET['oldCor']),
		clean_input($_GET['sec']),
		clean_input($_GET['oldSec'])
		);
