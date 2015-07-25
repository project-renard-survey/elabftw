<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
require_once '../inc/common.php';
// it might take some time and we don't want to be cut in the middle, so set time_limit to ∞
set_time_limit(0);

$inserted = 0;
$errflag = false;
$msg_arr = array();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die();
}

// look at mime type. not a trusted source, but it can prevent dumb errors
$mimes = array(null, 'application/vnd.ms-excel', 'text/plain', 'text/csv', 'text/tsv', 'application/zip', 'application/force-download');
if (!in_array($_FILES['file']['type'], $mimes)) {
    $errflag = true;
    $msg_arr[] = sprintf(_("This doesn't look like a .%s file. Import aborted."), $type);
}

// try to read the file
if (!is_readable($_FILES['file']['tmp_name'])) {
    $errflag = true;
    $msg_arr[] = _("Could not open the file.");
}

// get what type we want
if (isset($_COOKIE['itemType']) && is_pos_int($_COOKIE['itemType'])) {
    $itemType = $_COOKIE['itemType'];
} else {
    $errflag = true;
    $msg_arr[] = _("No cookies found. Import aborted.");
}

// redirect user on error
if ($errflag) {
    $_SESSION['errors'] = $msg_arr;
    header('Location: ../admin.php');
    exit;
}

switch($_POST['type']) {
    case 'csv':
        // CODE TO IMPORT CSV
        $row = 0;
        $column = array();

        // open file
        $handle = fopen($_FILES['file']['tmp_name'], 'r');
        if ($handle === false) {
            $errflag = true;
        }

        // loop the lines
        while ($data = fgetcsv($handle, 0, ",")) {
            $num = count($data);
            // get the column names (first line)
            if ($row == 0) {
                for ($i = 0; $i < $num; $i++) {
                    $column[] = $data[$i];
                }
                $row++;
                continue;
            }
            $row++;

            $title = $data[0];
            $body = '';
            $j = 0;
            foreach ($data as $line) {
                $body .= "<p><strong>" . $column[$j] . " :</strong> " . $line . '</p>';
                $j++;
            }
            // clean the body
            $body = str_replace('<p><strong> :</strong> </p>', '', $body);

            // SQL for importing
            $sql = "INSERT INTO items(team, title, date, body, userid, type) VALUES(:team, :title, :date, :body, :userid, :type)";
            $req = $pdo->prepare($sql);
            $result = $req->execute(array(
                'team' => $_SESSION['team_id'],
                'title' => $title,
                'date' => kdate(),
                'body' => $body,
                'userid' => $_SESSION['userid'],
                'type' => $itemType
            ));
            if ($result) {
                $inserted++;
            } else {
                $errflag = true;
            }
        }
        fclose($handle);
        // END CODE TO IMPORT CSV
        break;

    case 'zip':
        // CODE TO IMPORT ZIP
        try {
            $import = new \Elabftw\Elabftw\ImportZip($_FILES['file']['tmp_name'], $itemType);
        } catch (Exception $e) {
            $errflag = true;
            $msg_arr[] = $e->getMessage();
        }
        $inserted = $import->inserted;
        // END CODE TO IMPORT ZIP
        break;
    default:
        $errflag = true;
}


// REDIRECT
if (!$errflag) {
    $msg_arr[] = $inserted . ' ' . ngettext('item imported successfully.', 'items imported successfully.', $inserted);
    $_SESSION['infos'] = $msg_arr;
    header('Location: ../database.php');
} else {
    $msg_arr[] = sprintf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug.") . "<br>E#17", "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
    $_SESSION['errors'] = $msg_arr;
    header('Location: ../admin.php');
}